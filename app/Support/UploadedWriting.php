<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\IOFactory;
use Throwable;
use ZipArchive;

class UploadedWriting
{
    public static function toHtml(string $path, string $extension): string
    {
        $extension = strtolower($extension);

        $html = match ($extension) {
            'docx', 'docm' => self::docxToHtml($path),
            'doc' => self::phpWordToHtml($path, 'MsDoc'),
            'odt' => self::odtToHtml($path) ?: self::phpWordToHtml($path, 'ODText'),
            'html', 'htm' => PieceHtml::clean((string) file_get_contents($path)),
            default => self::plainToHtml(self::readText($path)),
        };

        return PieceHtml::clean($html);
    }

    private static function readText(string $path): string
    {
        $text = (string) file_get_contents($path);
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text;

        if ($text !== '' && ! mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }

        return str_replace(["\r\n", "\r"], "\n", $text);
    }

    private static function plainToHtml(string $text): string
    {
        $blocks = preg_split("/\n{2,}/", trim($text)) ?: [];
        $html = '';

        foreach ($blocks as $block) {
            $lines = array_values(array_filter(array_map(
                fn (string $line): string => e(trim($line)),
                explode("\n", $block)
            ), fn (string $line): bool => $line !== ''));

            if ($lines === []) {
                continue;
            }

            $html .= '<p>'.implode('<br>', $lines).'</p>';
        }

        return $html;
    }

    private static function docxToHtml(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! is_string($xml) || $xml === '') {
            return '';
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return '';
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $html = '';

        foreach ($xpath->query('//w:p') ?: [] as $paragraph) {
            $text = '';

            foreach ($xpath->query('.//w:t', $paragraph) ?: [] as $node) {
                $text .= $node->textContent;
            }

            $text = trim($text);

            if ($text === '') {
                continue;
            }

            $html .= '<p>'.e($text).'</p>';
        }

        return $html;
    }

    private static function odtToHtml(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = $zip->getFromName('content.xml');
        $zip->close();

        if (! is_string($xml) || $xml === '') {
            return '';
        }

        return self::openDocumentXmlToHtml($xml);
    }

    private static function openDocumentXmlToHtml(string $xml): string
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return '';
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');
        $html = '';

        foreach ($xpath->query('//text:h|//text:p') ?: [] as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            if (self::insideParagraph($paragraph)) {
                continue;
            }

            $text = trim(self::openDocumentText($paragraph));

            if ($text === '') {
                continue;
            }

            $lines = array_values(array_filter(array_map(
                fn (string $line): string => e(trim($line)),
                explode("\n", $text)
            ), fn (string $line): bool => $line !== ''));
            $inner = implode('<br>', $lines);
            $html .= $paragraph->localName === 'h' ? '<h2>'.$inner.'</h2>' : '<p>'.$inner.'</p>';
        }

        return $html;
    }

    private static function insideParagraph(DOMElement $node): bool
    {
        $parent = $node->parentNode;

        while ($parent instanceof DOMElement) {
            if (in_array($parent->localName, ['p', 'h'], true)) {
                return true;
            }

            $parent = $parent->parentNode;
        }

        return false;
    }

    private static function openDocumentText(DOMNode $node): string
    {
        $text = '';

        foreach ($node->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                $text .= $child->textContent;

                continue;
            }

            if ($child->localName === 'line-break') {
                $text .= "\n";

                continue;
            }

            if ($child->localName === 'tab') {
                $text .= "\t";

                continue;
            }

            if ($child->localName === 's') {
                $count = (int) ($child->getAttributeNS('urn:oasis:names:tc:opendocument:xmlns:text:1.0', 'c') ?: 1);
                $text .= str_repeat(' ', max(1, $count));

                continue;
            }

            $text .= self::openDocumentText($child);
        }

        return $text;
    }

    private static function phpWordToHtml(string $path, string $reader): string
    {
        try {
            $phpWord = IOFactory::load($path, $reader);
        } catch (Throwable) {
            return '';
        }

        $html = '';

        foreach ($phpWord->getSections() as $section) {
            $html .= self::elementsToHtml($section->getElements());
        }

        return $html;
    }

    /**
     * @param  array<int, mixed>  $elements
     */
    private static function elementsToHtml(array $elements): string
    {
        $html = '';

        foreach ($elements as $element) {
            if ($element instanceof TextBreak) {
                continue;
            }

            if ($element instanceof Title) {
                $html .= self::paragraphHtml((string) $element->getText(), true);

                continue;
            }

            if ($element instanceof ListItem) {
                $text = $element->getText();
                $html .= self::paragraphHtml(is_array($text) ? implode('', $text) : (string) $text);

                continue;
            }

            if ($element instanceof TextRun || $element instanceof ListItemRun) {
                $html .= self::paragraphHtml(self::inlineText($element->getElements()));

                continue;
            }

            if ($element instanceof Text) {
                $html .= self::paragraphHtml((string) $element->getText());

                continue;
            }

            if ($element instanceof Table) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $html .= self::elementsToHtml($cell->getElements());
                    }
                }

                continue;
            }

            if ($element instanceof AbstractContainer) {
                $html .= self::elementsToHtml($element->getElements());
            }
        }

        return $html;
    }

    /**
     * @param  array<int, mixed>  $elements
     */
    private static function inlineText(array $elements): string
    {
        $text = '';

        foreach ($elements as $element) {
            if ($element instanceof TextBreak) {
                $text .= "\n";

                continue;
            }

            if (is_object($element) && method_exists($element, 'getText')) {
                $value = $element->getText();
                $text .= is_array($value) ? implode('', $value) : (string) $value;

                continue;
            }

            if ($element instanceof AbstractContainer) {
                $text .= self::inlineText($element->getElements());
            }
        }

        return $text;
    }

    private static function paragraphHtml(string $text, bool $heading = false): string
    {
        $lines = array_values(array_filter(array_map(
            fn (string $line): string => e(trim($line)),
            explode("\n", $text)
        ), fn (string $line): bool => $line !== ''));

        if ($lines === []) {
            return '';
        }

        $inner = implode('<br>', $lines);

        return $heading ? '<h2>'.$inner.'</h2>' : '<p>'.$inner.'</p>';
    }
}
