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

    function __construct()
    {
        $this->throwIfGlobalExists();
        $this->initialize();

        add_action('wp', array($this, "setCurrentLocaleByPostContext"));
        add_action('wp_head', array($this, "appendHeaderHtml"));
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

    /**
     * Sets the current locale based on a loaded post, either
     */
    public function setCurrentLocaleByPostContext()
    {
        if (is_admin()) {
            $request = new Request();
            if ($request->hasGet("post")) {
                return $this->setLocaleByPostId($request->get("post"));
            }
        }

        $postId = get_the_ID();
        if ($postId) {
            return $this->setLocaleByPostId($postId);
        }
    }


    /**
     * Returns the list of all translations for a given object. The result of the
     * query is cached during the whole page rendering process.
     * @param  mixed $targetPost
     * @return array
     */
    public function findAllTranslationsOf($targetPost)
    {
        if (!$this->isCachedQuery("findAllTranslationsByPost", $targetPost->ID)) {
            $query = new Query();
            $this->cacheQuery("findAllTranslationsByPost", $targetPost->ID, $query->findAllTranlationsOfOriginal($targetPost));
        }

        return $this->queryCache["findAllTranslationsByPost"][$targetPost->ID];
    }

    public function findPostLocale($post)
    {
        if (!$this->isCachedQuery("findPostLocale", $post->ID)) {
            $query = new Query();
            $result = $query->findPostLocale($post);
            $this->cacheQuery("findPostLocale", $post->ID, $result ? $this->getLocaleByCode($result) : $this->getDefaultLocale());
        }

        return $this->queryCache["findPostLocale"][$post->ID];
    }

    public function findTranslationDetails($post)
    {
        if (!$this->isCachedQuery("findTranslationDetails", $post->ID)) {
            $this->cacheQuery("findTranslationDetails", $post->ID, $this->query()->findDetails($post));
        }

        return $this->queryCache["findTranslationDetails"][$post->ID];
    }

    public function findOriginalTranslationDetails($post)
    {
        if (!$this->isCachedQuery("findOriginalTranslationDetails", $post->ID)) {
            $this->cacheQuery("findOriginalTranslationDetails", $post->ID, $this->query()->findOriginalTranslationDetails($post));
        }

        return $this->queryCache["findOriginalTranslationDetails"][$post->ID];
    }


    public function hasTranslationDetails($post)
    {
        return !is_null($this->findTranslationDetails($post));
    }

    public function getCachedPostById($id)
    {
        if (!$this->isCachedPost($id)) {
            $post = get_post($id);
            if (!is_null($post)) {
                $this->cachePost($id, $post);
            }
        }

        return $this->postCache[$id];
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

    protected function query()
    {
        return new Query();
    }

    protected function registerHooks()
    {
        parent::registerHooks();
        add_action('wp', array($this, "setCurrentLocaleByPostContext"));
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

    protected function isCachedPost($postId)
    {
        return array_key_exists($postId, $this->postCache);
    }

    protected function cachePost($postId, WP_Post $post)
    {
        $this->postCache[$postId] = $post;
    }

    protected function isCachedQuery($function, $objectId)
    {
        return array_key_exists($function, $this->queryCache) && array_key_exists($objectId, $this->queryCache[$function]);
    }

    protected function cacheQuery($function, $objectId, $data)
    {
        if (!array_key_exists($function, $this->queryCache)) {
            $this->queryCache[$function] = array();
        }

        if (!array_key_exists($objectId, $this->queryCache[$function])) {
            $this->queryCache[$function][$objectId] = null;
        }

        // Placed outside of the previous If in case we need to reset the cache.
        $this->queryCache[$function][$objectId] = $data;
    }

    private function setLocaleByPostId($postId)
    {
        $post = $this->getCachedPostById($postId);
        $originalPost = $this->getMapper()->assignMappingByPost($post);

        $locale = $this->findPostLocale($post);
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
