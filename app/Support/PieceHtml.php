<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class PieceHtml
{
    /**
     * @var list<string>
     */
    private const ALLOWED_TAGS = [
        'p', 'br', 'div', 'span', 'b', 'strong', 'i', 'em', 'u', 's', 'strike',
        'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'blockquote', 'font',
    ];

    /**
     * @var list<string>
     */
    private const REMOVED_TAGS = ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta'];

    public static function clean(?string $html): string
    {
        $html = self::repairEncoding(trim((string) $html));

        if ($html === '') {
            return '';
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="piece-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('piece-root');

        if (! $root instanceof DOMElement) {
            return '';
        }

        self::cleanChildren($root);

        $clean = '';

        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
    }

    public static function repairEncoding(string $html): string
    {
        if ($html === '' || preg_match('/[\x{100}-\x{10FFFF}]/u', $html) === 1) {
            return $html;
        }

        $bytes = '';

        foreach (mb_str_split($html) as $char) {
            $code = mb_ord($char);

            if ($code === false || $code > 255) {
                return $html;
            }

            $bytes .= chr($code);
        }

        if ($bytes === $html || ! mb_check_encoding($bytes, 'UTF-8')) {
            return $html;
        }

        return $bytes;
    }

    private static function cleanChildren(DOMNode $node): void
    {
        if (! $node->hasChildNodes()) {
            return;
        }

        for ($index = $node->childNodes->length - 1; $index >= 0; $index--) {
            $child = $node->childNodes->item($index);

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::REMOVED_TAGS, true)) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            self::cleanChildren($child);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                self::unwrap($child);

                continue;
            }

            self::cleanAttributes($child);
        }
    }

    private static function cleanAttributes(DOMElement $element): void
    {
        $allowed = strtolower($element->tagName) === 'font'
            ? ['face', 'color', 'style']
            : ['style'];

        $remove = [];

        foreach ($element->attributes as $attribute) {
            if (! in_array(strtolower($attribute->name), $allowed, true)) {
                $remove[] = $attribute->name;
            }
        }

        foreach ($remove as $name) {
            $element->removeAttribute($name);
        }

        if ($element->hasAttribute('style')) {
            $style = self::cleanStyle($element->getAttribute('style'));

            if ($style === '') {
                $element->removeAttribute('style');
            } else {
                $element->setAttribute('style', $style);
            }
        }

        if ($element->hasAttribute('color') && ! self::safeColor($element->getAttribute('color'))) {
            $element->removeAttribute('color');
        }

        if ($element->hasAttribute('face') && ! self::safeFontFamily($element->getAttribute('face'))) {
            $element->removeAttribute('face');
        }
    }

    private static function cleanStyle(string $style): string
    {
        $kept = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);

            $safe = match ($property) {
                'font-family' => self::safeFontFamily($value),
                'font-size' => self::safeFontSize($value),
                'color', 'background-color' => self::safeColor($value),
                'text-align' => in_array(strtolower($value), ['left', 'center', 'right', 'justify', 'start', 'end'], true),
                default => false,
            };

            if ($safe) {
                $kept[] = $property.': '.$value;
            }
        }

        return implode('; ', $kept);
    }

    private static function safeFontFamily(string $value): bool
    {
        return strlen($value) <= 100 && preg_match('/^["\'a-z0-9 ,\-]+$/i', $value) === 1;
    }

    private static function safeFontSize(string $value): bool
    {
        if (preg_match('/^(\d{1,3}(?:\.\d+)?)(px|pt|rem|em)$/', $value, $matches) !== 1) {
            return false;
        }

        $size = (float) $matches[1];

        return $size >= 8 && $size <= 96;
    }

    private static function safeColor(string $value): bool
    {
        if (preg_match('/expression|url\s*\(|javascript|@import/i', $value) === 1) {
            return false;
        }

        return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value) === 1
            || preg_match('/^rgba?\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/i', $value) === 1
            || preg_match('/^[a-z]{3,20}$/i', $value) === 1;
    }

    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if ($parent === null) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
