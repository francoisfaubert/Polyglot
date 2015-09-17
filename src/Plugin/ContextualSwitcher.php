<?php

namespace Polyglot\Plugin;

use Strata\Controller\Request;

use Polyglot\Plugin\Polyglot;
use Exception;

/**
 * Looks up existing values in Wordpress' query for
 * clues on finding the locale that should currently be active.
 *
 */
class ContextualSwitcher {

    /**
     * Registers required hook to set the locale by context
     * @see WordpressAdaptor#addGlobalCallbacks()
     */
    public function registerHooks()
    {
        add_filter('strata_i18n_set_current_locale_by_context', array($this, "setCurrentLocaleByContext"));
    }

    public function setCurrentLocaleByContext()
    {
        return is_admin() ? $this->setCurrentLocaleByAdminContext() : $this->setCurrentLocaleByFrontContext();
    }

    /**
     * Triggered in the backend to learn the locale based on the type
     * of object the user is browsing. Prevents a user from seeing
     * a object in another locale than the one the object is supposed to be in.
     * @return Locale
     */
    public function setCurrentLocaleByAdminContext()
    {
        $request = new Request();
        if ($request->hasGet("post") && !is_array($request->get("post"))) { // trashing posts will generate an array
            return $this->setLocaleByPostId($request->get("post"));
        }

        if ($request->hasGet("taxonomy") && $request->hasGet("tag_ID")) {
            return $this->setLocaleByTaxonomyId($request->get("tag_ID"), $request->get("taxonomy"));
        }
    }

    /**
     * Triggered in the front end to learn the locale based on the type
     * of object the user is browsing. Prevents a user from seeing
     * a object in another locale than the one the object is supposed to be in.
     * @return Locale
     */
    public function setCurrentLocaleByFrontContext()
    {
        $postId = get_the_ID();
        if ($postId) {
            return $this->setLocaleByPostId($postId);
        }

        $instance = Polyglot::instance();
        foreach ($instance->getLocales() as $locale) {
            $regexed = preg_quote($locale->getCode(), '/');
            if (preg_match('/^\/(index.php\/)?'.$regexed.'/', $_SERVER['REQUEST_URI'])) {
                $instance->setLocale($locale);
                return $locale;
            }
        }
    }

    /**
     * Sets the current locale by post/page id
     * @param int $postId
     * @return Locale
     */
    private function setLocaleByPostId($postId)
    {
        $tree = $this->getTranslationTree($postId);
        if ($tree && $tree->hasTranslatedObject($postId, "WP_Post")) {
            return $this->setLocaleByObject($tree->getTranslatedObject($postId, "WP_Post"));
        }
    }

    private function setLocaleByTaxonomyId($taxonomyId, $taxonomyType)
    {
        $tree = $this->getTranslationTree($taxonomyId, "Term");
        if ($tree && $tree->hasTranslatedObject($taxonomyId, "Term")) {
            return $this->setLocaleByObject($tree->getTranslatedObject($taxonomyId, "Term"));
        }
    }

    /**
     * Sets the locale by Wordpress object.
     * @param TranslationEntity $mixed
     * @return Locale
     */
    private function setLocaleByObject($mixed)
    {
        return Polyglot::instance()->getLocaleByCode($mixed->translation_locale);
    }

    private function getTranslationTree($mixedId, $mixedKind = "WP_Post")
    {
        $originalId = $this->getOriginalObjectId($mixedId, $mixedKind);
        return Polyglot::instance()->query()->findTranlationsOfId($originalId, $mixedKind);
    }

    private function getOriginalObjectId($mixedId, $mixedKind)
    {
        $localizedDetails = Polyglot::instance()->query()->findDetailsById($mixedId, $mixedKind);
        if ($localizedDetails && !is_null($localizedDetails->translation_of)) {
            return (int)$localizedDetails->translation_of;
        }

        // Assume this object is the original since it had no translation
        return (int)$mixedId;
    }

}
