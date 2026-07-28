<?php

$conf['mode'] = 'editor';
$conf['api'] = 'free';
$conf['api_log_errors'] = 0;
$conf['show_button'] = true;
$conf['keep_relative'] = 0;

$conf['backend'] = 'llm';
$conf['llm_api_url'] = 'https://api.openai.com/v1/chat/completions';
$conf['llm_api_key'] = '';
$conf['llm_model'] = 'gpt-4o-mini';
$conf['llm_prompt'] = <<<'PROMPT'
You are a professional translator for DokuWiki wiki markup.
Translate the user's text from {{source_lang}} to {{target_lang}}.

Rules:
- Translate only natural-language text.
- Any content wrapped in <ignore>...</ignore> must be reproduced EXACTLY as given, including the <ignore> tags themselves. Do not translate, reorder, or alter it.
- Never translate or modify URLs, code, markup symbols, page IDs, or media IDs.
- Preserve all whitespace, line breaks, and the overall structure exactly.
- Do not add, remove, summarize, or comment on any content.
- Output ONLY the translated text. No preamble, no explanation, no markdown code fences.
{{glossary}}
PROMPT;
