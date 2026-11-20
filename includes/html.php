<?php
/**
 * Allow-list HTML sanitiser for blog post content written in the admin editor.
 * Removes scripts, event handlers, inline styles, iframes and unsafe URLs.
 */
if (!defined('APP_ROOT')) { http_response_code(403); exit; }

function sanitize_html(string $html): string
{
    $html = trim($html);
    if ($html === '') { return ''; }

    if (!class_exists('DOMDocument')) {
        // Very defensive fallback: keep only basic formatting tags, no attributes.
        $clean = strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><blockquote>');
        return preg_replace('/<(\w+)\s[^>]*>/', '<$1>', $clean);
    }

    $doc = new DOMDocument('1.0', 'UTF-8');
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"?><!DOCTYPE html><html><body><div id="sanitize-root">' . $html . '</div></body></html>', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = (new DOMXPath($doc))->query('//div[@id="sanitize-root"]')->item(0);
    if (!$root) { return ''; }

    sanitize_node($root);

    $out = '';
    foreach ($root->childNodes as $child) { $out .= $doc->saveHTML($child); }
    // Drop empty paragraphs the editor sometimes leaves at the end.
    $out = preg_replace('#(<p>(\s|&nbsp;|<br>)*</p>\s*)+$#', '', trim($out));
    return trim($out);
}

function sanitize_allowed_tags(): array
{
    return [
        'p' => [], 'br' => [], 'h2' => [], 'h3' => [], 'h4' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [],
        'a' => ['href', 'title', 'target'],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [], 'pre' => [], 'code' => [], 'hr' => [],
        'img' => ['src', 'alt', 'width', 'height'],
        'figure' => [], 'figcaption' => [], 'span' => [],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
    ];
}

function sanitize_node(DOMNode $node): void
{
    static $remove = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select',
                      'link', 'meta', 'svg', 'math', 'noscript', 'template', 'frame', 'frameset', 'applet', 'base', 'head', 'title'];
    $allowed = sanitize_allowed_tags();

    foreach (iterator_to_array($node->childNodes) as $child) {
        if ($child instanceof DOMText) { continue; }

        if (!($child instanceof DOMElement)) { // comments, processing instructions…
            $node->removeChild($child);
            continue;
        }

        $tag = strtolower($child->nodeName);

        if (in_array($tag, $remove, true)) {
            $node->removeChild($child);
            continue;
        }

        // Quill writes bullet lists as <ol><li data-list="bullet">.
        if ($tag === 'ol') {
            $firstLi = null;
            foreach ($child->childNodes as $c) { if ($c instanceof DOMElement && strtolower($c->nodeName) === 'li') { $firstLi = $c; break; } }
            if ($firstLi && $firstLi->getAttribute('data-list') === 'bullet') {
                $child = sanitize_rename($child, 'ul');
                $tag = 'ul';
            }
        }

        if ($tag === 'div') { // treat editor <div>s as paragraphs
            $child = sanitize_rename($child, 'p');
            $tag = 'p';
        }
        if ($tag === 'h1') { $child = sanitize_rename($child, 'h2'); $tag = 'h2'; }

        if (!isset($allowed[$tag])) {
            // Unknown tag: keep its (cleaned) contents, drop the tag itself.
            sanitize_node($child);
            while ($child->firstChild) { $node->insertBefore($child->firstChild, $child); }
            $node->removeChild($child);
            continue;
        }

        // Attributes
        foreach (iterator_to_array($child->attributes) as $attr) {
            $name = strtolower($attr->nodeName);
            $value = $attr->nodeValue;
            $keep = in_array($name, $allowed[$tag], true);

            if ($name === 'class') {
                $classes = array_filter(preg_split('/\s+/', $value) ?: [], static function ($c) {
                    return (bool)preg_match('/^ql-(align-(center|right|justify)|indent-[1-8])$/', $c);
                });
                if ($classes) { $child->setAttribute('class', implode(' ', $classes)); continue; }
                $keep = false;
            }
            if ($keep && ($name === 'href' || $name === 'src')) {
                $safe = sanitize_url($value, $name === 'src');
                if ($safe === null) { $keep = false; } else { $child->setAttribute($name, $safe); }
            }
            if ($keep && ($name === 'width' || $name === 'height' || $name === 'colspan' || $name === 'rowspan')) {
                $keep = ctype_digit($value);
            }
            if ($keep && $name === 'target') {
                $keep = $value === '_blank';
            }
            if (!$keep) { $child->removeAttribute($attr->nodeName); }
        }

        if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
            $child->setAttribute('rel', 'noopener noreferrer');
        }
        if ($tag === 'img') {
            if (!$child->hasAttribute('src')) { $node->removeChild($child); continue; }
            $child->setAttribute('loading', 'lazy');
            if (!$child->hasAttribute('alt')) { $child->setAttribute('alt', ''); }
        }

        sanitize_node($child);
    }
}

function sanitize_rename(DOMElement $el, string $newTag): DOMElement
{
    $new = $el->ownerDocument->createElement($newTag);
    foreach (iterator_to_array($el->attributes) as $attr) { $new->setAttribute($attr->nodeName, $attr->nodeValue); }
    while ($el->firstChild) { $new->appendChild($el->firstChild); }
    $el->parentNode->replaceChild($new, $el);
    return $new;
}

/** Returns the URL if its scheme is safe, otherwise null. */
function sanitize_url(string $url, bool $isImage = false): ?string
{
    $url = trim($url);
    if ($url === '') { return null; }
    $probe = preg_replace('/[\x00-\x20\x7F]+/', '', $url);
    if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $probe, $m)) {
        $scheme = strtolower($m[1]);
        $ok = $isImage ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'];
        return in_array($scheme, $ok, true) ? $url : null;
    }
    return $url; // relative, root-relative, #anchor or //protocol-relative
}
