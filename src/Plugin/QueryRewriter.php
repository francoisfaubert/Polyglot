<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\I18n\I18n;

use Polyglot\Plugin\Polyglot;
use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Db\Query;

use WP_Post;
use Exception;

class QueryRewriter {

    private $polyglot = null;

    function __construct()
    {
        $this->polyglot = Polyglot::instance();
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

        add_action('save_post', array($this, 'localizePostTerms'), 1, 3 );
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

    public function notAPolyglotPost($where = '')
    {
        global $wpdb;
        return $where . $wpdb->prepare(" AND {$wpdb->prefix}posts.ID NOT IN (
                SELECT obj_id
                FROM {$wpdb->prefix}polyglot
                WHERE obj_kind = %s
            ) ", "WP_Post");
    }

    public function inPolyglotPosts($where = '')
    {
        $locale = $this->polyglot->getCurrentLocale();
        global $wpdb;
        return $where . $wpdb->prepare(" AND {$wpdb->prefix}posts.ID IN (
            SELECT obj_id
            FROM {$wpdb->prefix}polyglot
            WHERE translation_locale = %s
            AND  obj_kind = %s
        )", $locale->getCode(), "WP_Post" );
    }

    public function preGetPosts($query)
    {
        $locale = $this->polyglot->getCurrentLocale();
        $postIds = array();

        if (is_admin() || $locale->isDefault()) {
            add_filter('posts_where', array($this, 'notAPolyglotPost'));
        } else {

            if ($query->is_main_query()) {
                $matches = $this->polyglot->query()->findTranslationIdsOf($locale, "WP_Post");
                foreach ((array)$matches as $row) {
                    $postIds[] = $row;
                }
                $query->set("post__in", $postIds);
            }
        }

        return $query;
    }


    public function getTerms($terms, $taxonomies)
    {
        $locale = $this->polyglot->getCurrentLocale();

        if ($locale->isDefault()) {
            $matches = $this->polyglot->query()->listTranslatedIds("Term");
        } else {
            $matches = $this->polyglot->query()->findTranslationIdsOf($locale, "Term");
        }

        if (!count($matches)) {
            return $terms;
        }

        $termIds = array();
        foreach ((array)$matches as $row) {
            $termIds[] = (int)$row;
        }

        $localized = array();

        foreach ($terms as $term) {

            $termIdToMatch = null;

            if (is_string($term)) {
                $termIdToMatch = $term;
            } else {
                $termIdToMatch = $term->term_id;
            }

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
            $matches = $this->polyglot->query()->listTranslatedIds("Term");
        }

        // By excluding ids, we allow for fallback
        // to the default locales.
        $args['exclude'] = array();
        foreach ((array)$matches as $row) {
            $args['exclude'][] = $row->obj_id;
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
     * @param int $postId The post ID.
     * @param post $post The post object.
     * @param bool $update Whether this is an existing post being updated or not.
     */
    public function localizePostTerms($postId)
    {
        if (wp_is_post_revision($postId))
            return;

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
