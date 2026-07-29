<?php

$lang['api_key'] = 'DeepL API key (only used when the backend is set to DeepL)';
$lang['api'] = 'DeepL API tier to use, free or pro (only used when the backend is set to DeepL)';
$lang['api_log_errors'] = 'Log API errors';
$lang['mode'] = 'Default operation mode of the plugin';
$lang['show_button'] = 'Show button for forced-translations';
$lang['push_langs'] = 'Space separated list of languages for push-translation (ISO codes)';
$lang['glossary_ns'] = 'Namespace for the definitions of glossaries';
$lang['blacklist_regex'] = 'Blacklist-Regex: All page names and namespaces matching this regex won\'t be translated';
$lang['direct_regex'] = 'Direct-Regex: All page names and namespaces matching this regex will be translated in the direct mode';
$lang['editor_regex'] = 'Editor-Regex: All page names and namespaces matching this regex will be translated in the editor mode';
$lang['ignored_expressions'] = 'Expressions that won\'t be translated, seperated by \':\'';
$lang['default_lang_in_ns'] = 'The default language is in a namespace (should normally not be the case)';
$lang['keep_relative'] = 'Do not rewrite relative links when translating';
$lang['bidirectional'] = 'Bidirectional translation: translate between all configured languages in both directions, using the most recently edited existing page as the source (not only the default language). The translate button then pushes the current page to all other configured languages.';

$lang['backend'] = 'Translation backend to use';
$lang['llm_api_url'] = 'LLM API URL (OpenAI-compatible chat/completions endpoint)';
$lang['llm_api_key'] = 'LLM API key';
$lang['llm_model'] = 'LLM model name';
$lang['llm_prompt'] = 'LLM translation prompt (placeholders: {{source_lang}}, {{target_lang}}, {{glossary}})';
