<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\Utility\Hash;
use Strata\Controller\Request;

use Polyglot\Plugin\Locale;
use Polyglot\Plugin\TranslationEntity;

use Polyglot\Plugin\Db\Query;

use WP_Post;
use Exception;


/**
 * Polyglot extends the default I18n class to add translation support
 * of dynamic objects. Otherwise I18n would only translate strings.
 */
class Polyglot extends \Strata\I18n\I18n {

    public static function instance()
    {
        global $polyglot;
        return is_null($polyglot) ? new self() : $polyglot;
    }

    protected $configuration;

    protected $queryCache = array();
    protected $postCache = array();

    protected $mapper = null;
    protected $query = null;

    function __construct()
    {
        $this->throwIfGlobalExists();
        $this->initialize();
        $this->query = new Query();
    }

    /**
     * Return an object that maps all the translations for a post.
     * It is used when multiple corresponding object translations must
     * be taken into account.
     * @return Mapper
     */
    public function getMapper()
    {
        if (is_null($this->mapper)) {
            $this->mapper = new Mapper($this);
        }

        return $this->mapper;
    }

    public function appendHeaderHtml()
    {
        $metatags = array();

        // Loop alternate versions
        foreach ($this->getLocales() as $locale) {
            if ($locale->hasTranslation()) {
                $metatags[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', $locale->getCode(), $locale->getTranslationPermalink());
            }
        }

        echo implode("\n", $metatags) . "\n";
    }


    /**
     * Contextualize all the locale translation details based on
     * the post variable
     * @param  WP_Post $post
     * @return TranslationEntity        The original post translation info
     */
    public function contextualizeMappingByPost(WP_Post $post)
    {
        return $this->getMapper()->assignMappingByPost($post);
    }


    public function contextualizeMappingByTaxonomy()
    {

    }

    public function setCurrentLocaleByAdminContext()
    {
        $request = new Request();
        if ($request->hasGet("post")) {
            return $this->setLocaleByPostId($request->get("post"));
        }

        if ($request->hasGet("taxonomy") && $request->hasGet("tag_ID")) {
            return $this->setLocaleByTaxonomyId($request->get("taxonomy"), $request->get("tag_ID"));
        }


    }

    /**
     *
     */
    public function setCurrentLocaleByFrontContext()
    {
        $postId = get_the_ID();
        if ($postId) {
            return $this->setLocaleByPostId($postId);
        }
    }

    public function isTypeEnabled($postType)
    {
        return in_array($postType, $this->getEnabledPostTypes());
    }

    public function isTaxonomyEnabled($taxonomy)
    {
        return in_array($taxonomy, $this->getEnabledTaxonomies());
    }

    public function toggleTaxonomy($taxonomy)
    {
        if (is_null($taxonomy)) {
            return;
        }

        $config = $this->getConfiguration();

        if (!$this->isTaxonomyEnabled($taxonomy)) {
            $config["taxonomies"][] = $taxonomy;
        }
        elseif(($key = array_search($taxonomy, $config["taxonomies"])) !== false) {
            unset($config["taxonomies"][$key]);
            $config = array_filter($config);
        }

        $this->configuration["taxonomies"] = $config["taxonomies"];
        $this->updateConfiguration();
    }

    public function togglePostType($postType)
    {
        if (is_null($postType)) {
            return;
        }

        $config = $this->getConfiguration();

        if (!$this->isTypeEnabled($postType)) {
            $config["post-types"][] = $postType;
        }
        elseif(($key = array_search($postType, $config["post-types"])) !== false) {
            unset($config["post-types"][$key]);
            $config = array_filter($config);
        }

        $this->configuration["post-types"] = $config["post-types"];
        $this->updateConfiguration();
    }


    public function getConfiguration()
    {
        if (is_null($this->configuration)) {
            $this->configuration = get_option("polyglot_configuration", $this->getDefaultConfiguration());
        }

        return $this->configuration;
    }

    protected function updateConfiguration()
    {
        return update_option("polyglot_configuration", $this->configuration);
    }

    public function getOptions()
    {
        return $this->getConfiguration()['options'];
    }

    public function getEnabledPostTypes()
    {
        return $this->getConfiguration()['post-types'];
    }

    public function getEnabledTaxonomies()
    {
        return $this->getConfiguration()['taxonomies'];
    }

    public function getDefaultConfiguration()
    {
        return array(
            "options" => array(),
            "post-types" => array(),
            "taxonomies" => array()
        );
    }

    public function getPostTypes()
    {
        return get_post_types(array(), "object");
    }

    public function getTaxonomies()
    {
        return get_taxonomies(array(), "objects");
    }

    public function generateTranslationEntity(WP_Post $post)
    {
        return $this->query()->createTranslationEntity($post);
    }

    public function query()
    {
        return $this->query;
    }

    protected function registerHooks()
    {
        parent::registerHooks();

        if (is_admin()) {
            add_action('admin_init', array($this, "setCurrentLocaleByAdminContext"));
        }

        add_action('wp', array($this, "setCurrentLocaleByFrontContext"));
        add_action('wp_head', array($this, "appendHeaderHtml"));
    }

    /**
     * Overrides the default function in order to use
     * our custom Locale object and its update functions.
     * @return array
     */
    protected function parseLocalesFromConfig()
    {
        $locales = Hash::normalize(Strata::config("i18n.locales"));
        $newLocales = array();

        foreach ($locales as $key => $config) {
            $newLocales[$key] = new Locale($key, $config);
        }

        return $newLocales;
    }

    private function setLocaleByPostId($postId)
    {
        $post = $this->query()->findCachedPostById($postId);
        $this->getMapper()->assignMappingByPost($post);

        return $this->setLocaleByObject($post);
    }

    private function setLocaleByTaxonomyId($taxonomyType, $taxonomyId)
    {
        $taxonomy = $this->query()->findCachedTaxonomyById($taxonomyType, $taxonomyId);
        $this->getMapper()->assignMappingByTaxonomies($taxonomy);

        return $this->setLocaleByObject($taxonomy);
    }

    private function setLocaleByObject($mixed)
    {
        $locale = $this->getLocaleByCode($this->query()->findObjectLocale($mixed));
        if (!is_null($locale)) {
            $this->setLocale($locale);
            return $locale;
        }
    }


    /**
     * If there are multiple instances of Polyglot running at the same time,
     * an exception should be raised.
     * @throws Exception
     */
    private function throwIfGlobalExists()
    {
        /**
         *  Hello,
         *
         *  If this exception is an hindrance to you, please go to our GitHub and
         *  explain what you which to accomplish by creating a second instance of
         *  the Polyglot object.
         *
         *  I am writing this throw early in the life of the plugin and I am still on the
         *  fence on whether it should exist.
         *
         *  I am adding the throw because I think it would slow the website to a crawl
         *  if I allow multiple instances of Polyglot that maintain their own separate caches. I would
         *  rather have an optimized list of API functions available on the global $polyglot object.
         *
         *  That's the idea anyways.
         *  Cheers,
         *
         *  - Frank.
         */
        global $polyglot;
        if (!is_null($polyglot)) {
            throw new Exception("There should only be one active reference to Polyglot. Please use global \$polyglot to get the instance.");
        }
    }
}
