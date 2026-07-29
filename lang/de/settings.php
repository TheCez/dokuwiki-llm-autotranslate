<?php

$lang['api_key'] = 'DeepL-API-Schlüssel (wird nur verwendet, wenn als Backend DeepL eingestellt ist)';
$lang['api'] = 'Zu verwendende DeepL-API-Stufe, free oder pro (wird nur verwendet, wenn als Backend DeepL eingestellt ist)';
$lang['api_log_errors'] = 'API-Fehler loggen';
$lang['mode'] = 'Standard-Modus des Plugins';
$lang['show_button'] = 'Button für erzwungene Übersetzungen anzeigen';
$lang['push_langs'] = 'Liste der Sprachen (ISO Codes) für Push-Übersetzungen, mittels Leerzeichen separiert';
$lang['glossary_ns'] = 'Namespace für die Definitionen von Glossaren';
$lang['blacklist_regex'] = 'Blacklist-Regex: Alle Seitennamen und Namespaces, auf die dieser Regex matched, werden nicht übersetzt';
$lang['direct_regex'] = 'Direct-Regex: Alle Seitennamen und Namespaces, auf die dieser Regex matched, werden im Direct-Modus übersetzt';
$lang['editor_regex'] = 'Editor-Regex: Alle Seitennamen und Namespaces, auf die dieser Regex matched, werden im Editor-Modus übersetzt';
$lang['ignored_expressions'] = 'Ausdrücke, welche nicht übersetzt werden sollen, separiert von \':\'';
$lang['default_lang_in_ns'] = 'Die Standardsprache befindet sich in einem Namespace (sollte normalerweise nicht der Fall sein)';
$lang['keep_relative'] = 'Relative Links beim Übersetzen nicht umschreiben';
$lang['bidirectional'] = 'Bidirektionale Übersetzung: zwischen allen konfigurierten Sprachen in beide Richtungen übersetzen, wobei die zuletzt bearbeitete vorhandene Seite als Quelle dient (nicht nur die Standardsprache). Der Übersetzungs-Button überträgt die aktuelle Seite dann in alle anderen konfigurierten Sprachen.';
$lang['sync_translations'] = 'Übersetzungen synchron halten: Wird eine Seite gespeichert, wird sie in die anderen Sprachen neu übersetzt, und wird eine Seite geöffnet, deren Quelle zuletzt neuer bearbeitet wurde, wird sie neu übersetzt. Die zuletzt bearbeitete Sprache gilt als maßgebliche Quelle (ihre Übersetzung überschreibt die anderen).';

$lang['backend'] = 'Zu verwendendes Übersetzungs-Backend';
$lang['llm_api_url'] = 'LLM-API-URL (OpenAI-kompatibler chat/completions-Endpunkt)';
$lang['llm_api_key'] = 'LLM-API-Schlüssel';
$lang['llm_model'] = 'LLM-Modellname';
$lang['llm_timeout'] = 'LLM-Anfrage-Timeout in Sekunden';
$lang['llm_prompt'] = 'LLM-Übersetzungs-Prompt (Platzhalter: {{source_lang}}, {{target_lang}}, {{glossary}})';

