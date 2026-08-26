<?php

namespace Tests\Unit;

use App\Aine\UploadGuard;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UploadGuardTest extends TestCase
{
    public function test_dangerous_extensions_are_rejected(): void
    {
        foreach (['shell.php', 'file.phar', 'page.html', 'script.js', 'x.svg', 'run.sh'] as $name) {
            $this->expectRejected($name, 'plain text');
        }
    }

    public function test_polyglot_image_containing_php_is_rejected(): void
    {
        // Extension looks like a safe image but the content is PHP.
        $this->expectRejected('photo.jpg', '<?php system($_GET["c"]); ?>');
    }

    public function test_embedded_html_in_extensionless_content_is_rejected(): void
    {
        $this->expectRejected('report.csv', '<html><script>alert(1)</script></html>');
    }

    public function test_legitimate_image_is_allowed(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        try {
            UploadGuard::rejectDangerous($file);
            $this->assertTrue(true);
        } catch (ValidationException) {
            $this->fail('A legitimate image must not be rejected.');
        }
    }

    public function test_null_file_is_ignored(): void
    {
        UploadGuard::rejectDangerous(null);
        $this->assertTrue(true);
    }

    private function expectRejected(string $filename, string $content): void
    {
        try {
            UploadGuard::rejectDangerous(UploadedFile::fake()->createWithContent($filename, $content));
            $this->fail("Expected {$filename} to be rejected.");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('file', $e->errors());
        }
    }
}
