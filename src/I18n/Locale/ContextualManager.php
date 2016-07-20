<?php

namespace Polyglot\I18n\Locale;

use Polyglot\I18n\Utility;
use Polyglot\I18n\Translation\Tree;

use Strata\Strata;
use Strata\Router\Router;
use Strata\Controller\Request;

/**
 * Looks up existing values in Wordpress' query for
 * clues on finding the locale that should currently be active.
 *
 */
class ContextualManager {

    public function filter_onSetStrataContext($locale)
    {
        if (is_null($locale)) {
            return is_admin() ?
                $this->getByAdminContext() :
                $this->getByFrontContext();
        }

        return $locale;
    }

    /**
     * Declares the query parameter for the locale.
     * @see query_vars
     * @param array $qv
     * @return array
     */
    public function filter_onQueryVars($qv)
    {
        $qv[] = 'locale';
        return $qv;
    }

    /**
     * Triggered in the backend to learn the locale based on the type
     * of object the user is browsing. Prevents a user from seeing
     * a object in another locale than the one the object is supposed to be in.
     * @return \Strata\i18n\Locale
     */
    public function getByAdminContext()
    {
        $request = new Request();

        if ($request->hasGet("post") && !is_array($request->get("post"))) { // trashing posts will generate an array
            return $this->getLocaleByPostId($request->get("post"));
        }

        if (!Router::isAjax() && $request->isPost() && $request->hasPost("post_ID")) {
            return $this->getLocaleByPostId($request->post("post_ID"));
        }

        if ($request->hasGet("taxonomy") && $request->hasGet("tag_ID")) {
            return $this->getLocaleByTaxonomyId($request->get("tag_ID"), $request->get("taxonomy"));
        }

        if ($request->hasGet("taxonomy") && !$request->hasGet("locale")) {
            return Strata::i18n()->getDefaultLocale();
        }

        if ($request->hasGet("post_type") && !$request->hasGet("locale")) {
            return Strata::i18n()->getDefaultLocale();
        }
    }

    /**
     * Triggered in the front end to learn the locale based on the type
     * of object the user is browsing. Prevents a user from seeing
     * a object in another locale than the one the object is supposed to be in.
     * @return \Strata\i18n\Locale
     */
    public function getByFrontContext()
    {
        $i18n = Strata::i18n();
        $defaultLocale = $i18n->getDefaultLocale();

        if (!is_search() && !is_404()) {
            // By Post
            $postId = get_the_ID();
            if ($postId) {
                $suspectedLocale = $this->getLocaleByPostId($postId);
                if ($suspectedLocale && !$suspectedLocale->isDefault() && $i18n->shouldFallbackToDefaultLocale()) {
                    $defaultPost = $defaultLocale->getTranslatedPost($postId);
                    $localizedPost = $suspectedLocale->getTranslatedPost($postId);

                    if ($localizedPost && $defaultPost) {
                        if ((int)$defaultPost->ID !== (int)$localizedPost->ID) {
                            return $suspectedLocale;
                        }
                    }
                }
            }

            // By Taxonomy
            global $wp_query;
            if ($wp_query) {
                $taxonomy = $wp_query->queried_object;
                if (is_a($taxonomy, "WP_Term")) {
                    $suspectedLocale = $this->getLocaleByTaxonomyId($taxonomy->term_id, $taxonomy->taxonomy);
                    if ($suspectedLocale && !$suspectedLocale->isDefault() && $i18n->shouldFallbackToDefaultLocale()) {
                        $defaultPost = $defaultLocale->getTranslatedTerm($taxonomy->term_id, $taxonomy->taxonomy);
                        $localizedPost = $suspectedLocale->getTranslatedTerm($taxonomy->term_id, $taxonomy->taxonomy);

                        if ($localizedPost && $defaultPost) {
                            if ((int)$defaultPost->term_id !== (int)$localizedPost->term_id) {
                                return $suspectedLocale;
                            }
                        }
                    }
                }
            }
        }

        if (preg_match('#/('. Utility::getLocaleUrlsRegex() .')/#', $_SERVER['REQUEST_URI'], $matches)) {
            return Strata::i18n()->getLocaleByUrl($matches[1]);
        }
    }

    /**
     * Sets the current locale by post/page id
     * @param int $postId
     * @return \Strata\i18n\Locale
     */
    private function getLocaleByPostId($postId)
    {
        $tree = Tree::grow($postId, "WP_Post");

        if ($tree->isLocalized()) {
            return $tree->getLocalizationLocaleById($postId);
        }
    }

    /**
     * [setLocaleByTaxonomyId description]
     * @param int $taxonomyId
     * @param string $taxonomyType
     * @return \Strata\i18n\Locale
     */
    private function getLocaleByTaxonomyId($taxonomyId, $taxonomyType)
    {
        $tree = Tree::grow($taxonomyId, "Term");
        if ($tree->isLocalized()) {
            return $tree->getLocalizationLocaleById($taxonomyId);
        }
    }
}
