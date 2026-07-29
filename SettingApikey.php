<?php

namespace dokuwiki\plugin\llmautotranslate;

use dokuwiki\plugin\config\core\Setting\SettingPassword;

/**
 * Config-manager setting for API keys.
 *
 * Behaves exactly like the core password setting - the stored key is never rendered into the page,
 * and an empty field on save keeps the existing value - but when a key is already stored it shows a
 * masked placeholder so admins can see that a value IS set without being able to read it.
 *
 * Only used by the config manager (referenced from conf/metadata.php); the plugin runtime reads the
 * value via getConf() as before.
 */
class SettingApikey extends SettingPassword {

    /**
     * @param \admin_plugin_config $plugin object of config plugin
     * @param bool $echo true if triggered by preview
     * @return string[] array(label_html, input_html)
     */
    public function html(\admin_plugin_config $plugin, $echo = false) {
        [$label, $input] = parent::html($plugin, $echo);

        // The parent always renders an empty field. When a key is stored, add a masked placeholder
        // as a purely decorative "a value is set" hint: the real key is still never sent to the
        // browser, and because the field value stays empty, an empty submit keeps the stored key.
        if (!$this->isProtected() && !empty($this->local)) {
            $placeholder = 'placeholder="&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;&#x2022;"';
            $input = str_replace('type="password"', 'type="password" ' . $placeholder, $input);
        }

        return [$label, $input];
    }
}
