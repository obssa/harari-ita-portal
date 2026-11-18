<?php

namespace App\Domain\Content\Support;

/**
 * Turns the content pack's simple body blocks into the editor's TipTap document (ADR-0045): a string is a paragraph,
 * ["h" => text] a heading, ["ul" => [items]] a bullet list. Markdown links in the text ([text](url)) become links.
 * A body that is already a document is kept as it is.
 */
final class PackBody
{
    /**
     * @param  array<mixed>  $blocks
     * @return array<string, mixed>
     */
    public static function build(array $blocks): array
    {
        if (($blocks['type'] ?? null) === 'doc') {
            return $blocks;
        }

        $content = [];
        foreach ($blocks as $block) {
            if (is_string($block) && trim($block) !== '') {
                $content[] = ['type' => 'paragraph', 'content' => self::inline($block)];
            } elseif (is_array($block) && is_string($block['h'] ?? null)) {
                $content[] = ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => self::inline($block['h'])];
            } elseif (is_array($block) && is_array($block['ul'] ?? null)) {
                $items = [];
                foreach ($block['ul'] as $item) {
                    if (is_string($item) && trim($item) !== '') {
                        $items[] = ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => self::inline($item)]]];
                    }
                }
                if ($items !== []) {
                    $content[] = ['type' => 'bulletList', 'content' => $items];
                }
            }
        }

        return ['type' => 'doc', 'content' => $content];
    }

    /**
     * Text nodes, with [text](url) as links.
     *
     * @return list<array<string, mixed>>
     */
    public static function inline(string $text): array
    {
        $nodes = [];
        $offset = 0;
        preg_match_all('/\[([^\]]+)\]\(((?:https?:\/\/|\/)[^)\s]+)\)/u', $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
        foreach ($matches as $match) {
            [$whole, $start] = $match[0];
            if ($start > $offset) {
                $nodes[] = ['type' => 'text', 'text' => substr($text, $offset, $start - $offset)];
            }
            $nodes[] = ['type' => 'text', 'text' => $match[1][0], 'marks' => [['type' => 'link', 'attrs' => ['href' => $match[2][0]]]]];
            $offset = $start + strlen($whole);
        }
        if ($offset < strlen($text)) {
            $nodes[] = ['type' => 'text', 'text' => substr($text, $offset)];
        }

        return $nodes;
    }
}
