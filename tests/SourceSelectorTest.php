<?php

namespace dokuwiki\plugin\llmautotranslate;

use PHPUnit\Framework\TestCase;

class SourceSelectorTest extends TestCase {
    public function testPicksMostRecentlyEdited(): void {
        $selector = new SourceSelector();

        $result = $selector->pickMostRecent(['en' => 100, 'de' => 300, 'fr' => 200]);

        $this->assertSame('de', $result);
    }

    public function testSingleCandidateIsReturned(): void {
        $selector = new SourceSelector();

        $result = $selector->pickMostRecent(['fr' => 42]);

        $this->assertSame('fr', $result);
    }

    public function testEmptyMapReturnsNull(): void {
        $selector = new SourceSelector();

        $result = $selector->pickMostRecent([]);

        $this->assertNull($result);
    }

    public function testTieResolvesToFirstSeenKey(): void {
        $selector = new SourceSelector();

        $result = $selector->pickMostRecent(['en' => 500, 'de' => 500]);

        $this->assertSame('en', $result);
    }

    public function testTieResolvesToFirstSeenKeyInReverseInsertionOrder(): void {
        $selector = new SourceSelector();

        $result = $selector->pickMostRecent(['de' => 500, 'en' => 500]);

        $this->assertSame('de', $result);
    }

    public function testHighestWinsEvenWhenItIsTheFirstKey(): void {
        $selector = new SourceSelector();

        $result = $selector->pickMostRecent(['de' => 900, 'en' => 100]);

        $this->assertSame('de', $result);
    }

    public function testZeroTimestampsTieResolvesToFirstSeenKey(): void {
        $selector = new SourceSelector();

        $result = $selector->pickMostRecent(['en' => 0, 'de' => 0]);

        $this->assertSame('en', $result);
    }

    public function testZeroTimestampLosesToHigherTimestamp(): void {
        $selector = new SourceSelector();

        $result = $selector->pickMostRecent(['en' => 0, 'de' => 5]);

        $this->assertSame('de', $result);
    }
}
