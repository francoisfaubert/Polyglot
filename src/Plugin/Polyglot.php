<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\Utility\Hash;

use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Db\Query;

use WP_Post;
use Exception;
use Gettext\Translations;
use Gettext\Translation;


/**
 * Polyglot extends the default I18n class to add translation support
 * of dynamic objects. Otherwise I18n would only translate strings.
 */
class Polyglot extends \Strata\I18n\I18n {
    protected $configuration;

    protected $queryCache = array();
    protected $postCache = array();
    public $mapper = null;

    function __construct()
    {
        $this->initialize();
    }

    public function getCurrentLocale()
    {
        $currentId = get_the_ID();
        if ($currentId) {
            $currentPost = $this->getCachedPostById($currentId);
            if ($currentPost) {
                return $this->findPostLocale($currentPost);
            }
        }

        return parent::getCurrentLocale();
    }

    /**
     * Overrides the default function in order to use
     * our custom Locale object and update functions.
     * @return [type] [description]
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
     * Specifies whether $target is the original untranslated version. This
     * query is cached during the whole page rendering process.
     * @param  mixed  $targetPost
     * @return boolean
     */
    public function isTheOriginal($target)
    {
        if (!$this->isCachedQuery("isTheOriginal", $target->ID)) {
            $query = new Query();
            $this->cacheQuery("isTheOriginal", $target->ID, $query->isOriginal($target));
        }

        return $this->queryCache["isTheOriginal"][$target->ID];
    }

    /**
     * Loads up the original post of $translatedPost. The result of the
     * query is cached during the whole page rendering process.
     * @param  WP_Post  $targetPost [description]
     * @return WP_Post
     */
    public function findOriginalPost(WP_Post $translatedPost)
    {
        if ($this->isTheOriginal($translatedPost)) {
            return $translatedPost;
        }

        if (!$this->isCachedQuery("findOriginalPost", $translatedPost->ID)) {
            $query = new Query();
            $originalPost = $this->getCachedPostById($query->findOriginal($translatedPost));
            $this->cacheQuery("findOriginalPost", $translatedPost->ID, $originalPost);
        }

        return $this->queryCache["findOriginalPost"][$translatedPost->ID];
    }

    /**
     * Returns the list of all translations for a given object. The result of the
     * query is cached during the whole page rendering process.
     * @param  mixed $targetPost
     * @return array
     */
    public function findAllTranslations($targetPost)
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
            $query = new Query();
            $this->cacheQuery("findTranslationDetails", $post->ID, $query->findDetails($post));
        }

        return $this->queryCache["findTranslationDetails"][$post->ID];
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
}
