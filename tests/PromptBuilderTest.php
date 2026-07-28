<?php

namespace dokuwiki\plugin\llmautotranslate;

use PHPUnit\Framework\TestCase;

class PromptBuilderTest extends TestCase {
    public function testPlaceholdersAreSubstituted(): void {
        $builder = new PromptBuilder();
        $result = $builder->build('From {{source_lang}} to {{target_lang}}.', 'EN', 'DE');

        $this->assertSame('From EN to DE.', $result);
    }

    public function testEmptyGlossaryYieldsEmptyString(): void {
        $builder = new PromptBuilder();
        $result = $builder->build('Glossary: [{{glossary}}]', 'EN', 'DE', []);

        $this->assertSame('Glossary: []', $result);
    }

    public function testNonEmptyGlossaryRendersTermLines(): void {
        $builder = new PromptBuilder();
        $glossary = [
            ['source' => 'Hello', 'target' => 'Hallo'],
            ['source' => 'World', 'target' => 'Welt'],
        ];

        $result = $builder->build('{{glossary}}', 'EN', 'DE', $glossary);

        $expected = "Use these fixed term translations:\n"
            . '- "Hello" -> "Hallo"' . "\n"
            . '- "World" -> "Welt"' . "\n";

        $this->assertSame($expected, $result);
    }

    public function testTemplateWithNoPlaceholdersIsReturnedUnchanged(): void {
        $builder = new PromptBuilder();
        $result = $builder->build('Just a plain instruction with no placeholders.', 'EN', 'DE');

        $this->assertSame('Just a plain instruction with no placeholders.', $result);
    }

    public function testUnicodeGlossaryContentIsPreserved(): void {
        $builder = new PromptBuilder();
        $glossary = [
            ['source' => 'straße', 'target' => 'street'],
            ['source' => '日本語', 'target' => 'Japanese'],
        ];

        $result = $builder->build('{{glossary}}', 'EN', 'DE', $glossary);

        $expected = "Use these fixed term translations:\n"
            . '- "straße" -> "street"' . "\n"
            . '- "日本語" -> "Japanese"' . "\n";

        $this->assertSame($expected, $result);
    }
}
