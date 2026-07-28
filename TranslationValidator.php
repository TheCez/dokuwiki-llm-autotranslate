<?php

namespace dokuwiki\plugin\llmautotranslate;

class TranslationValidator {
    private float $minRatio;
    private float $maxRatio;

    public function __construct(float $minRatio = 0.25, float $maxRatio = 4.0) {
        $this->minRatio = $minRatio;
        $this->maxRatio = $maxRatio;
    }

    public function validate(string $inputWithIgnores, string $modelOutput): string {
        $out = trim($modelOutput);

        $out = $this->stripCodeFence($out);
        $out = $this->stripPreamble($out);

        $inputIgnores = $this->extractIgnoreBlocks($inputWithIgnores);
        $outputIgnores = $this->extractIgnoreBlocks($out);

        sort($inputIgnores);
        sort($outputIgnores);

        if ($inputIgnores !== $outputIgnores) {
            throw new TranslationValidationException('Protected <ignore> content was altered, dropped, or added', 1);
        }

        if (mb_strlen($inputWithIgnores) > 0) {
            $ratio = mb_strlen($out) / mb_strlen($inputWithIgnores);
            if ($ratio < $this->minRatio || $ratio > $this->maxRatio) {
                throw new TranslationValidationException('Translated length is implausible', 2);
            }
        }

        return $out;
    }

    private function stripCodeFence(string $out): string {
        $lines = explode("\n", $out);
        if (count($lines) < 2) return $out;

        $first = trim($lines[0]);
        $last = trim($lines[count($lines) - 1]);

        if (preg_match('/^`{3,}\w*$/', $first) && preg_match('/^`{3,}$/', $last)) {
            array_shift($lines);
            array_pop($lines);
            return trim(implode("\n", $lines));
        }

        return $out;
    }

    private function stripPreamble(string $out): string {
        $lines = explode("\n", $out);
        $first = strtolower(trim($lines[0]));

        if ($first === 'here is the translation:' || $first === 'translation:') {
            array_shift($lines);
            return trim(implode("\n", $lines));
        }

        return $out;
    }

    private function extractIgnoreBlocks(string $text): array {
        preg_match_all('/<ignore>[\s\S]*?<\/ignore>/', $text, $matches);
        return $matches[0];
    }
}
