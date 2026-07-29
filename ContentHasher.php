<?php

namespace dokuwiki\plugin\llmautotranslate;

/**
 * Pure helper for content-change detection.
 *
 * Normalizes text the way DokuWiki's save cleaning does (line endings + surrounding whitespace)
 * so a translation hashes the same before and after it is saved, then hashes it. Kept free of
 * DokuWiki globals so it can be unit-tested standalone.
 */
class ContentHasher {
    public function normalize(string $text): string {
        $text = str_replace(array("\r\n", "\r"), "\n", $text);
        return trim($text);
    }

    public function hash(string $text): string {
        return md5($this->normalize($text));
    }
}
