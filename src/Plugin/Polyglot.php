<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\Utility\Hash;

use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Db\Query;

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

    function __construct()
    {
        $this->initialize();
    }

    public function getCurrentLocale()
    {
        $currentPost = get_post();
        if ($currentPost) {
            return $this->getPostLocale($currentPost);
        }

        return parent::getCurrentLocale();
    }
    /**
     * Override the default function in order to use
     * our custom update functions.
     * @return [type] [description]
     */
    protected function createLocalesFromConfig()
    {
        $locales = Hash::normalize(Strata::config("i18n.locales"));

        foreach ($locales as $key => $config) {
            $this->locales[$key] = new Locale($key, $config);
        }
    }

    public function assignMappingByPost(\WP_Post $post)
    {
        $translations = null;

        if ($this->isTheOriginalPost($post)) {
            $this->assignPostMap($post);
            $translations = $this->findAllPostTranslations($post);
        } else {
            $originalPost = $this->findOriginalPost($post);
            $this->assignPostMap($originalPost);
            $translations = $this->findAllPostTranslations($originalPost);
        }

        if (!is_null($translations)) {
            $this->assignTranslationsMap($translations);
        }
    }

    public function isTheOriginalPost($targetPost)
    {
        if (!$this->isCachedQuery("isTheOriginalPost", $targetPost->ID)) {
            $query = new Query();
            $this->cacheQuery("isTheOriginalPost", $targetPost->ID, $query->isOriginal($targetPost));
        }

        return $this->queryCache["isTheOriginalPost"][$targetPost->ID];
    }

    public function findOriginalPost($translatedPost)
    {

        if ($this->isTheOriginalPost($translatedPost)) {
            return $translatedPost;
        }

        if (!$this->isCachedQuery("findOriginalPost", $translatedPost->ID)) {
            $query = new Query();
            $this->cacheQuery("findOriginalPost", $translatedPost->ID, $query->findOriginal($translatedPost));
        }

        return $this->queryCache["findOriginalPost"][$translatedPost->ID];
    }

    public function findAllPostTranslations($targetPost)
    {
        if (!$this->isCachedQuery("findAllTranslationsByPost", $targetPost->ID)) {
            $query = new Query();
            $this->cacheQuery("findAllTranslationsByPost", $targetPost->ID, $query->findAllTranlationsOfOriginal($targetPost));
        }

        return $this->queryCache["findAllTranslationsByPost"][$targetPost->ID];
    }

    public function getPostLocale($post)
    {
        if (!$this->isCachedQuery("getPostLocale", $post->ID)) {
            $query = new Query();
            $result = $query->findPostLocale($post);
            $this->cacheQuery("getPostLocale", $post->ID, $result ? $this->getLocaleByCode($result) : $this->getDefaultLocale());
        }

        return $this->queryCache["getPostLocale"][$post->ID];
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

    protected function assignTranslationsMap($rows)
    {
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $this->assignTranslationsRow($row);
            }
        }
    }

    protected function assignPostMap($post)
    {
        if (!$this->isCachedQuery("assignPostMap", $post->ID)) {
            $query = new Query();
            $this->cacheQuery("assignPostMap", $post->ID, $query->findDetails($post));
        }

        $data = $this->queryCache["assignPostMap"][$post->ID];
        $this->assignTranslationsRow($data);
    }

    protected function assignTranslationsRow($row)
    {
        $locale = $this->locales[$row->translation_locale];
        $locale->setDbRow($row);
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
