/**
 * Extracts static UI strings from the admin Vue files and writes them to
 * database/seeders/data/admin_strings.php (the seed source for the
 * admin_string_sources registry table).
 * Run with:  node scripts/extract-admin-strings.js
 */
const fs = require("fs");
const path = require("path");

const ROOT = path.resolve(__dirname, "..");
const ADMIN_DIR = path.join(ROOT, "resources", "js", "admin");
const OUT = path.join(ROOT, "database", "seeders", "data", "admin_strings.php");

// Pure-uppercase HTML tag constants used in engine checks (e.g. "SCRIPT").
const HTML_TAGS = new Set([
    "A","ABBR","ADDRESS","AREA","ARTICLE","ASIDE","AUDIO","B","BASE","BDI","BDO",
    "BLOCKQUOTE","BODY","BR","BUTTON","CANVAS","CAPTION","CITE","CODE","COL",
    "COLGROUP","DATA","DATALIST","DD","DEL","DETAILS","DFN","DIALOG","DIV","DL",
    "DT","EM","EMBED","FIELDSET","FIGCAPTION","FIGURE","FOOTER","FORM","H1","H2",
    "H3","H4","H5","H6","HEAD","HEADER","HGROUP","HR","HTML","I","IFRAME","IMG",
    "INPUT","INS","KBD","LABEL","LEGEND","LI","LINK","MAIN","MAP","MARK","MENU",
    "META","METER","NAV","NOSCRIPT","OBJECT","OL","OPTGROUP","OPTION","OUTPUT",
    "P","PARAM","PICTURE","PRE","PROGRESS","Q","RP","RT","RUBY","S","SAMP",
    "SCRIPT","SECTION","SELECT","SLOT","SMALL","SOURCE","SPAN","STRONG","STYLE",
    "SUB","SUMMARY","SUP","TABLE","TBODY","TD","TEMPLATE","TEXTAREA","TFOOT",
    "TH","THEAD","TIME","TITLE","TR","TRACK","U","UL","VAR","VIDEO","WBR",
]);

const strings = new Set();

/** Lexically strip // and /* *\/ comments, keeping string literals intact. */
function stripComments(src) {
    let out = "";
    let i = 0;
    const n = src.length;
    while (i < n) {
        const c = src[i];
        const next = src[i + 1];
        if (c === "/" && next === "/") {
            while (i < n && src[i] !== "\n") i++;
        } else if (c === "/" && next === "*") {
            i += 2;
            while (i < n && !(src[i] === "*" && src[i + 1] === "/")) i++;
            i += 2;
        } else if (c === '"' || c === "'" || c === "`") {
            const q = c;
            out += c;
            i++;
            while (i < n) {
                if (src[i] === "\\") {
                    out += src[i] + (src[i + 1] || "");
                    i += 2;
                    continue;
                }
                out += src[i];
                if (src[i] === q) {
                    i++;
                    break;
                }
                i++;
            }
        } else {
            out += c;
            i++;
        }
    }
    return out;
}

function walk(dir) {
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) walk(full);
        else if (entry.name.endsWith(".vue")) collectVue(full);
        else if (entry.name.endsWith(".js"))
            collectStrings(stripComments(fs.readFileSync(full, "utf8")));
    }
}

function collectVue(file) {
    const src = fs.readFileSync(file, "utf8");
    const templateStart = src.indexOf("<template>");
    const templateEnd = src.lastIndexOf("</template>");
    if (templateStart !== -1 && templateEnd > templateStart) {
        const html = src.slice(templateStart + "<template>".length, templateEnd);

        // 1) Static text nodes: >TEXT<  (TEXT may contain entities, spaces,
        //    dashes and "{{ ... }}" interpolations, which are normalized)
        const textRe = />((?:[^<>{}]|\{\{[^{}]*\}\}){2,300})</g;
        let m;
        while ((m = textRe.exec(html)) !== null) {
            const clean = m[1]
                .replace(/&amp;/g, "&").replace(/&lt;/g, "<").replace(/&gt;/g, ">")
                .replace(/&#39;/g, "'").replace(/&quot;/g, '"')
                .replace(/\{\{\s*([^{}]*?)\s*\}\}/g, (_, expr) => normalizeInterpolation(expr))
                .replace(/\s+/g, " ").trim();
            if (isMeaningful(clean, true)) strings.add(clean);
        }

        const mustacheRe = /\{\{([\s\S]*?)\}\}/g;
        let mm;
        while ((mm = mustacheRe.exec(html)) !== null) {
            collectLiterals(mm[1], true);
        }

        // 2) Static placeholder attributes (skip :placeholder / @placeholder bindings)
        const phRe = /(?<![:\w@])placeholder="([^"{}]{2,120})"/g;
        while ((m = phRe.exec(html)) !== null) {
            const clean = m[1].trim();
            if (isMeaningful(clean)) strings.add(clean);
        }

        // 3) Directive-bound string literals: v-tooltip="'Open website'"
        const vRe = /v-[\w-]+="\s*'([^'"]{2,80})'\s*"/g;
        while ((m = vRe.exec(html)) !== null) {
            const clean = m[1].trim();
            if (isMeaningful(clean)) strings.add(clean);
        }

        // 4) String literals passed to the explicit __() helper:
        //    {{ __('Inactive') }} / :placeholder="__('Search...')".
        //    Matches the whole quoted literal so parentheses inside the text
        //    (e.g. "...(Listings, Categories, ...)") don't break the match.
        const callRe = /__\(\s*(['"])((?:\\.|(?!\1)[^\\\n]){2,120})\1/g;
        while ((m = callRe.exec(html)) !== null) {
            const clean = m[2]
                .replace(/\\(['"\\nrt])/g, (_, c) => {
                    if (c === "n") return "\n";
                    if (c === "r") return "\r";
                    if (c === "t") return "\t";
                    return c;
                })
                .replace(/\s+/g, " ")
                .trim();
            if (isMeaningful(clean)) strings.add(clean);
        }

        // 5) String literals inside attribute bindings, e.g.
        //    :placeholder="locale === sourceLocale ? item.source : 'Not translated'"
        const bindRe = /:[a-z-]+="([^"]*)"/g;
        while ((m = bindRe.exec(html)) !== null) {
            collectLiterals(m[1]);
        }
    }

    // 4) Human-readable strings in the <script> block
    const scriptStart = src.indexOf("<script");
    const scriptEnd = src.lastIndexOf("</script>");
    if (scriptStart !== -1 && scriptEnd > scriptStart) {
        collectStrings(stripComments(src.slice(scriptStart, scriptEnd)));
    }
}

/** Extract quoted string literals from a snippet and add meaningful ones. */
function collectLiterals(snippet, allowLowercaseToken = false) {
    const litRe = /(['"])((?:\\.|(?!\1)[^\\\n])*?)\1/g;
    let m;
    while ((m = litRe.exec(snippet)) !== null) {
        const clean = m[2]
            .replace(/\\(['"\\nrt])/g, (_, c) => {
                if (c === "n") return "\n";
                if (c === "r") return "\r";
                if (c === "t") return "\t";
                return c;
            })
            .replace(/\s+/g, " ")
            .trim();
        if (isMeaningful(clean, allowLowercaseToken)) strings.add(clean);
    }
}

/** Extract human-readable string literals from a JS snippet (no comments). */
function collectStrings(js) {
    // console.* calls are diagnostics, not UI copy (covers single-call,
    // non-nested cases like console.warn(x || "Forbidden")).
    js = js.replace(/console\.\w+\([^()]*\)/g, "");

    const strRe = /(['"`])((?:\\[\s\S]|(?!\1)[^\\\n])*)\1/g;
    let m;
    while ((m = strRe.exec(js)) !== null) {
        const quote = m[1];
        let body = m[2];
        if (!body) continue;
        const clean = body
            .replace(/\\(['"`\\nrt])/g, (_, c) => {
                if (c === "n") return "\n";
                if (c === "r") return "\r";
                if (c === "t") return "\t";
                return c;
            })
            .replace(/\$\{([^}]*)\}/g, (_, expr) => normalizeInterpolation(expr)) // interpolated template literals
            .replace(/\s+/g, " ")
            .trim();
        if (isMeaningful(clean)) strings.add(clean);
    }
}

/**
 * Normalize a Vue/JS interpolation expression into a named placeholder.
 *
 *   {{ totalCount }}      → {totalCount}      (simple identifier, keep name)
 *   {{ item.name }}       → {item.name}→{itemName}  (member access → camelCase)
 *   {{ $filters.date(x) }} → {value}           (complex expression → generic)
 *
 * Simple identifiers and dotted member access become readable names so
 * translators see WHAT value fills the slot. Anything more complex
 * (function calls, ternaries, arithmetic) degrades to the generic {value},
 * since the expression can't be turned into a meaningful label.
 */
function normalizeInterpolation(expr) {
    const trimmed = (expr || "").trim();
    if (!trimmed) return "{value}";
    // Simple identifier: totalCount, draftCount, code
    if (/^[a-zA-Z_$][a-zA-Z0-9_$]*$/.test(trimmed)) {
        return "{" + trimmed + "}";
    }
    // Dotted member access: item.name → itemName, settings.version → settingsVersion
    const dotted = trimmed.match(/^([a-zA-Z_$][a-zA-Z0-9_$]*)\.([a-zA-Z_$][a-zA-Z0-9_$]*)$/);
    if (dotted) {
        const head = dotted[1];
        const tail = dotted[2];
        const camelTail = tail.charAt(0).toUpperCase() + tail.slice(1);
        return "{" + head + camelTail + "}";
    }
    // Anything else (calls, ternaries, arithmetic, filters) → generic.
    return "{value}";
}

function isMeaningful(s, allowLowercaseToken = false) {
    // Collapse placeholders before judging, so their inner content doesn't
    // disguise paths (e.g. "localization/{name}").
    const compact = s.replace(/\{\{[^{}]*\}\}/g, "{}").replace(/\{[^{}]*\}/g, "{}");
    if (!compact || compact.length < 2) return false;
    if (!/[A-Za-z]/.test(compact)) return false;   // must contain letters
    // Icon / CSS class combos (e.g. "fas fa-lock", "bi bi-trash")
    if (/^(fa|fas|far|fal|fab|bi|bx|bx-|mdi|icon|glyphicon|el-icon)\b/i.test(compact)) return false;
    // Markup / code fragments
    if (/^[<>&?$="']/.test(compact)) return false; // <tag, &param, ?query, $route, =expr, "attr
    if (/^[()[\]]/.test(compact)) return false;    // regex / selector (NOT "{{", which is a placeholder)
    if (/^\w+:\s/.test(compact) && !compact.includes('{}')) return false; // "background: true", but keep "Description: {name}" labels
    // (type badges such as ": multiple" are UI copy and are now kept)
    if (/^\d+px$/.test(compact)) return false;     // "0px"
    if (/^(https?:)?\/\//.test(compact)) return false; // http(s)://
    if (/^[\/.#](?=\S)/.test(compact)) return false; // /path, ./path, #hash (keep "/ Forms", ". You can...")
    // Pure-uppercase HTML tag constants (OPTION / SCRIPT / STYLE ...)
    if (/^[A-Z]+$/.test(compact) && HTML_TAGS.has(compact)) return false;
    // Component / directive name prefixes (VTooltip, UiSwitch, AppContent)
    if (/^(V|Ui|App)[A-Z]/.test(compact) && !/^[A-Z]+$/.test(compact)) return false;
    if (!/\s/.test(compact)) {
        // No whitespace: keep only clear label-like tokens (PascalCase /
        // ALL CAPS words such as "Dashboard", "English", "Home") — drop
        // paths, camelCase / kebab-case code tokens, sizes and identifiers.
        if (/\//.test(compact)) return false;      // paths / URLs
        if (/^[a-z]/.test(compact)) {
            if (allowLowercaseToken && /^[a-z][a-z-]*$/.test(compact)) return true;
            return false;  // lowercase tokens → code
        }
        if (/\d/.test(compact) && !/[A-Z]/.test(compact)) return false; // "256x256", "0px"
        if (compact.length > 30) return false;     // long identifiers
        if (/[-]/.test(compact)) {
            if (allowLowercaseToken && /^[A-Z][A-Za-z]*-[A-Za-z]+$/.test(compact)) return true;
            return false;     // "X-Csrf-Token"
        }
        if (/^[A-Za-z]+$/.test(compact) && compact.length > 12) return false; // component names / charsets
        return true;
    }
    // Has whitespace: drop all-lowercase kebab strings such as
    // "-mb-px inline-flex items-center border-b-2 ..." (Tailwind classes).
    if (!/[A-Z]/.test(compact) && /-\w/.test(compact) && !/^:\s/.test(compact)) return false;
    return true;
}

walk(ADMIN_DIR);
// Admin entry file lives outside resources/js/admin
{
    const entry = path.join(ROOT, "resources", "js", "admin.js");
    if (fs.existsSync(entry)) collectStrings(stripComments(fs.readFileSync(entry, "utf8")));
}

const sorted = [...strings].sort((a, b) => a.localeCompare(b));

const phpLines = sorted.map((s) => {
    const escaped = s.replace(/\\/g, "\\\\").replace(/'/g, "\\'");
    return "    '" + escaped + "'";
});

// Preserve the hand-maintained 'defaults' block (factory default
// translations, e.g. zh) verbatim across runs.
let defaultsBlock = "";
const existing = fs.existsSync(OUT) ? fs.readFileSync(OUT, "utf8") : "";
const dIdx = existing.indexOf("    'defaults' => [");
if (dIdx !== -1) {
    defaultsBlock = existing.slice(dIdx);
} else {
    defaultsBlock = "    'defaults' => [\n        // 'zh' => [\n        //     'Some English string' => '中文翻译',\n        // ],\n    ],\n";
}

const php = `<?php

/*
|--------------------------------------------------------------------------
| Admin UI string registry (seed source only)
|--------------------------------------------------------------------------
|
| Machine-extracted static strings from resources/js/admin (vue/js files).
| These are the translatable UI labels shown in the Translations admin page.
|
| This file is the SEED SOURCE ONLY. Runtime reads happen from the database:
|   - admin_string_sources       -> registry of translatable strings
|   - admin_translation_defaults -> factory default translations per locale
|   - translations               -> runtime translations (editable in the admin panel)
|
| Regenerate the registry with: node scripts/extract-admin-strings.js
| Then import into the database with:
|   php artisan db:seed --class=AdminTranslationsSeeder
|
*/

return [
    'sources' => [
${phpLines.join(",\n")}
    ],
${defaultsBlock}`;

fs.writeFileSync(OUT, php);
console.log(`Extracted ${sorted.length} strings -> database/seeders/data/admin_strings.php`);
console.log(`Sync the registry into the database with:  php artisan db:seed --class=AdminTranslationsSeeder`);
