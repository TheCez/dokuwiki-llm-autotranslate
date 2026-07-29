<?php

namespace dokuwiki\plugin\llmautotranslate;

/**
 * Pure helper for choosing a translation source language among existing sibling pages.
 *
 * Kept free of DokuWiki globals so it can be unit-tested standalone.
 */
class SourceSelector {
    /**
     * Given a map of candidate language code => modification timestamp, return the language
     * with the greatest timestamp (most recently edited), or null if the map is empty.
     *
     * On ties the first-seen candidate among the highest wins, so callers that build the map
     * in preference order (e.g. default language first) get a deterministic tie-break.
     *
     * @param array<string,int> $langToMtime
     * @return string|null
     */
    public function pickMostRecent(array $langToMtime): ?string {
        $best = null;
        $bestMtime = null;
        foreach ($langToMtime as $lang => $mtime) {
            if ($bestMtime === null or $mtime > $bestMtime) {
                $best = $lang;
                $bestMtime = $mtime;
            }
        }
        return $best;
    }
}
