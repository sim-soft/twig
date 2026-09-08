<?php

declare(strict_types=1);

namespace Simsoft\Twig;

/**
 * HtmlMinifier class
 *
 * Tag-aware HTML minifier.
 *
 * The input is scanned into tokens before any whitespace is touched, so the
 * minifier always knows whether it is inside a tag, a comment, a raw-text
 * element, or ordinary text. This avoids the corruption a naive regex pass
 * causes inside `<script>`, `<pre>`, and attribute values.
 *
 * Guarantees:
 *  - Content of `<pre>`, `<textarea>`, `<script>`, and `<style>` is preserved
 *    byte for byte.
 *  - Attribute values are never rewritten.
 *  - Whitespace separating inline content is collapsed to a single space, never
 *    removed, so words are not joined together.
 *  - Conditional comments are preserved regardless of casing.
 *
 * @internal Use {@see Twig::minify()} as the public entry point.
 */
final class HtmlMinifier
{
    /** @var string[] Elements whose content is preserved verbatim */
    private const RAW_TEXT_ELEMENTS = [
        'pre',
        'script',
        'style',
        'textarea',
    ];

    /**
     * Block-level elements. Whitespace directly adjacent to these has no
     * rendered effect, so it can be removed outright rather than collapsed.
     *
     * @var string[]
     */
    private const BLOCK_ELEMENTS = [
        'address', 'article', 'aside', 'base', 'blockquote', 'body', 'canvas',
        'caption', 'col', 'colgroup', 'dd', 'details', 'dialog', 'div', 'dl',
        'dt', 'fieldset', 'figcaption', 'figure', 'footer', 'form', 'h1', 'h2',
        'h3', 'h4', 'h5', 'h6', 'head', 'header', 'hgroup', 'hr', 'html', 'li',
        'link', 'main', 'menu', 'meta', 'nav', 'noscript', 'ol', 'optgroup',
        'option', 'p', 'pre', 'script', 'section', 'style', 'summary', 'table',
        'tbody', 'td', 'tfoot', 'th', 'thead', 'title', 'tr', 'ul',
    ];

    /**
     * Minify an HTML string.
     *
     * @param string $html Raw HTML content.
     * @return string Minified HTML.
     */
    public static function minify(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $tokens = self::tokenize($html);

        // Drop non-conditional comments before neighbour analysis, so that
        // whitespace either side of a removed comment collapses correctly.
        $tokens = array_values(
            array_filter($tokens, static fn (array $token): bool => $token['type'] !== 'comment'),
        );

        // Removing a comment can leave two text nodes adjacent. Merge them so
        // the pair collapses to a single space rather than one space each.
        $tokens = self::mergeAdjacentText($tokens);

        $output = '';

        foreach ($tokens as $index => $token) {
            $output .= match ($token['type']) {
                'text' => self::collapseText(
                    $token['value'],
                    $tokens[$index - 1] ?? null,
                    $tokens[$index + 1] ?? null,
                ),
                'tag' => self::collapseTag($token['value']),
                default => $token['value'],
            };
        }

        return trim($output);
    }

    /**
     * Split the document into tokens.
     *
     * @param string $html
     * @return array<int, array{type: string, value: string, name: string|null}>
     */
    private static function tokenize(string $html): array
    {
        $tokens = [];
        $offset = 0;
        $length = strlen($html);

        while ($offset < $length) {
            $position = strpos($html, '<', $offset);

            if ($position === false) {
                $tokens[] = self::token('text', substr($html, $offset));
                break;
            }

            if ($position > $offset) {
                $tokens[] = self::token('text', substr($html, $offset, $position - $offset));
            }

            // Comments — conditional ones are kept as opaque markup.
            if (substr_compare($html, '<!--', $position, 4) === 0) {
                $end = strpos($html, '-->', $position + 4);
                $value = $end === false
                    ? substr($html, $position)
                    : substr($html, $position, $end + 3 - $position);

                $tokens[] = self::token(
                    self::isConditionalComment($value) ? 'markup' : 'comment',
                    $value,
                );

                $offset = $end === false ? $length : $end + 3;
                continue;
            }

            // Doctype, CDATA, downlevel-revealed conditionals, processing
            // instructions — preserved as-is.
            if (
                substr_compare($html, '<!', $position, 2) === 0
                || substr_compare($html, '<?', $position, 2) === 0
            ) {
                $end = self::findTagEnd($html, $position);
                $value = $end === null
                    ? substr($html, $position)
                    : substr($html, $position, $end + 1 - $position);

                $tokens[] = self::token('markup', $value);
                $offset = $end === null ? $length : $end + 1;
                continue;
            }

            // A '<' not starting a tag name is literal text (e.g. "a < b").
            if (preg_match('/^<\/?[a-zA-Z]/', substr($html, $position, 3)) !== 1) {
                $tokens[] = self::token('text', '<');
                $offset = $position + 1;
                continue;
            }

            $end = self::findTagEnd($html, $position);

            if ($end === null) {
                $tokens[] = self::token('text', substr($html, $position));
                break;
            }

            $tag = substr($html, $position, $end + 1 - $position);
            $name = self::tagName($tag);
            $offset = $end + 1;

            // Raw-text elements: swallow everything up to the closing tag so
            // that no whitespace or comment rule is ever applied to it.
            if (
                $name !== null
                && !str_starts_with($tag, '</')
                && !str_ends_with($tag, '/>')
                && in_array($name, self::RAW_TEXT_ELEMENTS, true)
            ) {
                $closePosition = stripos($html, '</' . $name, $offset);

                if ($closePosition !== false) {
                    $closeEnd = self::findTagEnd($html, $closePosition);

                    if ($closeEnd !== null) {
                        $tokens[] = self::token(
                            'raw',
                            substr($html, $position, $closeEnd + 1 - $position),
                            $name,
                        );
                        $offset = $closeEnd + 1;
                        continue;
                    }
                }

                // Unclosed raw element — preserve the remainder untouched.
                $tokens[] = self::token('raw', substr($html, $position), $name);
                break;
            }

            $tokens[] = self::token('tag', $tag, $name);
        }

        return $tokens;
    }

    /**
     * Merge consecutive text tokens into one.
     *
     * @param array<int, array{type: string, value: string, name: string|null}> $tokens
     * @return array<int, array{type: string, value: string, name: string|null}>
     */
    private static function mergeAdjacentText(array $tokens): array
    {
        $merged = [];

        foreach ($tokens as $token) {
            $last = array_key_last($merged);

            if ($last !== null && $token['type'] === 'text' && $merged[$last]['type'] === 'text') {
                $merged[$last]['value'] .= $token['value'];
                continue;
            }

            $merged[] = $token;
        }

        return $merged;
    }

    /**
     * @param string $type
     * @param string $value
     * @param string|null $name
     * @return array{type: string, value: string, name: string|null}
     */
    private static function token(string $type, string $value, ?string $name = null): array
    {
        return ['type' => $type, 'value' => $value, 'name' => $name];
    }

    /**
     * Locate the '>' closing a tag, ignoring any inside quoted attributes.
     *
     * @param string $html
     * @param int $start Offset of the opening '<'.
     * @return int|null Offset of the closing '>', or null when unterminated.
     */
    private static function findTagEnd(string $html, int $start): ?int
    {
        $length = strlen($html);
        $quote = null;

        for ($i = $start + 1; $i < $length; $i++) {
            $character = $html[$i];

            if ($quote !== null) {
                if ($character === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
                continue;
            }

            if ($character === '>') {
                return $i;
            }
        }

        return null;
    }

    /**
     * Extract the lowercased element name from a tag.
     *
     * @param string $tag
     * @return string|null
     */
    private static function tagName(string $tag): ?string
    {
        if (preg_match('/^<\/?\s*([a-zA-Z][a-zA-Z0-9:-]*)/', $tag, $matches) !== 1) {
            return null;
        }

        return strtolower($matches[1]);
    }

    /**
     * Whether a comment is a conditional comment that must be preserved.
     *
     * @param string $comment
     * @return bool
     */
    private static function isConditionalComment(string $comment): bool
    {
        return preg_match('/^<!--\s*\[\s*if\b/i', $comment) === 1;
    }

    /**
     * Collapse whitespace between attributes without touching quoted values.
     *
     * @param string $tag
     * @return string
     */
    private static function collapseTag(string $tag): string
    {
        $output = '';
        $length = strlen($tag);
        $quote = null;
        $pendingSpace = false;

        for ($i = 0; $i < $length; $i++) {
            $character = $tag[$i];

            if ($quote !== null) {
                $output .= $character;

                if ($character === $quote) {
                    $quote = null;
                }
                continue;
            }

            if (ctype_space($character)) {
                $pendingSpace = true;
                continue;
            }

            // Never leave a dangling space before the closing bracket.
            if ($character === '>') {
                $pendingSpace = false;
            }

            if ($pendingSpace) {
                $output .= ' ';
                $pendingSpace = false;
            }

            if ($character === '"' || $character === "'") {
                $quote = $character;
            }

            $output .= $character;
        }

        return $output;
    }

    /**
     * Collapse a text node relative to its neighbours.
     *
     * Whitespace-only text is dropped when it touches a block-level boundary
     * (where it has no rendered effect) and otherwise reduced to a single
     * space, which preserves word separation between inline elements.
     *
     * @param string $text
     * @param array{type: string, value: string, name: string|null}|null $previous
     * @param array{type: string, value: string, name: string|null}|null $next
     * @return string
     */
    private static function collapseText(string $text, ?array $previous, ?array $next): string
    {
        $collapsed = preg_replace('/\s+/', ' ', $text);

        if ($collapsed === null) {
            // Malformed encoding: leave the text untouched rather than risk
            // silently dropping content.
            return $text;
        }

        $previousIsBlock = self::isBlockBoundary($previous);
        $nextIsBlock = self::isBlockBoundary($next);

        if (trim($collapsed) === '') {
            return $previousIsBlock || $nextIsBlock ? '' : $collapsed;
        }

        if ($previousIsBlock) {
            $collapsed = ltrim($collapsed);
        }

        if ($nextIsBlock) {
            $collapsed = rtrim($collapsed);
        }

        return $collapsed;
    }

    /**
     * Whether a neighbouring token forms a block-level boundary.
     *
     * @param array{type: string, value: string, name: string|null}|null $token
     * @return bool
     */
    private static function isBlockBoundary(?array $token): bool
    {
        // Start or end of the document.
        if ($token === null) {
            return true;
        }

        if ($token['type'] === 'markup') {
            return !self::isConditionalComment($token['value']);
        }

        if ($token['type'] !== 'tag' && $token['type'] !== 'raw') {
            return false;
        }

        return $token['name'] !== null && in_array($token['name'], self::BLOCK_ELEMENTS, true);
    }
}
