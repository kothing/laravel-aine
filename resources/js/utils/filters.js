// Lightweight date formatter — drop-in replacement for the small subset of
// moment().format() tokens used across the app (YYYY, MM, MMM, DD, D, HH, H,
// hh, h, mm, A). Keeps moment out of the first-screen bundle.
const MONTHS = [
    "Jan", "Feb", "Mar", "Apr", "May", "Jun",
    "Jul", "Aug", "Sep", "Oct", "Nov", "Dec",
];

function pad(n) {
    return String(n).padStart(2, "0");
}

export function formatDate(date, format) {    const d = new Date(date);
    if (isNaN(d.getTime())) {
        return "";
    }
    const tokens = {
        YYYY: String(d.getFullYear()),
        MM: pad(d.getMonth() + 1),
        MMM: MONTHS[d.getMonth()],
        DD: pad(d.getDate()),
        D: String(d.getDate()),
        HH: pad(d.getHours()),
        H: String(d.getHours()),
        hh: pad(d.getHours() % 12 || 12),
        h: String(d.getHours() % 12 || 12),
        mm: pad(d.getMinutes()),
        A: d.getHours() >= 12 ? "PM" : "AM",
    };
    return format.replace(/YYYY|MMM|MM|DD|HH|hh|mm|D|H|h|A/g, (token) => tokens[token] ?? token);
}

export const filters = {
    prettyBytes(num, kib = 1000) {
        if (typeof num !== "number" || isNaN(num)) {
            throw new TypeError("Expected a number");
        }
        let exponent;
        let unit;
        let neg = num < 0;
        let units = ["B", "kB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];

        if (neg) {
            num = -num;
        }

        if (num < 1) {
            return (neg ? "-" : "") + num + " B";
        }

        exponent = Math.min(Math.floor(Math.log(num) / Math.log(kib)), units.length - 1);
        num = (num / Math.pow(kib, exponent)).toFixed(2) * 1;
        unit = units[exponent];

        return (neg ? "-" : "") + num + " " + unit;
    },

    date(value, arg = null) {
        if (value) {
            let format = "YYYY-MM-DD";
            if (arg !== null) {
                format = arg;
            }
            return formatDate(value, format);
        }
    },

    truncate(text, length, clamp) {
        let node = document.createElement("div");
        node.innerHTML = text;
        let content = node.textContent;
        return content.length > length ? content.slice(0, length) + (clamp || "...") : content;
    },
};

export function registerFilters(app) {
    app.config.globalProperties.$filters = filters;
}
