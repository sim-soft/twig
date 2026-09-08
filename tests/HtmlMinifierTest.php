<?php

declare(strict_types=1);

namespace Simsoft\Twig\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Simsoft\Twig\Twig;

class HtmlMinifierTest extends TestCase
{
    // =========================================================================
    // Raw-text elements are preserved verbatim
    // =========================================================================

    /**
     * @return array<string, array{string}>
     */
    public static function rawTextProvider(): array
    {
        return [
            'pre indentation' => ["<pre>\nline1\n    indented\n</pre>"],
            'textarea content' => ["<textarea>\n  user typed\n  text\n</textarea>"],
            'style rules' => ["<style>\n.a {  color: red; }\n</style>"],
            'script body' => ["<script>\nvar a = 1;\n\nvar b = 2;\n</script>"],
            'script with attributes' => ['<script type="text/javascript">a  b</script>'],
            'uppercase tag' => ["<SCRIPT>\n a  b\n</SCRIPT>"],
            'pre wrapping code' => ["<pre><code>a  b\n  c</code></pre>"],
        ];
    }

    #[Test]
    #[DataProvider('rawTextProvider')]
    public function it_preserves_raw_text_elements_verbatim(string $html): void
    {
        $this->assertSame($html, Twig::minify($html));
    }

    #[Test]
    public function it_does_not_treat_arrow_in_script_as_comment_end(): void
    {
        $html = '<script>var x = "<!-- hi -->"; var y = 1;</script>';

        $this->assertSame($html, Twig::minify($html));
    }

    #[Test]
    public function it_preserves_javascript_containing_comment_syntax(): void
    {
        $html = "<script>\nif (a --> b) {}\n</script>";

        $this->assertSame($html, Twig::minify($html));
    }

    #[Test]
    public function it_preserves_less_than_inside_script(): void
    {
        $html = '<div><script>if (a<b) {}</script></div>';

        $this->assertSame($html, Twig::minify($html));
    }

    // =========================================================================
    // Attribute values are never rewritten
    // =========================================================================

    #[Test]
    public function it_does_not_rewrite_attribute_values(): void
    {
        $html = '<a title="a >   < b">x</a>';

        $this->assertSame($html, Twig::minify($html));
    }

    #[Test]
    public function it_preserves_newlines_inside_attribute_values(): void
    {
        $html = "<div data-x=\"a\nb\">y</div>";

        $this->assertSame($html, Twig::minify($html));
    }

    #[Test]
    public function it_collapses_whitespace_between_attributes(): void
    {
        $html = "<a   href=\"x\"\n   class=\"y\" >t</a>";

        $this->assertSame('<a href="x" class="y">t</a>', Twig::minify($html));
    }

    #[Test]
    public function it_handles_unquoted_attribute_values(): void
    {
        $this->assertSame('<div class=foo id=bar>x</div>', Twig::minify('<div  class=foo   id=bar >x</div>'));
    }

    #[Test]
    public function it_handles_single_quoted_attributes_containing_angle_brackets(): void
    {
        $html = "<a title='c<d'>x</a>";

        $this->assertSame($html, Twig::minify($html));
    }

    // =========================================================================
    // Inline whitespace is collapsed, not removed
    // =========================================================================

    #[Test]
    public function it_keeps_a_space_between_inline_elements(): void
    {
        $html = "<span>Hello</span>\n<span>World</span>";

        $this->assertSame('<span>Hello</span> <span>World</span>', Twig::minify($html));
    }

    #[Test]
    public function it_keeps_spacing_around_inline_formatting(): void
    {
        $html = '<b>bold</b> and <i>italic</i>';

        $this->assertSame($html, Twig::minify($html));
    }

    #[Test]
    public function it_removes_whitespace_between_block_elements(): void
    {
        $html = "<div>\n  <p>Hello</p>\n</div>";

        $this->assertSame('<div><p>Hello</p></div>', Twig::minify($html));
    }

    #[Test]
    public function it_collapses_multiple_spaces_in_text(): void
    {
        $this->assertSame('<p>Hello World</p>', Twig::minify('<p>Hello     World</p>'));
    }

    #[Test]
    public function it_collapses_to_single_space_when_comment_separates_words(): void
    {
        $this->assertSame('<p>a b</p>', Twig::minify('<p>a <!-- c --> b</p>'));
    }

    // =========================================================================
    // Comments
    // =========================================================================

    #[Test]
    public function it_removes_regular_comments(): void
    {
        $html = "<!-- nav -->\n<nav>Menu</nav>\n<!-- end -->";

        $this->assertSame('<nav>Menu</nav>', Twig::minify($html));
    }

    #[Test]
    public function it_preserves_conditional_comments(): void
    {
        $html = '<!--[if IE]><link href="ie.css"><![endif]--><div>C</div>';

        $this->assertSame($html, Twig::minify($html));
    }

    #[Test]
    public function it_preserves_conditional_comments_regardless_of_case(): void
    {
        $html = '<!--[If IE]>x<![endif]--><p>ok</p>';

        $this->assertSame($html, Twig::minify($html));
    }

    // =========================================================================
    // Literal angle brackets in text
    // =========================================================================

    #[Test]
    public function it_preserves_greater_than_in_text(): void
    {
        $this->assertSame('<p>if a > b then</p>', Twig::minify('<p>if a > b then</p>'));
    }

    #[Test]
    public function it_preserves_less_than_in_text(): void
    {
        $this->assertSame('<p>if a < b then</p>', Twig::minify('<p>if a < b then</p>'));
    }

    #[Test]
    public function it_preserves_html_entities(): void
    {
        $html = '<p>a&nbsp;&nbsp;b</p>';

        $this->assertSame($html, Twig::minify($html));
    }

    // =========================================================================
    // Document-level markup
    // =========================================================================

    #[Test]
    public function it_preserves_doctype(): void
    {
        $html = "<!DOCTYPE html>\n<html>\n<body>x</body>\n</html>";

        $this->assertSame('<!DOCTYPE html><html><body>x</body></html>', Twig::minify($html));
    }

    #[Test]
    public function it_preserves_cdata_sections(): void
    {
        $html = '<p>a</p><![CDATA[ x  y ]]><p>b</p>';

        $this->assertSame($html, Twig::minify($html));
    }

    // =========================================================================
    // Malformed input
    // =========================================================================

    #[Test]
    public function it_handles_unclosed_raw_element(): void
    {
        $html = '<div><script>var a=1;';

        $this->assertSame($html, Twig::minify($html));
    }

    #[Test]
    public function it_handles_unterminated_comment(): void
    {
        $this->assertSame('<p>x</p>', Twig::minify('<p>x</p><!-- oops'));
    }

    #[Test]
    public function it_handles_unterminated_tag(): void
    {
        $html = '<p>ok</p><div class="x';

        $this->assertSame($html, Twig::minify($html));
    }

    #[Test]
    public function it_does_not_drop_content_on_invalid_utf8(): void
    {
        $html = "<div>\n\n  " . chr(0xB1) . chr(0x1F) . '  </div>';

        $result = Twig::minify($html);

        $this->assertStringContainsString(chr(0xB1), $result);
        $this->assertStringContainsString(chr(0x1F), $result);
    }

    // =========================================================================
    // Basic behaviour
    // =========================================================================

    #[Test]
    public function it_returns_empty_string_for_whitespace_only_input(): void
    {
        $this->assertSame('', Twig::minify("   \n  "));
    }

    #[Test]
    public function it_trims_bare_text(): void
    {
        $this->assertSame('hello world', Twig::minify('  hello   world  '));
    }

    #[Test]
    public function it_is_idempotent(): void
    {
        $html = "<div>\n  <p>Hello  World</p>\n  <span>a</span>\n  <span>b</span>\n</div>";

        $once = Twig::minify($html);

        $this->assertSame($once, Twig::minify($once));
    }
}
