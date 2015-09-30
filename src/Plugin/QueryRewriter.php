<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\I18n\I18n;

use Polyglot\Plugin\Polyglot;
use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Db\Query;
use Polyglot\Plugin\Db\Logger;

use WP_Post;
use Exception;

class QueryRewriter {

    private $polyglot = null;
    private $logger = null;

    function __construct()
    {
        $this->polyglot = Polyglot::instance();
        $this->logger = new Logger();
    }

    public function registerHooks()
    {
        add_action("pre_get_posts", array($this, "preGetPosts"));

        add_filter('get_previous_post_where', array($this, 'filterAdjacentWhere'));
        add_filter('get_next_post_where', array($this, 'filterAdjacentWhere'));

        if (is_admin()) {
            add_filter('get_terms', array($this, 'getTerms'), 1, 3);
        } else {
            add_filter('get_terms_args', array($this, 'getTermsArgs'), 10, 2);
        }

        add_action('save_post', array($this, 'localizePostTerms'), 1, 3);
        add_filter('wp_insert_post_data', array($this, 'localizeParentId'), 10, 2);
        add_action('created_term', array($this, 'localizeExistingTerms'), 1, 3);
    }


    public function filterAdjacentWhere($clause)
    {
        global $wpdb;
        $locale = $this->polyglot->getCurrentLocale();

        if (!$locale->isDefault()) {
            $clause .= $wpdb->prepare(" AND p.ID IN (
                SELECT obj_id
                FROM {$wpdb->prefix}polyglot
                WHERE obj_kind = %s
                AND translation_locale = %s
            )", "WP_Post", $locale->getCode());
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
        $currentLocale = $this->polyglot->getCurrentLocale();

        // In the backend of when we are in the default locale,
        // prevent non-localized posts to show up. The correct way
        // to access these would be through the Locale objects.
        if (is_admin() || $currentLocale->isDefault()) {

            $this->logger->logQueryStart();
            $localizedPostIds = $this->polyglot->query()->listTranslatedEntitiesIds();
            if (count($localizedPostIds)) {
                $query->set("post__not_in", array_merge($query->get("post__not_in"), $localizedPostIds));
                #$this->logger->logQueryCompletion("Injected from pre_get_post: WHERE ID NOT IN (" . implode(", ", $localizedPostIds) . ")");
            }

        } else {

            $currentTranslations = $this->polyglot->query()->findLocaleTranslations($currentLocale, "WP_Post");
            $this->logger->logQueryStart();

            if ((bool)Strata::app()->getConfig("i18n.default_locale_fallback")) {

                // Collect all locales that aren't the current one and prevent them.
                // This massive filter allows for default posts and the properly localized
                // ones to show.
                $otherTranslations = array();

                foreach ($this->polyglot->getLocales() as $locale) {
                    if ($locale->getCode() !== $currentLocale->getCode()) {
                        $otherTranslations += $this->polyglot->query()->findLocaleTranslations($locale, "WP_Post");
                    }
                }

                $notIn = array();
                if (count($otherTranslations)) {
                    foreach ($otherTranslations as $translationEntity) {
                        $notIn[] = $translationEntity->obj_id;
                    }
                }

                // At the moment, we have filtered out other languages. There are duplicates
                // because we have to prevent the translated posts to appear.
                foreach ($currentTranslations as $translationEntity) {
                    $notIn[] = $translationEntity->translation_of;
                }

                if (count($notIn)) {
                    $query->set("post__not_in", array_merge($query->get("post__not_in"), $notIn));
                   # $this->logger->logQueryCompletion("Injected from pre_get_post: WHERE ID NOT IN (" . implode(", ", $notIn) . ")");
                }

            // When we don't have to fallback, force the posts from the current locale.
            } else {
                $in = array();
                foreach ($currentTranslations as $translationEntity) {
                    $in[] = $translationEntity->obj_id;
                }
                if (count($in)) {
                    $query->set("post__in", array_merge($query->get("post__in"), $in));
                   # $this->logger->logQueryCompletion("Injected from pre_get_post: WHERE ID IN (" . implode(", ", $in) . ")");
                }
            }
        }

        return $query;
    }


    public function getTerms($terms, $taxonomies)
    {
        $locale = $this->polyglot->getCurrentLocale();

        if ($locale->isDefault()) {
            $termIds = $this->polyglot->query()->listTranslatedEntitiesIds("Term");
        } else {
            $termIds = array();
            foreach ($this->polyglot->query()->findLocaleTranslations($locale, "Term") as $translation) {
                $termIds[] = $translation->obj_id;
            }
        }

        if (!count($termIds)) {
            return $terms;
        }

        $localized = array();
        foreach ($terms as $term) {

            $termIdToMatch = is_string($term) ? $term : $term->term_id;

            if ((int)$termIdToMatch > 0) {
                if ($locale->isDefault()) {
                    if (!in_array((int)$termIdToMatch, $termIds)) {
                        $localized[] = $term;
                    }
                } else {
                    if (in_array((int)$termIdToMatch, $termIds)) {
                        $localized[] = $term;
                    }
                }
            }
        }
        return $localized;
    }

    public function getTermsArgs($args, $taxonomies)
    {
        $locale = $this->polyglot->getCurrentLocale();

        if (!$locale->isDefault()) {
            $matches = $this->polyglot->query()->findTranslationIdsNotInLocale($locale, "Term");
        } else {
            $matches = $this->polyglot->query()->listTranslatedEntitiesIds("Term");
        }

        // By excluding ids, we allow for fallback
        // to the default locales.
        $args['exclude'] = array();
        foreach ((array)$matches as $id) {
            $args['exclude'][] = $id;
        }

        return $args;
    }


    /**
     * After creating a new taxonomy, if the taxonomy is not in
     * the default language, check all posts to see if we could link them
     * to this new term.
     * @param  [type] $termId    [description]
     * @param  [type] $termTaxid [description]
     * @param  [type] $taxonomy  [description]
     * @return [type]            [description]
     */
    public function localizeExistingTerms($termId, $tt_id, $taxonomy)
    {
        $configuration = $this->polyglot->getConfiguration();
        if ($configuration->isTaxonomyEnabled($taxonomy)) {

            $postsUsingOriginalTerm = get_posts(array(
                'tax_query' => array(array(
                        'taxonomy' => $taxonomy,
                        'field' => 'ID',
                        'terms' => $termId
                ))
            ));

            $collectedIds = array(-1);
            foreach ($postsUsingOriginalTerm as $post) {
                $collectedIds[] = $post->ID;
            }

            $translatedPostUsingDefaultTerm = $this->polyglot->query()->findDetailsByIds($collectedIds);
            foreach ((array)$translatedPostUsingDefaultTerm as $translation) {

                $locale = $this->polyglot->getLocaleByCode($translation->translation_locale);
                if (!$locale->isDefault()) {

                    $translatedTerm = $locale->getTranslatedTerm($termId, $taxonomy);
                    if ($translatedTerm) {
                        wp_remove_object_terms($termId, $translatedTerm->term_id, $taxonomy);
                        wp_add_object_terms($translation->obj_id, $translatedTerm->term_id, $taxonomy);
                    }
                }
            }
        }
    }

    /**
     * When saving a post, validates the sent data to ensure the localized
     * post parent is saved upon saving a localized sub-post.
     * @see wp_insert_post_data
     * @param  array $data    Inserted data
     * @param  array $postarr Working data
     * @return array
     */
    public function localizeParentId($data , $postarr)
    {
        if (array_key_exists("ID", $postarr)) {
            $currentLocale = $this->polyglot->getCurrentLocale();
            $defaultLocale = $this->polyglot->getDefaultLocale();
            if (!$currentLocale->isDefault()) {

                // Check the default locale's post for a parent id. If there's a
                // parent id, then try to find if it has a translation. If it does,
                // that we've finally found what is the correct parent id.
                $translation = $defaultLocale->getTranslatedPost($postarr['ID']);
                if ($translation && (int)$translation->post_parent > 0) {
                    $parentPostTranslation = $currentLocale->getTranslatedPost($translation->post_parent);
                    if ($parentPostTranslation) {
                        $data["post_parent"] = $parentPostTranslation->ID;
                    }
                }
            }
        }

        return $data;
    }


    /**
     * @param int $postId The post ID.
     * @param post $post The post object.
     * @param bool $update Whether this is an existing post being updated or not.
     */
    public function localizePostTerms($postId)
    {
        if (wp_is_post_revision($postId)) {
            return;
        }

        $configuration = $this->polyglot->getConfiguration();
        $locale = $this->polyglot->getDefaultLocale();

        foreach ($configuration->getEnabledTaxonomies() as $taxonomy) {
            foreach ($this->polyglot->getLocales() as $locale) {

                // Clear the terms of all translations and update with either the
                // default taxonomy or the localized version.
                $translatedPost = $locale->getTranslatedPost($postId);
                if ($translatedPost) {
                    wp_delete_object_term_relationships($translatedPost->obj_id, $taxonomy);

                    // Assign either the original term as backup or the localized
                    // term.
                    foreach (wp_get_post_terms($postId, $taxonomy) as $term) {
                        $translatedTerm = $locale->getTranslatedTerm($term->term_id, $taxonomy);
                        wp_add_object_terms($postId, $translatedTerm ? $translatedTerm->term_id : $term->term_id, $taxonomy);
                    }
                }
            }
        }
    }

}
