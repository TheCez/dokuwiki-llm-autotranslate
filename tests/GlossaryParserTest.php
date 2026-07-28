<?php

namespace dokuwiki\plugin\llmautotranslate;

use PHPUnit\Framework\TestCase;

class GlossaryParserTest extends TestCase {
    public function testParsesTableRowsExcludingHeader(): void {
        $wikitext = "====== Glossary ======\n"
            . "^ DE ^ EN ^\n"
            . "| Haus | house |\n"
            . "| Baum | tree |\n";

        $parser = new GlossaryParser();
        $result = $parser->parse($wikitext);

        $this->assertSame([
            ['source' => 'Haus', 'target' => 'house'],
            ['source' => 'Baum', 'target' => 'tree'],
        ], $result);
    }

    public function testSkipsRowsWithEmptySourceOrTarget(): void {
        $wikitext = "^ DE ^ EN ^\n"
            . "| Haus | house |\n"
            . "|  | empty source |\n"
            . "| empty target |  |\n"
            . "| Baum | tree |\n";

        $parser = new GlossaryParser();
        $result = $parser->parse($wikitext);

        $this->assertSame([
            ['source' => 'Haus', 'target' => 'house'],
            ['source' => 'Baum', 'target' => 'tree'],
        ], $result);
    }

    public function testPreservesUnicodeTerms(): void {
        $wikitext = "^ DE ^ EN ^\n"
            . "| straße | street |\n"
            . "| 日本語 | Japanese |\n";

        $parser = new GlossaryParser();
        $result = $parser->parse($wikitext);

        $this->assertSame([
            ['source' => 'straße', 'target' => 'street'],
            ['source' => '日本語', 'target' => 'Japanese'],
        ], $result);
    }

    public function testEmptyInputReturnsEmptyArray(): void {
        $parser = new GlossaryParser();

        $this->assertSame([], $parser->parse(''));
    }

    public function testNoTableReturnsEmptyArray(): void {
        $wikitext = "Just some plain text with no table at all.\n";

        $parser = new GlossaryParser();

        $this->assertSame([], $parser->parse($wikitext));
    }

    public function testTrimsHeavySurroundingWhitespace(): void {
        $wikitext = "|    Haus    |    house    |\n"
            . "|\tBaum\t|\ttree\t|\n";

        $parser = new GlossaryParser();
        $result = $parser->parse($wikitext);

        $this->assertSame([
            ['source' => 'Haus', 'target' => 'house'],
            ['source' => 'Baum', 'target' => 'tree'],
        ], $result);
    }

    public function testHeaderRowAloneIsExcluded(): void {
        $wikitext = "^ DE ^ EN ^\n";

        $parser = new GlossaryParser();

        $this->assertSame([], $parser->parse($wikitext));
    }

    public function testMalformedSingleColumnRowIsSkipped(): void {
        $wikitext = "| Haus | house |\n"
            . "| only |\n"
            . "| Baum | tree |\n";

        $parser = new GlossaryParser();
        $result = $parser->parse($wikitext);

        $this->assertSame([
            ['source' => 'Haus', 'target' => 'house'],
            ['source' => 'Baum', 'target' => 'tree'],
        ], $result);
    }

    public function testPreservesInnerSpacesAndPunctuation(): void {
        $wikitext = "| New York | Neu-York, N.Y. |\n"
            . "| e.g. term | i.e. example |\n";

        $parser = new GlossaryParser();
        $result = $parser->parse($wikitext);

        $this->assertSame([
            ['source' => 'New York', 'target' => 'Neu-York, N.Y.'],
            ['source' => 'e.g. term', 'target' => 'i.e. example'],
        ], $result);
    }

    public function testParsesWindowsCrlfLineEndings(): void {
        $wikitext = "^ DE ^ EN ^\r\n"
            . "| Haus | house |\r\n"
            . "| Baum | tree |\r\n";

        $parser = new GlossaryParser();
        $result = $parser->parse($wikitext);

        $this->assertSame([
            ['source' => 'Haus', 'target' => 'house'],
            ['source' => 'Baum', 'target' => 'tree'],
        ], $result);
    }
}
