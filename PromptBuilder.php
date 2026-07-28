<?php

namespace dokuwiki\plugin\llmautotranslate;

class PromptBuilder {
    public function build(string $template, string $sourceLang, string $targetLang, array $glossary = []): string {
        $glossaryText = '';
        if (!empty($glossary)) {
            $glossaryText = "Use these fixed term translations:\n";
            foreach ($glossary as $pair) {
                $glossaryText .= '- "' . $pair['source'] . '" -> "' . $pair['target'] . '"' . "\n";
            }
        }

        return strtr($template, [
            '{{source_lang}}' => $sourceLang,
            '{{target_lang}}' => $targetLang,
            '{{glossary}}' => $glossaryText,
        ]);
    }
}
