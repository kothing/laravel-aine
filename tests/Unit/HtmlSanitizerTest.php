<?php

namespace Tests\Unit;

use App\Aine\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_script_tags_are_removed_with_their_content(): void
    {
        $html = '<p>Hello</p><script>alert("xss")</script>';
        $result = HtmlSanitizer::sanitize($html);

        $this->assertStringNotContainsString('script', $result);
        $this->assertStringNotContainsString('alert', $result);
        $this->assertStringContainsString('<p>Hello</p>', $result);
    }

    public function test_event_handler_attributes_are_stripped(): void
    {
        $html = '<img src="https://example.com/a.jpg" onerror="alert(1)">'
            .'<p onclick="steal()">text</p>';

        $result = HtmlSanitizer::sanitize($html);

        $this->assertStringNotContainsString('onerror', $result);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringContainsString('src="https://example.com/a.jpg"', $result);
        $this->assertStringContainsString('<p>text</p>', $result);
    }

    public function test_javascript_urls_are_removed(): void
    {
        $html = '<a href="javascript:alert(1)">click</a>';

        $result = HtmlSanitizer::sanitize($html);

        $this->assertStringNotContainsString('javascript:', $result);
        $this->assertStringContainsString('click', $result);
    }

    public function test_safe_urls_are_kept(): void
    {
        $html = '<a href="https://example.com" target="_blank">link</a>';

        $result = HtmlSanitizer::sanitize($html);

        $this->assertStringContainsString('href="https://example.com"', $result);
    }

    public function test_style_is_reduced_to_safe_properties(): void
    {
        $html = '<p style="text-align:center;position:fixed;background:url(javascript:evil)">x</p>';

        $result = HtmlSanitizer::sanitize($html);

        $this->assertStringContainsString('text-align: center', $result);
        $this->assertStringNotContainsString('position', $result);
        $this->assertStringNotContainsString('url(', $result);
    }

    public function test_iframe_is_restricted_to_whitelisted_providers(): void
    {
        $youtube = '<iframe src="https://www.youtube.com/embed/abc123"></iframe>';
        $evil = '<iframe src="https://evil.example/phish"></iframe>';

        $this->assertStringContainsString('youtube.com', HtmlSanitizer::sanitize($youtube));
        $this->assertStringNotContainsString('iframe', HtmlSanitizer::sanitize($evil));
    }

    public function test_non_whitelisted_tags_are_unwrapped_keeping_text(): void
    {
        $html = '<p>keep <marquee>visible</marquee> text</p>';

        $result = HtmlSanitizer::sanitize($html);

        $this->assertStringNotContainsString('marquee', $result);
        $this->assertStringContainsString('visible', $result);
    }

    public function test_form_tags_are_dropped(): void
    {
        $html = '<form action="https://evil.example"><input name="x"></form><p>ok</p>';

        $result = HtmlSanitizer::sanitize($html);

        $this->assertStringNotContainsString('form', $result);
        $this->assertStringNotContainsString('input', $result);
        $this->assertStringContainsString('<p>ok</p>', $result);
    }

    public function test_null_and_empty_input_are_returned_as_is(): void
    {
        $this->assertSame('', HtmlSanitizer::sanitize(''));
        $this->assertNull(HtmlSanitizer::sanitize(null));
    }

    public function test_valid_table_markup_is_preserved(): void
    {
        $html = '<table><tr><td>a</td><td>b</td></tr></table>';

        $result = HtmlSanitizer::sanitize($html);

        $this->assertStringContainsString('<table>', $result);
        $this->assertStringContainsString('<td>a</td>', $result);
    }

    public function test_sanitizer_is_idempotent(): void
    {
        $html = '<p>Hello <strong>world</strong></p>';

        $once = HtmlSanitizer::sanitize($html);
        $twice = HtmlSanitizer::sanitize($once);

        $this->assertSame($once, $twice);
    }
}
