# CLAUDE.md - Project Guide

This file is ingested at the start of every agent session. Read it fully before doing anything.

## What this project is

A DokuWiki plugin that automatically translates wiki pages. It currently uses the **DeepL API**. We are extending it to translate using **OpenAI-compatible LLM APIs** instead, while keeping the exact same DokuWiki-facing workflow (same hooks, same modes, same button, same page/namespace structure).

The end goal: an admin configures an LLM endpoint (API URL, API key, model, and a translation prompt with a sensible default), and every place the plugin used to call DeepL now calls the LLM, gets the translation back, and runs it through the existing syntax-protection framework plus new validation that guarantees the model added nothing extra.

DeepL is **not removed** - it becomes a selectable backend. A `backend` setting chooses `llm` (default, the new direction) or `deepl` (legacy).

## Architecture map

The plugin is a single DokuWiki action plugin. Key files:

- `action.php` - the whole engine. Registers hooks and contains the translation logic.
- `remote.php` - XML-RPC/remote entry point (`pushTranslation`) that calls into `action.php`.
- `MenuItem.php` - the "Translate page" toolbar button.
- `conf/default.php`, `conf/metadata.php` - config defaults and schema.
- `lang/en/`, `lang/de/` - UI strings (`lang.php`) and settings labels (`settings.php`).
- `plugin.info.txt` - plugin manifest (base name lives here).

### The single choke point

Every translation path funnels through one private method in `action.php`:

```
deepl_translate($text, $target_lang, $org_ns)
  -> patch_links()          # rewrite internal wiki/media links to target-lang namespace
  -> insert_ignore_tags()   # wrap non-translatable DokuWiki syntax in <ignore>...</ignore>
  -> POST to DeepL          # tag_handling=xml, ignore_tags=ignore
  -> remove_ignore_tags()   # strip the <ignore> wrappers, restore syntax
```

Three entry paths all call it:
- `autotrans_editor()` - fills the editor template with a translation (mode `editor`).
- `autotrans_direct()` - translates and saves immediately on page view (mode `direct`).
- `push_translate()` - pushes a translation into other language namespaces (button / remote).

**Because all three share this choke point, replacing the DeepL POST with an LLM call automatically covers all three modes.** That is the core of the plan.

### The `<ignore>` framework is reusable

`insert_ignore_tags()` / `remove_ignore_tags()` already protect DokuWiki syntax (tables, headings, links, media, code, smileys, ignored expressions) by wrapping it in `<ignore>` tags. This is the "validation framework already used" the project brief refers to. For the LLM backend we **keep this framework** and instruct the model, via the prompt, to leave `<ignore>...</ignore>` content untouched. Then `remove_ignore_tags()` works unchanged.

## Target design (LLM backend)

1. Build request text exactly as today: `patch_links()` then `insert_ignore_tags()`.
2. Fill prompt placeholders (`{{source_lang}}`, `{{target_lang}}`, `{{glossary}}`) from config + language map.
3. POST to the OpenAI-compatible endpoint (`/chat/completions`): system message = filled prompt, user message = the wiki text, low temperature.
4. Read `choices[0].message.content`.
5. **New validation** (`TranslationValidator`) before `remove_ignore_tags()`:
   - strip/reject LLM preamble, postamble, and markdown code-fence wrapping;
   - assert every `<ignore>` block from the input is present in the output, unchanged (multiset compare) - this guarantees links, media IDs, and code were not altered;
   - length sanity check to catch runaway hallucination;
   - on failure: throw (same error pattern as today), optionally log, never save garbage.
6. `remove_ignore_tags()` as today.

Glossary (LLM backend): keep the glossary namespace UI and term tables, but inject the term pairs into the prompt via `{{glossary}}` instead of calling DeepL's glossary REST API.

New logic (prompt building, response cleaning, ignore-block validation, request/response shaping) lives in **pure PHP classes with no DokuWiki globals**, so it is unit-testable standalone. `action.php` stays a thin caller.

## Strict working rules

### Orchestration model (do not deviate)

- **The current window is the sole orchestrator and is always Opus. Never launch another Opus orchestrator agent.** You (this session) plan, decompose, dispatch, review, integrate, and verify.
- **All coding is done by a `coder` subagent; all testing by a `tester` subagent. Subagents are ALWAYS Sonnet - never Opus.** Only the orchestrator is Opus.
- Subagents never spawn further subagents. They receive one precise, self-contained brief and do the work; the orchestrator reviews and verifies.
- The orchestrator does the reading, cross-file reasoning, and final verification itself; it writes code directly only for trivial glue when spawning a subagent would be pure overhead.

#### How to dispatch a subagent (Herdr-native - do NOT use the Agent tool, wmux, or a2a)

In-process Agent/Task subagents share this one process and have no terminal, so Herdr cannot show
them. Instead, use the **`herdr-subagent` skill** (installed globally) to launch each subagent as
its OWN live Claude Code session in a new Herdr tab:

1. Write the subagent's full, self-contained brief to `agents/briefs/<label>.md`.
2. Run its script:
   `powershell -NoProfile -File C:\Users\ajayc\.claude\skills\herdr-subagent\scripts\run-subagent.ps1 -Brief agents\briefs\<label>.md -Label <label> -Cwd <workdir>`
   It opens a new tab, launches Sonnet with the brief as the launch-time prompt, waits for idle,
   prints the transcript tail, and closes the tab.
3. Then verify the actual file changes / test output directly (not just the transcript), since the
   subagent ran in a separate process.

The skill's SKILL.md documents the guarantees (separate live tab, Sonnet only, prompt-at-launch,
idle-based completion, auto-close).

### Vertical slices (do not deviate)

- Work proceeds one **vertical slice** at a time. Each slice leaves the plugin in a working, shippable state - never a half-wired horizontal layer.
- A slice is not done until it is coded, tested, and verified end-to-end. Only then start the next slice.
- The slice list and acceptance criteria live in `agents/PLAN.md`. Keep it updated as slices complete.

### Git workflow (per slice)

- Never commit slice work directly to `main`. Each slice gets its own branch, and (from Slice 2 on)
  its own **git worktree** (`git worktree add ../wt-slice<N> -b slice-<N>-<slug>`) so the coder
  subagent works in an isolated directory (`-Cwd <worktree>`).
- After a slice is coded, tested, and verified: commit in the worktree, `git push -u origin`, and
  open a PR with `gh pr create --base main` into `TheCez/dokuwiki-llm-autotranslate` (the fork
  `origin`). Then remove the worktree. Only then start the next slice.
- Commit identity: `TheCez <achodankar28@gmail.com>`. No co-author trailers. No em dashes.

### Engineering standards

- Prefer quality, simplicity, robustness, and long-term maintainability over speed.
- For bug fixes, reproduce end-to-end first (as a real DokuWiki admin/editor would), then fix.
- Keep the DokuWiki-coupled code thin; keep new logic pure and tested.
- Never use the em dash; use a plain dash.
- Do not hand-edit auto-generated files.

## Where to look next

- `agents/PLAN.md` - the vertical-slice implementation plan, acceptance criteria, and testing strategy.
