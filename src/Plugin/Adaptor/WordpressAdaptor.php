<?php

namespace Polyglot\Plugin\Adapter;

class WordpressAdaptor {

    const WP_UNIQUE_KEY = "polyglot-plugin";

    public function addActions()
    {
        add_action('plugins_loaded', array($this, 'onPluginsLoaded'));
    }

    public function onPluginsLoaded()
    {
        if (is_admin()) {
            $this->loadPluginTextdomain();
        }
    }

    protected function loadPluginTextdomain()
    {
        load_plugin_textdomain(self::WP_UNIQUE_KEY, false, $this->getPluginLocalePath());
    }

    protected function getPluginLocalePath()
    {
        return basename(dirname(__FILE__ )) . DIRECTORY_SEPARATOR . 'locale';
    }

}
