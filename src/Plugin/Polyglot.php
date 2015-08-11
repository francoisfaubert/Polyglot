<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\Utility\Hash;
use Strata\Controller\Request;

use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Configuration;
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

    protected $mapper = null;
    protected $query = null;
    protected $configuration = null;

    function __construct()
    {
        $this->throwIfGlobalExists();
        $this->initialize();
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

    /**
     * Returns a object than handles and caches queries
     * @return Query
     */
    public function query()
    {
        if (is_null($this->query)) {
            $this->query = new Query($this);
        }

        return $this->query;
    }

    /**
     * Returns an object that maps all the configuration values
     * @return Configuration
     */
    public function getConfiguration()
    {
        if (is_null($this->configuration)) {
            $this->configuration = new Configuration();
        }

        return $this->configuration;
    }

    /**
     * Appends meta tags with additional localization information and links to localized versions.
     * @return html (it actually echoes it)
     * @see wp_head
     */
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
        //todo
    }

    /**
     * Triggered in the backend to learn the locale based on the type
     * of object the user is browsing. Prevents a user from seeing
     * a object in another locale than the one the object is supposed to be in.
     */
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

    /**
     * Generates a TranslationEntity wrapper around a post.
     * @param  WP_Post $post
     * @return TranslationEntity
     */
    public function generateTranslationEntity(WP_Post $post)
    {
        return $this->query()->createTranslationEntity($post);
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
