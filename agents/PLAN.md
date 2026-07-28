# Implementation Plan - DeepL to LLM Autotranslate

Read `../CLAUDE.md` first. This file is the working plan: the vertical slices, their acceptance
criteria, the technical design details, and the testing strategy. The orchestrator keeps the
slice checkboxes and notes up to date as work completes.

## Guiding constraints

- Same DokuWiki-facing workflow as today. Hooks, modes (`editor` / `direct`), the translate
  button, push translation, and the language-namespace page structure all stay identical.
- DeepL stays as a selectable backend. A `backend` setting selects `llm` (default) or `deepl`.
- Plugin base name is renamed `deeplautotranslate` -> `llmautotranslate`.
- Glossary is reimplemented for the LLM backend by injecting term pairs into the prompt.
- New logic goes in pure, DokuWiki-free classes so the `tester` subagent can run it standalone.
- One vertical slice at a time. Each slice ends in a working, shippable plugin.

## Config: final shape

Existing settings that stay: `mode`, `show_button`, `push_langs`, `glossary_ns`,
`blacklist_regex`, `direct_regex`, `editor_regex`, `ignored_expressions`, `default_lang_in_ns`,
`keep_relative`, `api_log_errors`.

Renamed / repurposed:
- `backend` - multichoice `['llm','deepl']`, default `llm`. Chooses the translation backend.
- DeepL legacy settings kept under the `deepl` backend: `api_key` (DeepL key), `api` (`free`/`pro`).

New LLM connection settings (the brief's "3 things" plus the model an OpenAI-compatible
`/chat/completions` call requires):
- `llm_api_url` - string. Default `https://api.openai.com/v1/chat/completions`.
- `llm_api_key` - string. Default empty.
- `llm_model` - string. Default `gpt-4o-mini` (placeholder; admin overrides per provider).
- `llm_prompt` - string (long). Default = the translation prompt below.

### Default translation prompt (`$conf['llm_prompt']`)

The code substitutes `{{source_lang}}`, `{{target_lang}}`, and `{{glossary}}` before sending.
The wiki text is sent as the user message, never substituted into the prompt (avoids injection
and escaping problems).

```
You are a professional translator for DokuWiki wiki markup.
Translate the user's text from {{source_lang}} to {{target_lang}}.

Rules:
- Translate only natural-language text.
- Any content wrapped in <ignore>...</ignore> must be reproduced EXACTLY as given,
  including the <ignore> tags themselves. Do not translate, reorder, or alter it.
- Never translate or modify URLs, code, markup symbols, page IDs, or media IDs.
- Preserve all whitespace, line breaks, and the overall structure exactly.
- Do not add, remove, summarize, or comment on any content.
- Output ONLY the translated text. No preamble, no explanation, no markdown code fences.
{{glossary}}
```

`{{glossary}}`, when a glossary applies, expands to a block like:
```
- Use these fixed term translations: "source term" -> "target term"; ...
```
and expands to an empty string otherwise.

## Technical design of the LLM path

New classes (pure PHP, namespace `dokuwiki\plugin\llmautotranslate`, no DokuWiki globals -
pass everything in via constructor/params):

- `PromptBuilder`
  - `build(string $promptTemplate, string $sourceLang, string $targetLang, array $glossary): string`
  - Substitutes placeholders; renders or empties the glossary block.
- `LlmClient`
  - Given URL, key, model, system prompt, and user text, shapes the `/chat/completions` JSON
    request and parses `choices[0].message.content` from the response. HTTP transport is
    injected (an interface) so tests use a fake and `action.php` passes a `DokuHTTPClient`
    adapter. Maps HTTP error codes to typed exceptions.
- `TranslationValidator`
  - `validate(string $inputWithIgnores, string $modelOutput): string` returns the cleaned
    output or throws `TranslationValidationException`.
  - Steps: strip surrounding markdown code fences; strip known preamble/postamble lines;
    extract the multiset of `<ignore>...</ignore>` block contents from input and output and
    assert they match exactly; length sanity check (reject if output length is outside a
    configurable ratio of input length); reject leftover chat artifacts.

`action.php` keeps `patch_links()`, `insert_ignore_tags()`, `remove_ignore_tags()` unchanged and
gains a `llm_translate()` sibling to `deepl_translate()`. A small dispatcher (`translate()`)
picks the backend from config and calls the right one. All three entry paths call the dispatcher.

## Vertical slices

### Slice 1 - Rename and backend config scaffold
Rename base name and lay down the config surface. No new translation behavior yet.

- Rename `deeplautotranslate` -> `llmautotranslate` everywhere: `plugin.info.txt` base, class
  names (`action_plugin_...`, `remote_plugin_...`), PHP namespace, `plugin_load` calls, the
  logger instance name, and the glossary state file (`deepl-glossaries.json` ->
  `llm-glossaries.json`, with read-fallback to the old name for migration). The installable
  plugin directory must equal the base name `llmautotranslate`.
- Add config: `backend`, `llm_api_url`, `llm_api_key`, `llm_model`, `llm_prompt` in
  `conf/default.php` and `conf/metadata.php`. Keep `api_key`/`api` for DeepL.
- Add settings labels + the prompt default to `lang/en` and `lang/de`.
- Behavior unchanged: with `backend=deepl` the existing DeepL flow still works.

Acceptance: plugin loads under the new name; admin sees the new settings; a DeepL translation
still succeeds (backend=deepl); no references to the old base name remain except the migration
fallback.

### Slice 2 - LLM translation engine, editor mode end-to-end
Deliver working LLM translation in the default `editor` mode.

- Implement `PromptBuilder`, `LlmClient`, `TranslationValidator` (pure classes) with unit tests.
- Add `llm_translate()` in `action.php` reusing `patch_links` + `insert_ignore_tags` +
  `remove_ignore_tags`, and a `translate()` dispatcher selecting backend.
- Wire `autotrans_editor()` through the dispatcher.

Acceptance: with `backend=llm` configured against an OpenAI-compatible endpoint, creating a page
in a language namespace fills the editor with a correct translation; `<ignore>` content is
preserved; validation rejects a response that adds commentary or drops ignore blocks. Unit tests
for the three new classes pass.

### Slice 3 - Direct mode and push translation on LLM
Confirm the shared choke point covers the other two entry paths.

- Route `autotrans_direct()` and `push_translate()` through the dispatcher.
- Verify remote (`remote.php`) push path.

Acceptance: with `backend=llm`, direct mode auto-saves a translation on view, and the translate
button push-translates into every configured `push_langs` namespace; all preserve structure.

### Slice 4 - Glossary via prompt injection (LLM backend)
Reimplement the glossary feature for the LLM backend.

- Read the glossary term table for the active source/target pair and pass the pairs into
  `PromptBuilder` so they render in `{{glossary}}`. Bypass DeepL glossary REST calls when
  backend=llm. Keep the glossary namespace UI and page templates.
- Under backend=deepl, the existing DeepL glossary REST flow stays.

Acceptance: with backend=llm and a defined glossary, the injected terms are honored in output;
no DeepL glossary REST calls happen; glossary namespace pages still initialize correctly.

### Slice 5 - Hardening, i18n, docs
Finish the edges.

- All new user-facing strings (errors for LLM failure, validation failure, bad URL/key) in
  `lang/en` and `lang/de`.
- LLM error handling maps HTTP + validation failures to clear messages; honor `api_log_errors`
  for LLM failures too.
- Update `readme.md`, `plugin.info.txt` description, and any docs to reflect LLM support and the
  dual backend.
- Final full end-to-end pass: both backends, all three modes, glossary on/off.

Acceptance: clean i18n, actionable error messages, updated docs, green tests, and a manual E2E
pass recorded in this file's notes.

## Testing strategy

- **Unit (tester subagent, standalone PHP + PHPUnit):** `PromptBuilder`, `LlmClient` (with a
  fake HTTP transport), and `TranslationValidator`. These have no DokuWiki dependencies. Cover:
  placeholder substitution, glossary block on/off, request JSON shape, response parsing, HTTP
  error mapping, and every validation rule (preamble/fence stripping, ignore-block multiset
  match, length bounds, artifact rejection). Add a `_test/` dir and a dev-only `composer.json`
  pinning PHPUnit.
- **Integration / E2E (orchestrator-verified):** DokuWiki-coupled methods (`patch_links`, the
  ignore round-trip, the three entry paths, glossary namespace) are exercised in a real
  DokuWiki instance against a live or mock OpenAI-compatible endpoint, driven the way an admin
  and an editor would. Reproduce before fixing any bug.
- Every slice must be green on its own tests before the next slice starts.

## Orchestration workflow (recap)

- This window (Opus) orchestrates only; never spawn another Opus orchestrator.
- Coding -> `coder` subagent on Sonnet (`subagent_type: "general-purpose"`, `model: "sonnet"`).
- Testing -> `tester` subagent on Sonnet, same launch.
- Give each subagent one precise, self-contained brief. The orchestrator reviews, integrates,
  and verifies each slice end-to-end before moving on.

## Progress

- [x] Slice 1 - Rename and backend config scaffold (done, verified)
- [x] Slice 2 - LLM translation engine, editor mode (done; unit-verified)
- [ ] Slice 3 - Direct mode and push translation on LLM
- [ ] Slice 4 - Glossary via prompt injection
- [ ] Slice 5 - Hardening, i18n, docs

### Notes
(Record decisions, deviations, and E2E verification results here as slices complete.)

**Environment:** PHP was not installed on this machine. Installed PHP 8.5.8 via `scoop install php`
(shim at `~/scoop/shims/php`). Composer 2.10.2 via `scoop install composer`. The scoop PHP shipped
with no `php.ini`; created `~/scoop/apps/php/current/php.ini` from `php.ini-production` enabling
`openssl`, `mbstring`, `curl` (needed for Composer + the suite). Run tests:
`cd <worktree> && composer install && php vendor/bin/phpunit`.

**Slice 2 (done):** Coder (Sonnet) added pure classes `PromptBuilder`, `LlmClient` (HTTP via
injected `HttpTransport`), `TranslationValidator` (code-fence/preamble strip + `<ignore>` multiset
equality + length-ratio guard), `LlmException`, `TranslationValidationException`, and thin
`DokuHttpTransport`. Wired `action.php`: `translate()` backend dispatcher + `llm_translate()`
reusing `patch_links`/`insert_ignore_tags`/`remove_ignore_tags`; `autotrans_editor()` routed through
it. `deepl_translate`, `autotrans_direct`, `push_translate` untouched (Slice 3). Added en/de error
strings, `composer.json`, `phpunit.xml`, `.gitignore` (/vendor, /composer.lock). Tester (Sonnet)
hardened coverage to 32 tests / 50 assertions (all green), no implementation bugs found.
Orchestrator verified: all PHP lint-clean; `action.php` diff reviewed; full suite green.

**Verification limit:** full in-DokuWiki E2E (editor fills with a live LLM translation) needs a
running wiki + a real OpenAI-compatible endpoint, which this environment lacks. Slice 2 is verified
by the unit suite (pure logic) + static review of the DokuWiki-coupled wiring + lint. A manual
in-wiki E2E remains as a checkout step when an endpoint is available.

**Follow-up (Slice 5):** `TranslationValidator::extractIgnoreBlocks` uses a non-greedy regex that
only matches one level of `<ignore>` nesting. `insert_ignore_tags` does emit doubly-nested blocks
(e.g. for `''`, `//`, `**`, `__`, `\\`). Harmless for the equality check (input and output parse
identically, confirmed by test), but tampering hidden in the nested "gap" could slip through.
Consider a nesting-aware check or normalization in Slice 5.

**Slice 1 (done):** Coder (Sonnet) renamed base `deeplautotranslate` -> `llmautotranslate` across
action.php, remote.php, MenuItem.php, plugin.info.txt, readme.md (class names, namespace,
plugin_load args, logger instance, doc URLs). Glossary state file moved to `llm-glossaries.json`
via two helpers: `glossary_state_file()` (writes) and `glossary_state_file_read()` (reads, with
fallback to legacy `deepl-glossaries.json` until first write migrates). Added config: `backend`
(llm|deepl, default llm), `llm_api_url`, `llm_api_key`, `llm_model` (gpt-4o-mini), `llm_prompt`
(NOWDOC default with the {{source_lang}}/{{target_lang}}/{{glossary}} placeholders) in
default.php + metadata.php, plus en/de settings labels. `llm_prompt` uses the plain `string`
meta type (core config manager has no multiline type) - revisit in Slice 5 if a textarea is
wanted.
Verification (orchestrator): `php -l` clean on all 7 edited PHP files; executing default.php +
metadata.php yields the expected `$conf`/`$meta` keys, all three prompt placeholders present,
backend choices `llm,deepl`; grep confirms no `deeplautotranslate` remains outside the legacy
glossary-migration fallback and the plan docs. No behavior change; DeepL path untouched. No tester
subagent (no new runtime logic to unit-test at this slice).
