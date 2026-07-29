# DokuWiki LLM Autotranslate Plugin
This plugin automatically creates translations based on the [Translation plugin](https://www.dokuwiki.org/plugin:translation) page structure. It supports two translation backends, selected via the `backend` setting:

- **llm** (default): any OpenAI-compatible chat/completions endpoint (OpenAI, Azure OpenAI, self-hosted models, etc.)
- **deepl**: the [DeepL](https://deepl.com) API (the plugin's original backend)

Both backends share the same hooks, modes (`direct`, `editor`), push-translate button, and page/namespace structure. Wiki syntax (links, media, tables, headings, code blocks, etc.) is protected from the translation call the same way for both backends.

## LLM backend configuration
- `llm_api_url` - the chat/completions endpoint URL, e.g. `https://api.openai.com/v1/chat/completions`.
- `llm_api_key` - the API key for that endpoint.
- `llm_model` - the model name, e.g. `gpt-4o-mini`.
- `llm_prompt` - the system prompt sent with every translation request. It supports three placeholders:
  - `{{source_lang}}` - the source language code.
  - `{{target_lang}}` - the target language code.
  - `{{glossary}}` - filled in with any fixed term translations defined for the language pair.

The model's response is validated before use: any preamble, postamble, or markdown code-fence wrapping is stripped, every protected `<ignore>...</ignore>` region from the request must reappear unchanged in the response, and the output length is checked for plausibility. If validation fails, the translation is rejected and no page is saved.

## Glossaries
The glossary namespace and term-table pages work the same for both backends. With the `llm` backend, the term pairs from the glossary page are injected into the prompt via `{{glossary}}` instead of being pushed to DeepL's glossary API.

## Bidirectional translation
By default a page is only translated from the configured default language into the other
languages. Enable the `bidirectional` setting to translate between all configured languages in
both directions: when a page is opened or created in any language namespace and does not yet
exist, it is translated from whichever sibling version already exists (the most recently edited
one, if several exist). With this setting on, the "Translate page" button also pushes the current
page to every other configured language, from any language namespace. When the setting is off,
behavior is unchanged.

## Usage and configuration
For further usage and configuration instructions please visit the [DokuWiki plugin page](https://www.dokuwiki.org/plugin:llmautotranslate) of this plugin.
