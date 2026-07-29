<?php

namespace dokuwiki\plugin\llmautotranslate;

use PHPUnit\Framework\TestCase;

class ContentHasherTest extends TestCase {
    public function testCrlfAndLfHashEqual(): void {
        $hasher = new ContentHasher();

        $this->assertSame($hasher->hash("a\r\nb"), $hasher->hash("a\nb"));
    }

    public function testBareCrNormalizedToLf(): void {
        $hasher = new ContentHasher();

        $this->assertSame($hasher->hash("a\rb"), $hasher->hash("a\nb"));
    }

    public function testSurroundingWhitespaceIgnored(): void {
        $hasher = new ContentHasher();

        $this->assertSame($hasher->hash("  hello\n"), $hasher->hash("hello"));
    }

    public function testTrailingNewlineVersusNoneHashEqual(): void {
        $hasher = new ContentHasher();

        $this->assertSame($hasher->hash("hello\n"), $hasher->hash("hello"));
    }

    public function testRealContentChangeIsDetected(): void {
        $hasher = new ContentHasher();

        $this->assertNotSame($hasher->hash("Hello world"), $hasher->hash("Hello there"));
    }

    public function testInteriorWhitespaceIsSignificant(): void {
        $hasher = new ContentHasher();

        $this->assertNotSame($hasher->hash("a b"), $hasher->hash("ab"));
    }

    public function testEmptyAndWhitespaceOnlyHashEqual(): void {
        $hasher = new ContentHasher();

        $this->assertSame($hasher->hash(""), $hasher->hash("   \n  "));
    }

    public function testNormalizeConvertsCrlfAndTrimsTrailingNewline(): void {
        $hasher = new ContentHasher();

        $this->assertSame("a\nb", $hasher->normalize("a\r\nb\r\n"));
    }

    public function testHashMatchesMd5OfAlreadyNormalizedText(): void {
        $hasher = new ContentHasher();

        $this->assertSame(md5("hello"), $hasher->hash("hello"));
    }
}
