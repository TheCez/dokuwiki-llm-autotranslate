<?php

namespace dokuwiki\plugin\llmautotranslate;

class GlossaryParser {
    public function parse(string $wikitext): array {
        preg_match_all('/[ \t]*\|(.*?)\|(.*?)\|/', $wikitext, $matches, PREG_SET_ORDER);

        $pairs = [];
        foreach ($matches as $match) {
            $source = trim($match[1]);
            $target = trim($match[2]);
            if ($source === '' or $target === '') continue;
            $pairs[] = ['source' => $source, 'target' => $target];
        }

        return $pairs;
    }
}
