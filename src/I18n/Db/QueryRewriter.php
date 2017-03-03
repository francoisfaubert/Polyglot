<?php

namespace Polyglot\I18n\Db;

use Strata\Strata;
use Strata\Router\Router;
use Strata\I18n\I18n;
use Polyglot\I18n\Locale\Locale;
use Polyglot\I18n\Db\Query;
use Polyglot\I18n\Db\Logger;
use Polyglot\I18n\Locale\ContextualManager;
use WP_Post;
use Exception;

class QueryRewriter {

    private $logger = null;
    protected $currentLocale;
    protected $defaultLocale;
    protected $query;
    protected $configuration;
    protected $wpIsCaching = false;

    function __construct()
    {
        $this->logger = new Logger();


        $i18n = Strata::i18n();
        $this->currentLocale = $i18n->getCurrentLocale();
        $this->defaultLocale = $i18n->getDefaultLocale();
        $this->query = $i18n->query();
        $this->configuration = $i18n->getConfiguration();
    }


    public function filterAdjacentWhere($clause)
    {
        global $wpdb;

        if (!$this->currentLocale->isDefault()) {
            $clause .= $wpdb->prepare(" AND p.ID IN (
                SELECT obj_id
                FROM {$wpdb->prefix}polyglot
                WHERE obj_kind = %s
                AND translation_locale = %s
            )", "WP_Post", $this->currentLocale->getCode());
        } else {
            $clause .= $wpdb->prepare(" AND p.ID NOT IN (
                SELECT obj_id
                FROM {$wpdb->prefix}polyglot
                WHERE obj_kind = %s
            ) ", "WP_Post");
        }

        return $clause;
    }

    public function preGetPosts($query)
    {
        if ($query->get("polyglot_locale")) {
            $this->currentLocale = Strata::i18n()->getLocaleByCode($query->get("polyglot_locale"));
        }

        // In the backend of when we are in the default locale,
        // prevent non-localized posts to show up. The correct way
        // to access these would be through the Locale objects.
        if ($this->currentLocale->isDefault()) {

            $localizedPostIds = $this->query->listTranslatedEntitiesIds("WP_Post");
            if (count($localizedPostIds)) {
                $query->set("post__not_in", array_merge($query->get("post__not_in"), $localizedPostIds));
            }

        } else {

            $postType = null;
            if (isset($query->query_vars['post_type'])) {
                $postType = $query->query_vars['post_type'];
            }

            if ($this->postTypeIsSupported($postType)) {

                $currentTranslations = $this->query->findLocaleTranslations($this->currentLocale, "WP_Post", $postType);

                if ((bool)Strata::app()->getConfig("i18n.default_locale_fallback")) {
                    // Collect all locales that aren't the current one and prevent them.
                    // This massive filter allows for default posts and the properly localized
                    // ones to show.
                    // Except for the default locale because additional validation needs to be
                    // done on that one to chose whether the fallback is needed.
                    $otherTranslations = array();
                    foreach (Strata::i18n()->getLocales() as $locale) {
                        if ($locale->getCode() !== $this->currentLocale->getCode() && !$locale->isDefault() ) {
                            $otherTranslations += $this->query->findLocaleTranslations($locale, "WP_Post", $postType);
                        }
                    }

                    $notIn = array();
                    if (count($otherTranslations)) {
                        foreach ($otherTranslations as $translationEntity) {
                            if ($postType === $translationEntity->getObjectType()) {
                                $notIn[] = $translationEntity->getObjectId();
                            }
                        }
                    }

                    // At the moment, we have filtered out other languages. There are duplicates
                    // with the default locale and we have to prevent the translation sources from
                    // appearing.
                    foreach ($currentTranslations as $translationEntity) {
                        $notIn[] = $translationEntity->getOriginalObjectId();
                    }

                    if (count($notIn)) {
                        $query->set("post__not_in", array_merge($query->get("post__not_in"), $notIn));
                    }

                // When we don't have to fallback, force the posts from the current locale.
                } else {
                    $in = array();
                    foreach ($currentTranslations as $translationEntity) {
                        if ($postType === $translationEntity->getObjectType()) {
                            $in[] = $translationEntity->getObjectId();
                        }
                    }
                    if (count($in)) {
                        $query->set("post__in", array_merge($query->get("post__in"), $in));
                    }
                }
            }
        }

        return $query;
    }

    public function getTerms($terms, $taxonomies, $query)
    {
        if (!$this->taxonomyGroupIsSupported($taxonomies) || (bool)$this->wpIsCaching) {
            return $terms;
        }

        if ($this->currentLocale->isDefault()) {
            $termIds = $this->query->listTranslatedEntitiesIds("Term");
        } else {
            $termIds = array();
            foreach ($this->query->findLocaleTranslations($this->currentLocale, "Term") as $translation) {
                $termIds[] = $translation->getObjectId();
            }
        }

        // bail if Polyglot has nothing else to add.
        if (!count($termIds)) {
            return $terms;
        }

        $localized = array();
        $shouldFallback = Strata::i18n()->shouldFallbackToDefaultLocale();
        foreach ($terms as $term) {
            $termIdToMatch = is_string($term) ? $term : $term->term_id;
            if ((int)$termIdToMatch > 0) {

                if ($this->currentLocale->isDefault()) {
                    if (!in_array((int)$termIdToMatch, $termIds)) {
                        $localized[] = $term;
                    }
                } else {
                    if (in_array((int)$termIdToMatch, $termIds) || $shouldFallback) {
                        $localized[] = $term;
                    }
                }
            }
        }

        if ($shouldFallback && !count($localized)) {
            return $terms;
        }

        return $localized;
    }

    public function getTermsArgs($args, $taxonomies)
    {
        if (!$this->taxonomyGroupIsSupported($taxonomies) || $this->wordpressIsCaching($args)) {
            return $args;
        }

        $notIn = array();
        $i18n = Strata::i18n();
        $locales = $i18n->getLocales();
        $shouldFallbackToDefault = $i18n->shouldFallbackToDefaultLocale();

        foreach ($taxonomies as $taxonomy) {

            // Remove references to the original object
            $currentTranslations = $this->query->findLocaleTranslations($this->currentLocale, "Term", $taxonomy);
            foreach ($currentTranslations as $translationEntity) {
                $notIn[] = $translationEntity->getOriginalObjectId();
            }

            // Remove translations in other locales
            $otherTranslations = array();
            foreach ($locales as $locale) {
                if ($locale->getCode() !== $this->currentLocale->getCode()) {
                    if (!$locale->isDefault() && !$this->currentLocale->isDefault()) {
                        $otherTranslations += $this->query->findLocaleTranslations($locale, "Term", $taxonomy);
                    }
                }
            }

            if (count($otherTranslations)) {
                foreach ($otherTranslations as $translationEntity) {
                    $notIn[] = $translationEntity->getObjectId();
                }
            }
        }

        if (count($notIn)) {
            $args['exclude'] = $notIn;
        }

        return $args;
    }

    private function taxonomyGroupIsSupported($taxonomies)
    {
        foreach ($taxonomies as $taxonomy) {
            if (!$this->configuration->isTaxonomyEnabled($taxonomy)) {
                return false;
            }
        }

        return true;
    }

    private function postTypeIsSupported($postType)
    {
        return $this->configuration->isTypeEnabled($postType);
    }

    private function wordpressIsCaching($args = array())
    {
        $this->wpIsCaching = $args['fields'] === "id=>parent" && $args['cache_domain'] === "core";
        return $this->wpIsCaching;
    }
}
