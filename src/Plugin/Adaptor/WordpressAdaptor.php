<?php

namespace Polyglot\Plugin\Adaptor;

use Strata\Strata;
use Polyglot\Admin\Router;
use Polyglot\Plugin\Db\Query;
use Polyglot\Plugin\Polyglot;
use Polyglot\Plugin\UrlRewriter;
use Polyglot\Plugin\QueryRewriter;
use Polyglot\Plugin\ContextualSwitcher;

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
        add_action('wp_head', array($this, "appendHeaderHtml"));
    }

    /**
     * Registers callbacks on the backend
     */
    protected function addAdminCallbacks()
    {
        add_action('admin_menu', array($this, 'adminMenu'));
        add_action('admin_init', array($this, 'adminInit'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));

        $configuration = $this->polyglot->getConfiguration();
        $router = new Router();
        $router->contextualize($this);

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
     * Registers callbacks required by both the backend and frontend.
     */
    protected function addGlobalCallbacks()
    {

        $switcher = new ContextualSwitcher();
        $switcher->registerHooks();

        $rewriter = new UrlRewriter();
        $rewriter->registerHooks();

        $querier = new QueryRewriter();
        $querier->registerHooks();

        add_action('plugins_loaded', array($this, 'load'));
        add_action('wp_trash_post', array($this, 'onTrashPost'));
        add_action('delete_term_taxonomy', array($this, 'onTrashTerm'));
        add_action('widgets_init', array("\\Polyglot\\Widget\\LanguageMenu", "register"));
    }

    public function onTrashTerm($termId)
    {
        Polyglot::instance()->query()->unlinkTranslationFor($termId, "Term");
    }

    public function onTrashPost($postId)
    {
        Polyglot::instance()->query()->unlinkTranslationFor($postId, "WP_Post");
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

    /**
     * Appends meta tags with additional localization information and links to localized versions.
     * @return html (it actually echoes it)
     * @see wp_head
     */
    public function appendHeaderHtml()
    {
        $alternates = array();
        $canonicals = array();
        $currentPost = get_post();

        if ($currentPost) {
            foreach ($this->polyglot->getLocales() as $locale) {
                if ($locale->hasPostTranslation($currentPost->ID)) {
                    $translatedPost = $locale->getTranslatedPost($currentPost->ID);
                    if ($translatedPost && $translatedPost->post_status === "publish") {
                       $alternates[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', $locale->getCode(),  get_permalink($translatedPost->ID));
                   }
                } else {
                    // When we are fallbacking to default local on missing content but this
                    // is not the default locale, we need canonicals too.
                    if ((bool)Strata::app()->getConfig("i18n.default_locale_fallback") && $currentPost->post_status === "publish") {
                        $defaultLocale = $this->polyglot->getDefaultLocale();
                        $originalPost = $defaultLocale->getTranslatedPost($currentPost->ID);
                        $originalUrl = get_permalink($originalPost);
                        $localizedFakeUrl = str_replace(WP_HOME . "/", WP_HOME . "/" . $locale->getUrl() . "/", $originalUrl);

                        $alternates[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', $locale->getCode(), $localizedFakeUrl);
                        $canonicals[] = sprintf('<link rel="canonical" href="%s">', $localizedFakeUrl);
                    }
                }
            }
        }

        echo
            implode("\n", $alternates) . "\n" .
            implode("\n", $canonicals) . "\n";
    }

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
