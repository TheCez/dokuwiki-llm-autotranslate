<?php

$meta['api_key'] = array('string');
$meta['api'] = array('multichoice', '_choices' => array('free', 'pro'));
$meta['api_log_errors'] = array('onoff');
$meta['mode'] = array('multichoice', '_choices' => array('direct', 'editor'));
$meta['show_button'] = array('onoff');
$meta['push_langs'] = array('string');
$meta['glossary_ns'] = array('string');
$meta['blacklist_regex'] = array('regex');
$meta['direct_regex'] = array('regex');
$meta['editor_regex'] = array('regex');
$meta['ignored_expressions'] = array('string');
$meta['default_lang_in_ns'] = array('onoff');
$meta['keep_relative'] = array('onoff');
$meta['bidirectional'] = array('onoff');

$meta['backend'] = array('multichoice', '_choices' => array('llm', 'deepl'));
$meta['llm_api_url'] = array('string');
$meta['llm_api_key'] = array('string');
$meta['llm_model'] = array('string');
$meta['llm_timeout'] = array('numeric');
// DokuWiki core config manager has no multiline type; a single-line string is acceptable for now.
$meta['llm_prompt'] = array('string');
