<?php

namespace Polyglot\Plugin\Adaptor;

use Strata\Strata;
use Polyglot\Admin\Router;
use Polyglot\Plugin\Db\Query;
use Polyglot\Plugin\Polyglot;
use Polyglot\Plugin\PolyglotRewriter;

class WordpressAdaptor {

    /**
     * Plugin activation trigger
     */
    public static function activate()
    {
        $query = new Query();
        $query->createTable();
    }
    /**
     * Plugin deactivation trigger
     */
    public static function deactivate()
    {

    }

    /** @var string Path to the plugin's launcher file. */
    public $loaderPath;

    /** @var Polyglot A local reference to the global polyglot object. */
    private $polyglot;

    /**
     * Registers the plugin and saved the context in which
     * the plugin will run.
     * @param  string $loaderPath The plugin path
     */
    public function register($loaderPath)
    {
        $this->loaderPath = $loaderPath;
        $this->polyglot = Polyglot::instance();

        $this->addRegistrationHooks();
        $this->addCallbacks();
    }

    /**
     * Kicks of the adaptor
     * @see plugins_loaded
     */
    public function load()
    {
        if (is_admin()) {
            $this->loadPluginTextdomain();
        }
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

    /**
     * @see admin_init
     */
    public function adminInit()
    {

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

    /**
     * Registers callbacks based on the current Wordpress context.
     */
    protected function addCallbacks()
    {
        is_admin() ?
            $this->addAdminCallbacks() :
            $this->addWebsiteCallbacks();

        $this->addGlobalCallbacks();
    }

    /**
     * Registers callbacks on the frontend
     */
    protected function addWebsiteCallbacks()
    {

    }

    /**
     * Registers callbacks on the backend
     */
    protected function addAdminCallbacks()
    {
        add_action('admin_menu', array($this, 'adminMenu'));
        add_action('admin_init', array($this, 'adminInit'));

        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));

        $router = new Router();
        $router->contextualize($this);
        add_action('wp_ajax_polyglot_ajax', array($router, "autoroute"));
        add_filter('add_meta_boxes', array($router, "addMetaBox"), 1000000);

        foreach($this->polyglot->getEnabledPostTypes() as $type) {
            add_filter('views_edit-' . $type, array($router, "addViewEditLocaleSelect"));
        }

        foreach($this->polyglot->getEnabledTaxonomies() as $tax) {
            add_action ($tax . '_edit_form_fields', array($router, "addTaxonomyLocaleSelect"));
        }
    }

    /**
     * Registers callbacks required by both the backend and frontend.
     */
    protected function addGlobalCallbacks()
    {
        add_action('plugins_loaded', array($this, 'load'));
        add_action('the_post', array($this->polyglot, 'contextualizeMappingByPost'));

        $rewriter = new PolyglotRewriter();
        $rewriter->registerHooks();
    }

    /**
     * Adds plugin registration hooks
     */
    protected function addRegistrationHooks()
    {
        register_activation_hook($this->loaderPath, array($this, 'activate'));
        register_deactivation_hook($this->loaderPath, array($this, 'deactivate'));
    }


    /**
     * Loads the plugin text domain.
     */
    protected function loadPluginTextdomain()
    {
        load_plugin_textdomain('polyglot-plugin', false, $this->getPluginLocalePath());
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

    // public function preGetPosts($query)
    // {
    //     $app = Strata::app();
    //     $locale = $app->i18n->getCurrentLocale();
    //     $polyQuery = new Query();
    //     $ids = array();

    //     foreach ((array)$polyQuery->findAllIdsOfLocale($locale->getCode()) as $id) {
    //         $ids[] = $id;
    //     }

    //     return $query->set("post__in", $ids);
    // }


    protected function getPluginLocalePath()
    {
        return dirname($this->loaderPath) . DIRECTORY_SEPARATOR . 'locale';
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
