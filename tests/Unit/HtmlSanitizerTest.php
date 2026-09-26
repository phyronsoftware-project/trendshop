<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_it_keeps_editor_formatting_and_removes_executable_markup(): void
    {
        $html = '<h2 onclick="alert(1)">Title</h2><script>alert(1)</script><a href="javascript:alert(1)" target="_blank">Link</a><strong>Safe</strong>';

        $clean = (new HtmlSanitizer)->sanitize($html);

        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        $this->assertStringContainsString('<strong>Safe</strong>', $clean);
        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
    }
}
