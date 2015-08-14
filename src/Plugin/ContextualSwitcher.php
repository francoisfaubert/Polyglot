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
    }

    /**
     * Sets the current locale by post/page id
     * @param int $postId
     * @return Locale
     */
    private function setLocaleByPostId($postId)
    {
        $post = Polyglot::instance()->query()->findPostById($postId);
        if (isset($post->ID)) {
            return $this->setLocaleByObject($post);
        }
    }

    private function setLocaleByTaxonomyId($taxonomyId, $taxonomyType)
    {
        $term = Polyglot::instance()->query()->findTermById($taxonomyId, $taxonomyType);
        if (isset($term->term_id)) {
            return $this->setLocaleByObject($term);
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
