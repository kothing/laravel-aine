/**
 * Lazy-load the SSML editor (IIFE bundle).
 */

let ssmlEditorPromise = null;

export function loadSsmlEditor() {
    if (ssmlEditorPromise) {
        return ssmlEditorPromise;
    }

    if (typeof window !== "undefined" && window.SSMLEditor) {
        ssmlEditorPromise = Promise.resolve(window.SSMLEditor);
        return ssmlEditorPromise;
    }

    ssmlEditorPromise = new Promise((resolve, reject) => {
        const cssHref = "/js/ssml-editor/style.css";
        if (!document.querySelector(`link[href="${cssHref}"]`)) {
            const cssLink = document.createElement("link");
            cssLink.rel = "stylesheet";
            cssLink.href = cssHref;
            document.head.appendChild(cssLink);
        }

        const jsSrc = "/js/ssml-editor/ssml-editor.iife.js";
        const existingScript = document.querySelector(`script[src="${jsSrc}"]`);

        if (existingScript && window.SSMLEditor) {
            resolve(window.SSMLEditor);
            return;
        }

        const script = document.createElement("script");
        script.src = jsSrc;
        script.async = true;
        script.onload = () => {
            if (window.SSMLEditor) {
                resolve(window.SSMLEditor);
            } else {
                reject(new Error("SSML editor script loaded but window.SSMLEditor is not available"));
            }
        };
        script.onerror = () => {
            ssmlEditorPromise = null;
            reject(new Error(`Failed to load SSML editor script: ${jsSrc}`));
        };
        document.head.appendChild(script);
    });

    return ssmlEditorPromise;
}
