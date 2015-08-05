<?php

namespace Polyglot\Plugin\Adaptor;

use Strata\Strata;
use Polyglot\Admin\Router;
use Polyglot\Plugin\Db\Query;
use Polyglot\Plugin\Polyglot;
use Polyglot\Plugin\PolyglotRewriter;

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

    public static function activate()
    {
        $query = new Query();
        $query->createTable();
    }

    public static function deactivate()
    {

    }

    public function adminMenu()
    {
        $router = new Router();
        $router->contextualize($this);
        add_options_page('Localization', 'Localization', 'manage_options', self::WP_UNIQUE_KEY, array($router, 'autoroute'));
    }

    public function adminInit()
    {

    }

    public function enqueueScripts($suffix)
    {
        wp_enqueue_script('polyglot_admin_js', $this->getAdminJsPath());
        wp_enqueue_style('polyglot_admin_css', $this->getAdminCSSPath());
        $this->requireJqueryUi();
    }

    protected function addCallbacks()
    {
        is_admin() ?
            $this->addAdminCallbacks() :
            $this->addWebsiteCallbacks();

        $this->addGlobalCallbacks();
    }

    protected function addWebsiteCallbacks()
    {
        add_filter('page_link', array($this, 'pageLink'));
        add_filter('post_link', array($this, 'postLink'));
    }

    protected function addAdminCallbacks()
    {
        add_action('admin_menu', array($this, 'adminMenu'));
        add_action('admin_init', array($this, 'adminInit'));

        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));

        $router = new Router();
        $router->contextualize($this);
        add_action('wp_ajax_polyglot_ajax', array($router, "autoroute"));
        add_filter('add_meta_boxes', array($router, "addMetaBox"), 1000000);

        $polyglot = new Polyglot();
        foreach($polyglot->getEnabledPostTypes() as $type) {
            add_filter('views_edit-' . $type, array($router, "addViewEditLocaleSelect"));
        }
    }

    protected function addGlobalCallbacks()
    {
        add_action('plugins_loaded', array($this, 'load'));

        $rewriter = new PolyglotRewriter();
        $rewriter->registerHooks();
    }

    public function pageLink($url)
    {
        return $this->addCurrentLanguageUrl($url);
    }

    public function postLink($url)
    {
        return $this->addCurrentLanguageUrl($url);
    }

    protected function addCurrentLanguageUrl($url)
    {
        $app = Strata::app();
        $locale = $app->i18n->getCurrentLocale();

        if (!$locale->isDefault()) {
            $homeUrl = get_home_url();
            if (strstr($url, $homeUrl . '/index.php/')) {
                $homeUrl .= '/index.php';
            }
            return str_replace($homeUrl, $homeUrl . "/" . $locale->getUrl(), $url);
        }

        return $url;
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

    public function preGetPosts($query)
    {
        $app = Strata::app();
        $locale = $app->i18n->getCurrentLocale();
        $polyQuery = new Query();
        $ids = array();

        foreach ((array)$polyQuery->findAllIdsOfLocale($locale->getCode()) as $id) {
            $ids[] = $id;
        }

        return $query->set("post__in", $ids);
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
