<?php

/**
 * Though these functions are made accessible the Wordpress preferred way, it should
 * be noted that Strata would rather want you to have a 'src/View/Helper/I18nHelper' file that would do
 * the heavier modifications.
 */


function get_i18n_permalink($post_ID = null, $locale = null) {
    $translated_post = get_i18n_post_translation($post_ID, $locale);
    if ($translated_post) {
        return get_permalink($translated_post->ID);
    }

    return get_permalink($post_ID);
}


function get_i18n_post_translation($post_ID = null, $locale = null)
{
    if (is_a($post_ID, "WP_Post")) {
        $post_ID = $post_ID->ID;
    } elseif (is_null($post_ID)) {
        $post_ID = get_the_ID();
    }

    if (is_null($locale)) {
        global $polyglot;
        $locale = $polyglot->getCurrentLocale();
    }

    if ($post_ID) {
        return $locale->getTranslatedPost($post_ID);
    }
}
