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

    /**
     * Choose the source language among sibling candidates.
     *
     * Prefers the most recently edited HUMAN-authored page (auto === false); only if there is no
     * human-authored candidate does it fall back to the most recently edited auto-translation.
     * Returns null for an empty list. On ties the first-seen candidate wins, so callers that pass
     * candidates in preference order (e.g. default language first) get a deterministic result.
     *
     * @param array<int,array{lang:string,mtime:int,auto:bool}> $candidates
     * @return string|null
     */
    public function pickSource(array $candidates): ?string {
        $bestHuman = null; $bestHumanMtime = null;
        $bestAny = null;   $bestAnyMtime = null;
        foreach ($candidates as $c) {
            $lang = $c['lang'];
            $mtime = $c['mtime'];
            if ($bestAnyMtime === null or $mtime > $bestAnyMtime) {
                $bestAny = $lang; $bestAnyMtime = $mtime;
            }
            if (empty($c['auto'])) {
                if ($bestHumanMtime === null or $mtime > $bestHumanMtime) {
                    $bestHuman = $lang; $bestHumanMtime = $mtime;
                }
            }
        }
        return $bestHuman !== null ? $bestHuman : $bestAny;
    }
}
