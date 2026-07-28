<?php

namespace dokuwiki\plugin\llmautotranslate;

use PHPUnit\Framework\TestCase;

class TranslationValidatorTest extends TestCase {
    public function testCleanOutputReturnedUnchanged(): void {
        $validator = new TranslationValidator();
        $input = 'Hello <ignore>[[start]]</ignore> world';
        $output = 'Hallo <ignore>[[start]]</ignore> Welt';

        $this->assertSame($output, $validator->validate($input, $output));
    }

    public function testCodeFencesAreStripped(): void {
        $validator = new TranslationValidator();
        $input = 'Hello <ignore>[[start]]</ignore> world';
        $output = "```\nHallo <ignore>[[start]]</ignore> Welt\n```";

        $this->assertSame('Hallo <ignore>[[start]]</ignore> Welt', $validator->validate($input, $output));
    }

    public function testKnownPreambleLineIsStripped(): void {
        $validator = new TranslationValidator();
        $input = 'Hello <ignore>[[start]]</ignore> world';
        $output = "Here is the translation:\nHallo <ignore>[[start]]</ignore> Welt";

        $this->assertSame('Hallo <ignore>[[start]]</ignore> Welt', $validator->validate($input, $output));
    }

    public function testThrowsWhenIgnoreBlockDropped(): void {
        $validator = new TranslationValidator();
        $input = 'Hello <ignore>[[start]]</ignore> world <ignore>[[end]]</ignore>';
        $output = 'Hallo Welt <ignore>[[end]]</ignore>';

        $this->expectException(TranslationValidationException::class);
        $validator->validate($input, $output);
    }

    public function testThrowsWhenIgnoreBlockAdded(): void {
        $validator = new TranslationValidator();
        $input = 'Hello <ignore>[[start]]</ignore> world';
        $output = 'Hallo <ignore>[[start]]</ignore> <ignore>[[extra]]</ignore> Welt';

        $this->expectException(TranslationValidationException::class);
        $validator->validate($input, $output);
    }

    public function testThrowsWhenIgnoreBlockAltered(): void {
        $validator = new TranslationValidator();
        $input = 'Hello <ignore>[[start]]</ignore> world';
        $output = 'Hallo <ignore>[[changed]]</ignore> Welt';

        $this->expectException(TranslationValidationException::class);
        $validator->validate($input, $output);
    }

    public function testPassesWhenIgnoreBlocksAreIdenticalButReordered(): void {
        // Blocks are sorted before comparison, so reordering is intentionally
        // not treated as tampering for Slice 2.
        $validator = new TranslationValidator();
        $input = '<ignore>[[a]]</ignore> Hello <ignore>[[b]]</ignore> world';
        $output = '<ignore>[[b]]</ignore> Hallo <ignore>[[a]]</ignore> Welt';

        $this->assertSame($output, $validator->validate($input, $output));
    }

    public function testThrowsWhenLengthRatioOutOfBounds(): void {
        $validator = new TranslationValidator();
        $input = str_repeat('word ', 20);
        $output = 'short';

        $this->expectException(TranslationValidationException::class);
        $validator->validate($input, $output);
    }

    public function testNestedIgnoreBlocksArePreserved(): void {
        $validator = new TranslationValidator();
        $input = "Hello <ignore><ignore>''</ignore></ignore> world";
        $output = "Hallo <ignore><ignore>''</ignore></ignore> Welt";

        $this->assertSame($output, $validator->validate($input, $output));
    }

    public function testCodeFenceWithLanguageTagIsStripped(): void {
        $validator = new TranslationValidator();
        $input = 'Hello <ignore>[[start]]</ignore> world';
        $output = "```xml\nHallo <ignore>[[start]]</ignore> Welt\n```";

        $this->assertSame('Hallo <ignore>[[start]]</ignore> Welt', $validator->validate($input, $output));
    }

    public function testOutputWithNoCodeFenceReturnedUnchangedAsideFromTrim(): void {
        $validator = new TranslationValidator();
        $input = 'Hello <ignore>[[start]]</ignore> world';
        $output = "  Hallo <ignore>[[start]]</ignore> Welt  \n";

        $this->assertSame('Hallo <ignore>[[start]]</ignore> Welt', $validator->validate($input, $output));
    }

    public function testLeadingTranslationColonLineIsStrippedCaseInsensitive(): void {
        $validator = new TranslationValidator();
        $input = 'Hello world';
        $output = "TRANSLATION:\nHallo Welt";

        $this->assertSame('Hallo Welt', $validator->validate($input, $output));
    }

    public function testSentenceContainingWordTranslationIsNotStripped(): void {
        $validator = new TranslationValidator();
        $input = 'Hello world';
        $output = "This translation is accurate.\nHallo Welt";

        $this->assertSame("This translation is accurate.\nHallo Welt", $validator->validate($input, $output));
    }

    public function testThrowsWhenIgnoreBlockAlteredHasCode1(): void {
        $validator = new TranslationValidator();
        $input = 'Hello <ignore>[[start]]</ignore> world';
        $output = 'Hallo <ignore>[[changed]]</ignore> Welt';

        $this->expectException(TranslationValidationException::class);
        $this->expectExceptionCode(1);
        $validator->validate($input, $output);
    }

    public function testThrowsWhenIgnoreBlockDroppedHasCode1(): void {
        $validator = new TranslationValidator();
        $input = 'Hello <ignore>[[start]]</ignore> world <ignore>[[end]]</ignore>';
        $output = 'Hallo Welt <ignore>[[end]]</ignore>';

        $this->expectException(TranslationValidationException::class);
        $this->expectExceptionCode(1);
        $validator->validate($input, $output);
    }

    public function testThrowsWhenIgnoreBlockAddedHasCode1(): void {
        $validator = new TranslationValidator();
        $input = 'Hello <ignore>[[start]]</ignore> world';
        $output = 'Hallo <ignore>[[start]]</ignore> <ignore>[[extra]]</ignore> Welt';

        $this->expectException(TranslationValidationException::class);
        $this->expectExceptionCode(1);
        $validator->validate($input, $output);
    }

    public function testThrowsWhenOutputTooLongHasCode2(): void {
        $validator = new TranslationValidator();
        $input = 'short input';
        $output = str_repeat('this output is much much too long ', 20);

        $this->expectException(TranslationValidationException::class);
        $this->expectExceptionCode(2);
        $validator->validate($input, $output);
    }

    public function testThrowsWhenOutputTooShortHasCode2(): void {
        $validator = new TranslationValidator();
        $input = str_repeat('word ', 20);
        $output = 'short';

        $this->expectException(TranslationValidationException::class);
        $this->expectExceptionCode(2);
        $validator->validate($input, $output);
    }

    public function testEmptyInputDoesNotTriggerRatioCheck(): void {
        $validator = new TranslationValidator();
        $input = '';
        $output = 'This output is far longer than the empty input but should not fail the ratio check.';

        $this->assertSame($output, $validator->validate($input, $output));
    }
}
