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
    private $rewriter;

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
        add_filter( 'body_class', array($this, "classHandler"));
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

        $this->rewriter = new UrlRewriter();
        $this->rewriter->registerHooks();

        $querier = new QueryRewriter();
        $querier->registerHooks();

        $this->setupPostTrash();
        $this->setupTermTrash();

        add_action('plugins_loaded', array($this, 'load'));
        add_action('widgets_init', array("\\Polyglot\\Widget\\LanguageMenu", "register"));
    }

    protected function setupPostTrash()
    {
        add_action('wp_trash_post', array($this, 'onTrashPost'));
    }

    protected function removePostTrash()
    {
        remove_action('wp_trash_post', array($this, 'onTrashPost'));
    }

    protected function setupTermTrash()
    {
        add_action('delete_term_taxonomy', array($this, 'onTrashTerm'));
    }

    protected function removeTermTrash()
    {
        remove_action('delete_term_taxonomy', array($this, 'onTrashTerm'));
    }

    public function onTrashTerm($termId)
    {
        // We need to remove the listener because it would start infinite loops.
        $this->removeTermTrash();
        Polyglot::instance()->query()->unlinkTranslationFor($termId, "Term");
        Polyglot::instance()->query()->unlinkTranslation($termId, "Term");
        $this->setupTermTrash();
    }

    public function onTrashPost($postId)
    {
        // We need to remove the listener because it would start infinite loops.
        $this->removePostTrash();
        Polyglot::instance()->query()->unlinkTranslationFor($postId, "WP_Post");
        Polyglot::instance()->query()->unlinkTranslation($postId, "WP_Post");
        $this->setupPostTrash();
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
     * @filters strata_polyglot_canonicals_meta_before_print, strata_polyglot_alternates_meta_before_print.
     */
    public function appendHeaderHtml()
    {
        $alternates = array();
        $canonicals = array();

        $currentLocale = $this->polyglot->getCurrentLocale();
        $defaultLocale = $this->polyglot->getDefaultLocale();

        $currentPost = get_post();
        if ($currentPost) {
            foreach ($this->polyglot->getLocales() as $locale) {
                if ($locale->hasPostTranslation($currentPost->ID)) {
                    $translatedPost = $locale->getTranslatedPost($currentPost->ID);
                    if ($translatedPost && $translatedPost->post_status === "publish") {
                        $localizedUrl = get_permalink($translatedPost->ID);
                        if ((bool)Strata::app()->getConfig("i18n.default_locale_fallback")) {
                            $localizedUrl = $this->rewriter->getLocalizedFallbackUrl($localizedUrl, $locale);
                        }
                        $alternates[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', $locale->getCode(),  $localizedUrl);
                    }
                } else {
                    // When we are fallbacking to default local on missing content but this
                    // is not the default locale, we need canonicals too.
                    if ((bool)Strata::app()->getConfig("i18n.default_locale_fallback") && $currentPost->post_status === "publish") {

                        $originalPost = $defaultLocale->getTranslatedPost($currentPost->ID);
                        $originalUrl = get_permalink($originalPost);
                        $localizedFakeUrl = $this->rewriter->getLocalizedFallbackUrl($originalUrl, $locale);

                        $alternates[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', $locale->getCode(), $localizedFakeUrl);

                        // On a forced translation page, if the current locale is pretending to exist but
                        // fallbacks to the global, say it's a canonical of that global translation.
                        if (!$currentLocale->isDefault() && $currentLocale->getCode() === $locale->getCode()) {

                            $localizedFakeUrl = $this->rewriter->getLocalizedFallbackUrl($originalUrl, $defaultLocale);
                            $canonicals[] = sprintf('<link rel="canonical" href="%s">', $localizedFakeUrl);
                        }
                    }
                }
            }
        }

        $alternates = apply_filters("strata_polyglot_alternates_meta_before_print", $alternates);
        $canonicals = apply_filters("strata_polyglot_canonicals_meta_before_print", $canonicals);

        echo
            implode("\n", $alternates) . "\n" .
            implode("\n", $canonicals) . "\n";
    }

    public function classHandler($classes)
    {
        // On a secondary locale, if the current page is a translation
        // of the page on front, then replace the classes of the body correctly
        $currentLocale = $this->polyglot->getCurrentLocale();
        if (!$currentLocale->isDefault()) {
            $pageOnFront = $currentLocale->getTranslatedPost(get_option('page_on_front'));
            if ($pageOnFront && $pageOnFront->ID == get_the_ID()) {
                array_splice($classes, 0, 0, "blog");
            }
        }

        return $classes;
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
