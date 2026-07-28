<?php

namespace dokuwiki\plugin\llmautotranslate;

use PHPUnit\Framework\TestCase;

/**
 * Hits a real OpenAI-compatible LLM endpoint. Skipped unless LLM_API_URL and
 * LLM_API_KEY are set (via .env, copied from .env.example). Not part of the
 * regular offline test suite.
 */
class LiveLlmIntegrationTest extends TestCase {
    private string $url;
    private string $key;
    private string $model;

    protected function setUp(): void {
        $this->url = (string) getenv('LLM_API_URL');
        $this->key = (string) getenv('LLM_API_KEY');
        $model = getenv('LLM_MODEL');

        if (empty($this->url) || empty($this->key)) {
            $this->markTestSkipped('Set LLM_API_URL/LLM_API_KEY/LLM_MODEL in .env to run live LLM tests');
        }

        $this->model = empty($model) ? 'gpt-4o-mini' : $model;
    }

    public function testTranslatesWhileKeepingIgnoredBlocksIntact(): void {
        $conf = [];
        include __DIR__ . '/../conf/default.php';

        $prompt = (new PromptBuilder())->build($conf['llm_prompt'], 'German', 'English', []);

        $input = "<ignore>======</ignore> Willkommen <ignore>======</ignore>\n\nDies ist ein <ignore>[[start</ignore>Testlink<ignore>]]</ignore> im Text.";

        $client = new LlmClient(new CurlHttpTransport(), $this->url, $this->key, $this->model);
        $raw = $client->translate($prompt, $input);

        $out = (new TranslationValidator())->validate($input, $raw);

        $this->assertNotEmpty($out);

        preg_match_all('/<ignore>[\s\S]*?<\/ignore>/', $input, $inputMatches);
        preg_match_all('/<ignore>[\s\S]*?<\/ignore>/', $out, $outputMatches);

        foreach ($inputMatches[0] as $ignoreBlock) {
            $this->assertContains($ignoreBlock, $outputMatches[0], "Ignore block '$ignoreBlock' was not preserved in output");
        }

        $this->assertNotSame($input, $out);
    }
}
