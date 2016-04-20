<?php

namespace Polyglot\Plugin\Adaptor;

use Strata\Strata;
use Polyglot\Admin\Router;

class AdminAdaptor {

    public static function addFilters()
    {
        $adaptor = new self();

        add_action('admin_menu', array($adaptor, 'adminMenu'));
        add_action('admin_enqueue_scripts', array($adaptor, 'enqueueScripts'));

        add_action('plugins_loaded', array($adaptor, 'loadPluginTextdomain'));

        $configuration = Strata::i18n()->getConfiguration();
        $router = new Router();
        $router->contextualize($adaptor);

        add_action('wp_ajax_polyglot_ajax', array($router, "autoroute"));

        foreach($configuration->getEnabledTaxonomies() as $tax) {
            add_action ($tax . '_edit_form_fields', array($router, "addTaxonomyLocaleSelect"));
            add_filter('manage_edit-'.$tax.'_columns', array($router, "addLocalizationColumn"));
            add_action('manage_'.$tax.'_custom_column', array($router, "renderTaxonomyLocalizationColumn"), 20, 5 );
        }

        foreach($configuration->getEnabledPostTypes() as $postType) {
            add_action('add_meta_boxes_'.$postType, array($router, "addMetaBox"), 20, 2);
            add_filter('manage_'.$postType.'_posts_columns', array($router, "addLocalizationColumn"));
            add_action('manage_'.$postType.'_posts_custom_column', array($router, "renderLocalizationColumn"), 10, 2 );
        }
    }

    /**
     * Loads the plugin text domain.
     */
    public function loadPluginTextdomain()
    {
        load_plugin_textdomain('polyglot-plugin', false, $this->getPluginLocalePath());
    }


    /**
     * Registers the option menu entry.
     */
    public function adminMenu()
    {
        $router = new Router();
        $router->contextualize($this);
        add_options_page('Localization', 'Localization', 'manage_options', 'polyglot-plugin', array($router, 'autoroute'));
    }

    protected function getAdminJsPath()
    {
        $paths = array('src', 'Admin', 'assets', 'polyglot.js');
        return  plugin_dir_url(Strata::config('runtime.polyglot.loaderPath')) . implode(DIRECTORY_SEPARATOR, $paths);
    }

    protected function getAdminCSSPath()
    {
        $paths = array('src', 'Admin', 'assets', 'polyglot.css');
        return  plugin_dir_url(Strata::config('runtime.polyglot.loaderPath')) . implode(DIRECTORY_SEPARATOR, $paths);
    }

    /**
     * Loads up the scripts and styles required to display our popup.
     */
    public function requireJqueryUi()
    {
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
    }

    /**
     * @see admin_enqueue_scripts
     */
    public function enqueueScripts($suffix)
    {
        wp_enqueue_script('polyglot_admin_js', $this->getAdminJsPath());
        wp_enqueue_style('polyglot_admin_css', $this->getAdminCSSPath());
        $this->requireJqueryUi();
    }

    protected function getPluginLocalePath()
    {
        return dirname(Strata::config('runtime.polyglot.loaderPath')) . DIRECTORY_SEPARATOR . 'locale';
    }
}
