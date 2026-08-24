/**
 * Shared axios instance for the frontend with unified response handling.
 *
 * axios is used directly — there is NO extra request wrapper. Callers do:
 *
 *   import http from "./http";
 *   const response = await http.get("/api/...", { params });
 *
 * A single response interceptor enforces the unified rules:
 *   - network errors (timeout / no response)      → resolves `null`, shows a hint
 *   - HTTP errors (404, 403, 500, ...)            → resolves `null`, shows a hint
 *   - business errors (200 + `success: false`)    → resolves `null`, shows a hint
 *   - success                                     → normal axios response
 *     (`response.data` is the backend envelope { success, code, message, data },
 *     except endpoints like /settings that return a plain object)
 *
 * Resolving `null` instead of rejecting means views render empty/fallback
 * states instead of crashing. The hint shown depends on the response `code`:
 *   0 network | 400 | 401 | 403 | 404 | 408 | 422 | 429 | 500 | 502 | 503 | 504
 *
 * Language policy:
 *   - messages sent by the backend (`body.message`) are shown verbatim — the
 *     server owns their language
 *   - local fallback texts are authored in English (the frontend's base UI
 *     language) and pass through the translator registered via
 *     `setTranslator()`, so a UI i18n layer can be plugged in later without
 *     touching this file
 */

import axios from "axios";

export const DEFAULT_TIMEOUT = 15000;

/** code → base-language hint used when the backend sent no message. */
export const ERROR_MESSAGES = {
    0: "Network error. Please check your connection and try again.",
    400: "Invalid request. Please review the data and try again.",
    401: "Your session has expired. Please sign in again.",
    403: "You don't have permission to access this content.",
    404: "The requested content was not found or has been removed.",
    405: "This request method is not allowed.",
    408: "The request timed out. Please try again.",
    422: "The submitted data failed validation. Please review and try again.",
    429: "Too many requests. Please slow down and try again.",
    500: "The server ran into an error. Please try again later.",
    502: "Bad gateway. Please try again later.",
    503: "The service is temporarily unavailable. Please try again later.",
    504: "Gateway timeout. Please try again later.",
};

const http = axios.create({
    timeout: DEFAULT_TIMEOUT,
    headers: {
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        Accept: "application/json",
    },
});

/* ------------------------------------------------------------------ *
 * Error reporting                                                     *
 * ------------------------------------------------------------------ */

/** Custom global handler; when set, it replaces the built-in toast. */
let errorHandler = null;
/** Whether the built-in lightweight toast is enabled. */
let errorToastEnabled = true;
/** Optional translator injected by the UI (e.g. a future i18n layer). */
let translator = null;

/**
 * Register a custom handler for API errors, e.g. to route them into your own
 * snackbar component: setApiErrorHandler(({ code, message, url }) => ...).
 * Pass null to fall back to the built-in toast.
 */
export function setApiErrorHandler(handler) {
    errorHandler = typeof handler === "function" ? handler : null;
}

/** Enable / disable the built-in error toast (default: enabled). */
export function setErrorToastEnabled(enabled) {
    errorToastEnabled = !!enabled;
}

/**
 * Plug in a translation function for local fallback texts, e.g.
 * setTranslator((text) => t(text)). The function receives the English source
 * string and must return the translated string (or the source unchanged).
 */
export function setTranslator(fn) {
    translator = typeof fn === "function" ? fn : null;
}

/** Pick the hint for a code, falling back to a safe default. */
export function errorMessageFor(code, fallback = "Request failed. Please try again.") {
    return ERROR_MESSAGES[code] || fallback;
}

/** Translate a local (non-server) message through the injected translator. */
function translate(text) {
    if (!translator) return text;
    try {
        const result = translator(text);
        return typeof result === "string" && result ? result : text;
    } catch (e) {
        return text;
    }
}

/* Dependency-free toast — no UI framework involved. */
let toastNode = null;
let toastTimer = null;
let toastStyleInjected = false;

function injectToastStyle() {
    if (toastStyleInjected || typeof document === "undefined") return;
    toastStyleInjected = true;
    const style = document.createElement("style");
    style.textContent = `
#aine-api-toast{position:fixed;left:50%;bottom:32px;transform:translateX(-50%) translateY(16px);z-index:99999;max-width:86vw;padding:10px 18px;border-radius:8px;background:rgba(17,24,39,.92);color:#fff;font-size:14px;line-height:1.5;box-shadow:0 6px 24px rgba(0,0,0,.18);opacity:0;pointer-events:none;transition:opacity .25s ease,transform .25s ease}
#aine-api-toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
#aine-api-toast.warn{background:rgba(180,83,9,.95)}
`;
    document.head.appendChild(style);
}

function showToast(message, level = "error") {
    if (typeof document === "undefined") return;
    injectToastStyle();
    if (!toastNode) {
        toastNode = document.createElement("div");
        toastNode.id = "aine-api-toast";
        document.body.appendChild(toastNode);
    }
    toastNode.textContent = message;
    toastNode.className = level === "warn" ? "warn" : "";
    requestAnimationFrame(() => toastNode.classList.add("show"));
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toastNode.classList.remove("show"), 2600);
}

/**
 * Single choke point for every API failure. The unified rules:
 *   - custom handler (if registered) receives the structured detail
 *   - otherwise a toast shows the message (if enabled)
 *   - otherwise the failure is logged to the console
 * `fromServer` marks messages coming from the backend: they are shown verbatim
 * (the server owns their language) and are never re-translated.
 */
function reportError({ code, message, data, url, status, fromServer = false }) {
    const codeNum = Number.isFinite(code) ? code : 0;
    const fallback = errorMessageFor(codeNum);
    const display = fromServer && message ? message : translate(message || fallback);
    const detail = { code: codeNum, message: display, data, url, status };

    if (errorHandler) {
        try {
            errorHandler(detail);
        } catch (e) {
            console.warn("[API] error handler failed:", e);
        }
        return;
    }
    if (errorToastEnabled) {
        showToast(display, codeNum === 0 ? "warn" : "error");
    } else {
        console.warn(`[API] ${codeNum} ${display}`, url || "");
    }
}

/* ------------------------------------------------------------------ *
 * Response interceptor — the single place where every response is
 * checked and failures are turned into `null` + a matching hint.        *
 * ------------------------------------------------------------------ */

http.interceptors.response.use(
    (response) => {
        const body = response.data;

        // Business error: HTTP 200 with `success: false`.
        if (body && typeof body === "object" && body.success === false) {
            const code = typeof body.code === "number" ? body.code : response.status;
            reportError({
                code,
                message: body.message,
                data: body.data,
                url: response.config.url,
                status: response.status,
                fromServer: true,
            });
            return null;
        }

        // Success — pass the normal axios response through untouched.
        return response;
    },
    (error) => {
        const status = error.response ? error.response.status : 0;

        if (error.response) {
            // The server answered with an HTTP error.
            const body = error.response.data || {};
            const code = typeof body.code === "number" ? body.code : status;
            reportError({
                code,
                message: body.message || "",
                data: body.data,
                url: error.config && error.config.url,
                status,
                fromServer: true,
            });
        } else if (error.code === "ECONNABORTED" || error.code === "ETIMEDOUT") {
            // Request timed out — local fallback (translated if registered).
            reportError({ code: 408, message: "", url: error.config && error.config.url, status: 0 });
        } else {
            // No response at all (offline, DNS, ...) — local fallback.
            reportError({ code: 0, message: "", url: error.config && error.config.url, status: 0 });
        }

        // Never reject: callers receive `null` and render fallback states.
        return null;
    }
);

export default http;
