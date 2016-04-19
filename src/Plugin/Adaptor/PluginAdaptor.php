<?php

namespace Polyglot\Plugin\Adaptor;

use Strata\Strata;
use Polyglot\I18n\Db\Query;
use Polyglot\I18n\Polyglot;

/**
 * Registers into Wordpress actions and filters and
 * handles the plugin's loading.
 */
class PluginAdaptor {

    /**
     * Registers the plugin and saved the context in which
     * the plugin will run.
     */
    public function addFilters()
    {
        register_activation_hook(Strata::config('runtime.polyglot.loaderPath'), function(){
            $query = new Query();
            $query->createTable();
        });



        $polyglot = new Polyglot();
        // We need to rehook into setCurrentLocaleByContext because we need to
        // try again once global post objects are loaded.
        add_action(is_admin() ? 'admin_init' : 'wp', array($polyglot, "setCurrentLocaleByContext"), 3);

        is_admin() ?
            AdminAdaptor::addFilters() :
            FrontAdaptor::addFilters();

        CommonAdaptor::addFilters();
        WidgetAdaptor::addFilters();
    }

    /*
     * @param  string $loaderPath The plugin path
     */
    public function __construct($loaderPath)
    {
        $app = Strata::app();
        $app->setConfig('runtime.polyglot.loaderPath', $loaderPath);
    }
}
