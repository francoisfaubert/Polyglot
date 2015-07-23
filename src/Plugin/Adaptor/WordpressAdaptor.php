<?php

namespace Polyglot\Plugin\Adaptor;

use Polyglot\Admin\AdminController;
use Polyglot\Admin\AdminAjaxController;

class WordpressAdaptor {

    const WP_UNIQUE_KEY = "polyglot-plugin";

    public $loaderPath;

    public function register($loaderPath)
    {
        $this->loaderPath = $loaderPath;
        $this->addRegistrationHooks();

        $this->addCallbacks();
    }

    public function load()
    {
        if (is_admin()) {
            $this->loadPluginTextdomain();
        }
    }

    public function activate()
    {

    }

    public function deactivate()
    {

    }

    public function adminMenu()
    {
        $ctrl = new AdminController();
        $ctrl->contextualize($this);

        add_options_page('Polyglot', 'Polyglot', 'manage_options', self::WP_UNIQUE_KEY, array($ctrl, 'index'));
    }

    public function adminInit()
    {

    }

    public function enqueueScripts($suffix)
    {
        if ($suffix === "settings_page_" . self::WP_UNIQUE_KEY) {
            wp_enqueue_script('polyglot_admin_js', $this->getAdminJsPath());
            wp_enqueue_style('polyglot_admin_css', $this->getAdminCSSPath());
            $this->requireJqueryUi();
        }
    }

    protected function addCallbacks()
    {
        is_admin() ?
            $this->addAdminCallbacks() :
            $this->addWebsiteCallbacks();

        $this->addGlobalCallbacks();
    }

    protected function addAdminCallbacks()
    {
        add_action('admin_menu', array($this, 'adminMenu'));
        add_action('admin_init', array($this, 'adminInit'));

        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));

        $ctrl = new AdminAjaxController();
        $ctrl->contextualize($this);
        add_action('wp_ajax_polyglot_ajax', array($ctrl, "autoroute"));
    }

    protected function addWebsiteCallbacks()
    {

    }

    protected function addGlobalCallbacks()
    {
        add_action('plugins_loaded', array($this, 'load'));
    }

    protected function addRegistrationHooks()
    {
        register_activation_hook($this->loaderPath, array($this, 'activate'));
        register_deactivation_hook($this->loaderPath, array($this, 'deactivate'));
    }

    protected function loadPluginTextdomain()
    {
        load_plugin_textdomain(self::WP_UNIQUE_KEY, false, $this->getPluginLocalePath());
    }

    public function requireJqueryUi()
    {
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-draggable');
        wp_enqueue_script('jquery-ui-droppable');
    }

    protected function getPluginLocalePath()
    {
        return dirname($this->loaderPath) . DIRECTORY_SEPARATOR . 'locale';
    }

    public function getAdminViewPath()
    {
        $paths = array(dirname($this->loaderPath), 'src', 'Admin', 'View');
        return  implode(DIRECTORY_SEPARATOR, $paths);
    }

    protected function getAdminJsPath()
    {
        $paths = array('src', 'Admin', 'assets', 'polyglot.js');
        return  plugin_dir_url($this->loaderPath) . implode(DIRECTORY_SEPARATOR, $paths);
    }

    protected function getAdminCSSPath()
    {
        $paths = array('src', 'Admin', 'assets', 'polyglot.css');
        return  plugin_dir_url($this->loaderPath) . implode(DIRECTORY_SEPARATOR, $paths);
    }


}
