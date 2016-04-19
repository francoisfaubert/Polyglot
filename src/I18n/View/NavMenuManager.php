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
            $currentPageId = (int)get_the_ID();

            foreach ($sortedMenuItems as $wpPost) {
                if (is_a($wpPost, '\WP_Post')) {
                    if ($this->currentLocale->hasPostTranslation($wpPost->object_id)) {

                        $translatedInfo = $this->currentLocale->getTranslatedPost($wpPost->object_id);
                        $defaultInfo = $this->defaultLocale->getTranslatedPost($wpPost->object_id);

                        // The title isn't carried away, if it matches the post title,
                        // then use the translation. Otherwise, pass it along gettext

                        if ($defaultInfo->post_title === $wpPost->title) {
                            $sortedMenuItems[$count]->title = $translatedInfo->post_title;
                        } else {
                            $sortedMenuItems[$count]->title = __($sortedMenuItems[$count]->title, $this->textdomain);
                        }

                        $sortedMenuItems[$count]->url = get_permalink($translatedInfo->ID);

                        // Because we don't want to lose the added menu data of the previous item,
                        // replace every matching key from this translation.
                        foreach ($translatedInfo as $key => $data) {
                            $sortedMenuItems[$count]->{$key} = $data;
                        }

                        if ($currentPageId === (int)$translatedInfo->ID) {
                            $sortedMenuItems[$count]->current = true;
                            $sortedMenuItems[$count]->classes[] = "active";
                        }
                    }
                }
                $count++;
            }
        }

        return $sortedMenuItems;
    }
}
