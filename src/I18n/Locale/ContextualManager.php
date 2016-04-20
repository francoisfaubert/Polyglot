<?php

namespace Polyglot\I18n\Locale;

use Strata\Controller\Request;
use Polyglot\I18n\Utility;
use Polyglot\I18n\Translation\Tree;

use Strata\Strata;

/**
 * Looks up existing values in Wordpress' query for
 * clues on finding the locale that should currently be active.
 *
 */
class ContextualManager {

    public function filter_onSetStrataContext()
    {
        $locale = is_admin() ?
            $this->getByAdminContext() :
            $this->getByFrontContext();

        if (!is_null($locale)) {
            Strata::i18n()->setLocale($locale);
        }
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

        if ($request->isPost() && $request->hasPost("post_ID")) {
            return $this->getLocaleByPostId($request->post("post_ID"));
        }

        if ($request->hasGet("taxonomy") && $request->hasGet("tag_ID")) {
            return $this->getLocaleByTaxonomyId($request->get("tag_ID"), $request->get("taxonomy"));
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
        $postId = get_the_ID();
        if ($postId) {
            return $this->getLocaleByPostId($postId);
        }

        if (preg_match('/^('. Utility::getLocaleUrlsRegex() .')/', $_SERVER['REQUEST_URI'])) {
            return $locale;
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
