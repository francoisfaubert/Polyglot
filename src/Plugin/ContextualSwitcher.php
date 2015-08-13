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
        if (is_admin()) {
            add_action('admin_init', array($this, "setCurrentLocaleByAdminContext"));
        }

        add_action('wp', array($this, "setCurrentLocaleByFrontContext"));
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
        if ($request->hasGet("post")) {
            return $this->setLocaleByPostId($request->get("post"));
        }

        if ($request->hasGet("taxonomy") && $request->hasGet("tag_ID")) {
            return $this->setLocaleByTaxonomyId($request->get("taxonomy"), $request->get("tag_ID"));
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
    }

    /**
     * Sets the current locale by post/page id
     * @param int $postId
     * @return Locale
     */
    private function setLocaleByPostId($postId)
    {
        $post = Polyglot::instance()->query()->findPostById($postId);
        return $this->setLocaleByObject($post);
    }

    private function setLocaleByTaxonomyId($taxonomyType, $taxonomyId)
    {
        $taxonomies = Polyglot::instance()->findTaxonomyById($taxonomyType, $taxonomyId);

        if (count($taxonomies)) {
            // Always build using the first taxonomy in the array. It would be too
            // resource exhaustive to validate against every possible return.
            return $this->setLocaleByObject($taxonomies[0]);
        }
    }

    /**
     * Sets the locale by Wordpress object.
     * @param mixed $mixed WP_Post, stdClass
     * @return Locale
     */
    private function setLocaleByObject($mixed)
    {
        $instance = Polyglot::instance();
        $locale = $instance->query()->findObjectLocale($mixed);
        if (!is_null($locale)) {
            $instance->setLocale($locale);
            return $locale;
        }
    }

}
