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
        // $this->logger->logQueryStart();

        // In the backend of when we are in the default locale,
        // prevent non-localized posts to show up. The correct way
        // to access these would be through the Locale objects.
        if ((is_admin() && !Router::isFrontendAjax() ) || $this->currentLocale->isDefault() || is_search()) {

            $localizedPostIds = $this->query->listTranslatedEntitiesIds("WP_Post");
            if (count($localizedPostIds)) {
                $query->set("post__not_in", array_merge($query->get("post__not_in"), $localizedPostIds));
                #$this->logger->logQueryCompletion("Injected from pre_get_post: WHERE ID NOT IN (" . implode(", ", $localizedPostIds) . ")");
            }

        } else {

            $postType = null;
            if (isset($query->query_vars['post_type'])) {
                $postType = $query->query_vars['post_type'];
            }

            if ($this->postTypeIsSupported($postType)) {

                $thiscurrentTranslations = $this->query->findLocaleTranslations($this->currentLocale, "WP_Post", $postType);

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
                        #$this->logger->logQueryCompletion("Injected from pre_get_post: WHERE ID NOT IN (" . implode(", ", $notIn) . ")");
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
                       # $this->logger->logQueryCompletion("Injected from pre_get_post: WHERE ID IN (" . implode(", ", $in) . ")");
                    }
                }
            }
        }

        return $query;
    }


    /**
     * This is only ran in the admin
     * @param  [type] $terms      [description]
     * @param  [type] $taxonomies [description]
     * @return [type]             [description]
     */
    public function getTerms($terms, $taxonomies)
    {
        if (!$this->taxonomyGroupIsSupported()) {
            return $terms;
        }

        // The current locale gets lost in metabox queries.
        if (is_admin() && !Router::isAjax()) {
            $context = new ContextualManager();
            $locale = $context->getByAdminContext();
            if (!is_null($locale)) {
                $this->currentLocale = $locale;
            }
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
        foreach ($terms as $term) {
            $termIdToMatch = is_string($term) ? $term : $term->term_id;
            if ((int)$termIdToMatch > 0) {
                if ($this->currentLocale->isDefault()) {
                    if (!in_array((int)$termIdToMatch, $termIds)) {
                        $localized[] = $term;
                    }
                } elseif (in_array((int)$termIdToMatch, $termIds)) {
                    $localized[] = $term;
                }
            }
        }

        return $localized;
    }

    public function getTermsArgs($args, $taxonomies)
    {
        $notIn = array();
        foreach ($taxonomies as $taxonomy) {

            $currentTranslations = $this->query->findLocaleTranslations($this->currentLocale, "Term", $taxonomy);
            $otherTranslations = array();

            foreach (Strata::i18n()->getLocales() as $locale) {
                if ($locale->getCode() !== $this->currentLocale->getCode() && !$locale->isDefault() ) {
                    $otherTranslations += $this->query->findLocaleTranslations($locale, "Term", $taxonomy);
                }
            }

            if (count($otherTranslations)) {
                foreach ($otherTranslations as $translationEntity) {
                    $notIn[] = $translationEntity->getObjectId();
                }
            }

            foreach ($currentTranslations as $translationEntity) {
                $notIn[] = $translationEntity->getOriginalObjectId();
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


    /**
     * After creating a new taxonomy, if the taxonomy is not in
     * the default language, check all posts to see if we could link them
     * to this new term.
     * @param  [type] $termId    [description]
     * @param  [type] $termTaxid [description]
     * @param  [type] $taxonomy  [description]
     * @return [type]            [description]
     */
    // public function localizeExistingTerms($termId, $tt_id, $taxonomy)
    // {
    //     $configuration = Strata::i18n()->getConfiguration();
    //     if ($configuration->isTaxonomyEnabled($taxonomy)) {

    //         $postsUsingOriginalTerm = get_posts(array(
    //             'tax_query' => array(array(
    //                     'taxonomy' => $taxonomy,
    //                     'field' => 'ID',
    //                     'terms' => $termId
    //             ))
    //         ));

    //         $collectedIds = array(-1);
    //         foreach ($postsUsingOriginalTerm as $post) {
    //             $collectedIds[] = $post->ID;
    //         }

    //         $translatedPostUsingDefaultTerm = $this->query->findDetailsByIds($collectedIds);
    //         foreach ((array)$translatedPostUsingDefaultTerm as $translation) {

    //             $locale = $translation->getTranslationLocale();
    //             if (!$locale->isDefault()) {
    //                 $translatedTerm = $locale->getTranslatedTerm($termId, $taxonomy);
    //                 if ($translatedTerm) {
    //                     wp_remove_object_terms($termId, $translatedTerm->term_id, $taxonomy);
    //                     wp_add_object_terms($translation->getObjectId(), $translatedTerm->term_id, $taxonomy);
    //                 }
    //             }
    //         }
    //     }
    // }

    /**
     * When saving a post, validates the sent data to ensure the localized
     * post parent is saved upon saving a localized sub-post.
     * @see wp_insert_post_data
     * @param  array $data    Inserted data
     * @param  array $postarr Working data
     * @return array
     */
    // public function localizeParentId($data , $postarr)
    // {
    //     if (array_key_exists("ID", $postarr)) {
    //         if (!$this->currentLocale->isDefault()) {

    //             // Check the default locale's post for a parent id. If there's a
    //             // parent id, then try to find if it has a translation. If it does,
    //             // that we've finally found what is the correct parent id.
    //             $translation = $this->defaultLocale->getTranslatedPost($postarr['ID']);
    //             if ($translation && (int)$translation->post_parent > 0) {
    //                 $parentPostTranslation = $this->currentLocale->getTranslatedPost($translation->post_parent);
    //                 if ($parentPostTranslation) {
    //                     $data["post_parent"] = $parentPostTranslation->ID;
    //                 }
    //             }
    //         }
    //     }

    //     return $data;
    // }


    /**
     * @param int $postId The post ID.
     * @param post $post The post object.
     * @param bool $update Whether this is an existing post being updated or not.
     */
    // public function localizePostTerms($postId)
    // {
    //     if (wp_is_post_revision($postId)) {
    //         return;
    //     }

    //     $configuration = Strata::i18n()->getConfiguration();
    //     foreach ($configuration->getEnabledTaxonomies() as $taxonomy) {
    //         foreach (Strata::i18n()->getLocales() as $locale) {

    //             // Clear the terms of all translations and update with either the
    //             // default taxonomy or the localized version.
    //             $translatedPost = $locale->getTranslatedPost($postId);
    //             if ($translatedPost) {
    //                 wp_delete_object_term_relationships($translatedPost->ID, $taxonomy);

    //                 // Assign either the original term as backup or the localized
    //                 // term.
    //                 foreach (wp_get_post_terms($postId, $taxonomy) as $term) {
    //                     if ($term && !array_key_exists('invalid_taxonomy', $term)) {
    //                         $translatedTerm = $locale->getTranslatedTerm($term->term_id, $taxonomy);
    //                         wp_add_object_terms($postId, $translatedTerm ? $translatedTerm->term_id : $term->term_id, $taxonomy);
    //                     }
    //                 }
    //             }
    //         }
    //     }
    // }

}
