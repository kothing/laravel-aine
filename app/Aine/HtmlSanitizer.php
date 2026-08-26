<?php

namespace App\Aine;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Whitelist-based HTML sanitizer for rich text (TinyMCE) content.
 *
 * Rather than trying to detect "bad" input, everything not explicitly
 * allowed is removed:
 *
 *  - tags outside the whitelist are dropped (script/style/object/form/...)
 *    or unwrapped while keeping their visible text;
 *  - every attribute is checked, event handlers (on*) are always stripped
 *    and URL attributes are protocol-checked (no javascript:/data: unless
 *    a data:image for <img>);
 *  - inline styles are reduced to a safe visual subset;
 *  - <iframe> embeds are restricted to known video providers.
 *
 * The sanitizer is intentionally tolerant of malformed HTML and never
 * throws: on failure the original input is returned unchanged so content
 * is never lost.
 */
class HtmlSanitizer
{
    /** Tags allowed to remain in the output. */
    private const ALLOWED_TAGS = [
        'a', 'abbr', 'b', 'blockquote', 'br', 'caption', 'cite', 'code',
        'col', 'colgroup', 'dd', 'del', 'dfn', 'div', 'dl', 'dt', 'em',
        'figcaption', 'figure', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr',
        'i', 'img', 'ins', 'kbd', 'li', 'mark', 'ol', 'p', 'pre', 'q', 's',
        'samp', 'small', 'span', 'strike', 'strong', 'sub', 'sup', 'table',
        'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'u', 'ul', 'var',
        'iframe',
    ];

    /** Tags removed together with their content. */
    private const DROP_TAGS = [
        'script', 'style', 'object', 'embed', 'meta', 'link', 'base',
        'form', 'input', 'select', 'textarea', 'button', 'option',
    ];

    /** Per-tag attribute whitelists (lower-case). */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading'],
        'iframe' => ['src', 'width', 'height', 'frameborder', 'allow', 'allowfullscreen', 'scrolling', 'title'],
        'td' => ['colspan', 'rowspan', 'align', 'valign', 'width'],
        'th' => ['colspan', 'rowspan', 'align', 'valign', 'width'],
        'table' => ['border', 'cellpadding', 'cellspacing', 'width', 'align'],
        'col' => ['span', 'width'],
        'colgroup' => ['span', 'width'],
    ];

    private const GLOBAL_ATTRIBUTES = [
        'title', 'class', 'id', 'dir', 'lang', 'style',
    ];

    /** Inline style properties kept on elements. */
    private const ALLOWED_STYLE_PROPERTIES = [
        'text-align', 'color', 'background-color', 'font-family',
        'font-size', 'font-weight', 'font-style', 'text-decoration',
        'line-height', 'margin', 'margin-top', 'margin-right',
        'margin-bottom', 'margin-left', 'padding', 'padding-top',
        'padding-right', 'padding-bottom', 'padding-left', 'width',
        'height', 'float', 'clear', 'border', 'border-top', 'border-right',
        'border-bottom', 'border-left', 'border-radius',
        'list-style-type', 'vertical-align', 'white-space', 'max-width',
    ];

    /** Hosts allowed for <iframe> embeds. */
    private const IFRAME_ALLOWED_HOSTS = [
        'youtube.com', 'www.youtube.com',
        'youtube-nocookie.com', 'www.youtube-nocookie.com',
        'player.vimeo.com', 'vimeo.com',
        'open.spotify.com',
        'player.bilibili.com',
    ];

    /**
     * Sanitize a piece of HTML.
     */
    public static function sanitize(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        if (trim($html) === '') {
            return $html;
        }

        try {
            $doc = new DOMDocument();
            libxml_use_internal_errors(true);

            // Prefix with an xml encoding declaration so non-ASCII input is
            // parsed without warnings.
            $loaded = $doc->loadHTML(
                '<?xml encoding="utf-8"?>'.$html,
                LIBXML_NOERROR | LIBXML_NOWARNING
            );

            if (! $loaded) {
                return $html;
            }

            foreach (iterator_to_array($doc->childNodes) as $child) {
                (new self())->cleanNode($child);
            }

            $body = $doc->getElementsByTagName('body')->item(0);

            if (! $body) {
                return $html;
            }

            $out = '';
            foreach ($body->childNodes as $child) {
                $out .= $doc->saveHTML($child);
            }

            return $out;
        } catch (\Throwable) {
            return $html;
        }
    }

    /**
     * Recursively clean a node (children first, so removals do not skip).
     */
    private function cleanNode(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->cleanNode($child);
        }

        if ($node instanceof DOMElement) {
            $this->cleanElement($node);
        }
    }

    private function cleanElement(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);

        if (in_array($tag, self::DROP_TAGS, true)) {
            $this->dropElement($element);

            return;
        }

        // The document skeleton must stay in place so the serialization can
        // still locate <body>; its attributes are sanitized like any other.
        if (in_array($tag, ['html', 'head', 'body'], true)) {
            $this->sanitizeAttributes($element, $tag);

            return;
        }

        if (! in_array($tag, self::ALLOWED_TAGS, true)) {
            $this->unwrapElement($element);

            return;
        }

        // iframes are only kept for whitelisted video providers.
        if ($tag === 'iframe' && ! $this->isAllowedIframeSrc($element->getAttribute('src'))) {
            $this->dropElement($element);

            return;
        }

        $this->sanitizeAttributes($element, $tag);
    }

    /**
     * Strip or normalize every attribute of an element according to the
     * per-tag whitelist.
     */
    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = array_merge(
            self::GLOBAL_ATTRIBUTES,
            self::ALLOWED_ATTRIBUTES[$tag] ?? []
        );

        $toRemove = [];
        foreach ($element->attributes as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = $attribute->nodeValue;

            // Event handlers are always dangerous.
            if (str_starts_with($name, 'on')) {
                $toRemove[] = $attribute->nodeName;

                continue;
            }

            if (! in_array($name, $allowed, true)) {
                $toRemove[] = $attribute->nodeName;

                continue;
            }

            if (in_array($name, ['href', 'src', 'action', 'poster'], true)
                && ! $this->isSafeUrl($value, $tag, $name)) {
                $toRemove[] = $attribute->nodeName;

                continue;
            }

            if ($name === 'style') {
                $clean = $this->sanitizeStyle($value);
                if ($clean === '') {
                    $toRemove[] = $attribute->nodeName;
                } else {
                    $attribute->nodeValue = $clean;
                }
            }
        }

        foreach ($toRemove as $name) {
            $element->removeAttribute($name);
        }
    }

    /**
     * Remove an element together with its content.
     */
    private function dropElement(DOMElement $element): void
    {
        if ($element->parentNode) {
            $element->parentNode->removeChild($element);
        }
    }

    /**
     * Unwrap an element, keeping its children (visible text is preserved).
     */
    private function unwrapElement(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
    }

    /**
     * Restrict inline styles to a safe visual subset.
     */
    private function sanitizeStyle(string $style): string
    {
        $parts = explode(';', $style);
        $safe = [];

        foreach ($parts as $part) {
            $pair = explode(':', $part, 2);
            if (count($pair) !== 2) {
                continue;
            }

            $property = strtolower(trim($pair[0]));
            $value = trim($pair[1]);

            if (! in_array($property, self::ALLOWED_STYLE_PROPERTIES, true)) {
                continue;
            }

            // Reject anything that can carry code or remote resources.
            if (preg_match('/(url\s*\(|expression|javascript:|behaviour|@import|\\\\|&#)/i', $value)) {
                continue;
            }

            $safe[] = $property.': '.$value;
        }

        return implode('; ', $safe);
    }

    /**
     * URL protocol check. Relative URLs and fragments are fine; only a
     * handful of schemes are allowed and data: is restricted to images.
     */
    private function isSafeUrl(string $url, string $tag, string $attribute): bool
    {
        $url = trim($url);
        if ($url === '') {
            return true;
        }

        // Remove control characters and whitespace used to obfuscate schemes.
        $cleaned = (string) preg_replace('/[\x00-\x20\x7f]+/u', '', $url);
        $lower = strtolower($cleaned);

        // No scheme: relative path or fragment.
        if (! preg_match('~^[a-z][a-z0-9+.\-]*:~', $lower)) {
            return true;
        }

        $scheme = (string) parse_url($lower, PHP_URL_SCHEME);

        if (in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return true;
        }

        if ($scheme === 'data' && $tag === 'img'
            && str_starts_with($lower, 'data:image/')) {
            return true;
        }

        return false;
    }

    /**
     * iframe src must belong to a whitelisted video provider.
     */
    private function isAllowedIframeSrc(string $src): bool
    {
        $host = strtolower((string) parse_url(trim($src), PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        foreach (self::IFRAME_ALLOWED_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.ltrim($allowed, '.'))) {
                return true;
            }
        }

        return false;
    }
}
