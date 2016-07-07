<?php

namespace Polyglot\I18n\View;

use Strata\Strata;

class NavMenuManager {

    private $currentLocale;
    private $defaultLocale;
    private $textdomain;

    public function __construct()
    {
        $i18n = Strata::i18n();
        $this->currentLocale = $i18n->getCurrentLocale();
        $this->defaultLocale = $i18n->getDefaultLocale();
        $this->textdomain = $i18n->getTextdomain();
    }

    public function filter_onNavMenuObjects($sortedMenuItems, $args)
    {
        if (!$this->currentLocale->isDefault()) {
            $count = 1; // it really does start at 1...


            foreach ($sortedMenuItems as $wpObject) {
                if (is_a($wpObject, '\WP_Post')) {
                    if ($wpObject->type === "post_type") {
                        if ($this->currentLocale->hasPostTranslation($wpObject->object_id)) {
                            $sortedMenuItems[$count] = $this->updateFromPostTitle($sortedMenuItems[$count], $wpObject);
                        }
                    } elseif ($wpObject->type === "taxonomy") {
                        if ($this->currentLocale->hasTermTranslation($wpObject->object_id)) {
                            $sortedMenuItems[$count] = $this->updateFromTermTitle($sortedMenuItems[$count], $wpObject);
                        }
                    }
                }
                $count++;
            }
        }

        return $sortedMenuItems;
    }

    protected function updateFromPostTitle($menuItem, $wpPost)
    {
        $translatedInfo = $this->currentLocale->getTranslatedPost($wpPost->object_id);
        $defaultInfo = $this->defaultLocale->getTranslatedPost($wpPost->object_id);

        // The title isn't carried away, if it matches the post title,
        // then use the translation. Otherwise, pass it along gettext
        if ($defaultInfo->post_title === htmlspecialchars_decode($wpPost->title)) {
            $menuItem->title = $translatedInfo->post_title;
        } else {
            $menuItem->title = __($menuItem->title, $this->textdomain);
        }

        $menuItem->url = get_permalink($translatedInfo->ID);

        // Because we don't want to lose the added menu data of the previous item,
        // replace every matching key from this translation.
        foreach ($translatedInfo as $key => $data) {
            $menuItem->{$key} = $data;
        }

        if ((int)get_the_ID() === (int)$translatedInfo->ID) {
            $menuItem->current = true;
            $menuItem->classes[] = "current-menu-item";
        }

        return $menuItem;
    }

    protected function updateFromTermTitle($menuItem, $wpPost)
    {
        $translatedInfo = $this->currentLocale->getTranslatedTerm($wpPost->object_id, $wpPost->object);
        $defaultInfo = $this->defaultLocale->getTranslatedTerm($wpPost->object_id, $wpPost->object);

        // The title isn't carried away, if it matches the post title,
        // then use the translation. Otherwise, pass it along gettext
        if ($defaultInfo->name === htmlspecialchars_decode($wpPost->title)) {
            $menuItem->title = $translatedInfo->name;
        } else {
            $menuItem->title = __($menuItem->title, $this->textdomain);
        }

        $menuItem->url = get_term_link($translatedInfo->term_id, $wpPost->object);

        // Because we don't want to lose the added menu data of the previous item,
        // replace every matching key from this translation.
        foreach ($translatedInfo as $key => $data) {
            $menuItem->{$key} = $data;
        }

        return $menuItem;
    }
}
