<?php

namespace Polyglot\I18n\Translation;

use Strata\Strata;
use Exception;

class PostMetaManager {

    private $supportedTypes;
    private $post;

    public function filter_onSavePost($postId)
    {
        if ($this->isAKnownPost($postId)) {
            $this->distributeMenuOrder();
        }
    }

    protected function isAKnownPost($postId = null)
    {
        $this->post = get_post($postId);

        if (wp_is_post_revision($this->post->ID)) {
            return false;
        }

        $postType = get_post_type($this->post->post_type);
        $configuration = Strata::i18n()->getConfiguration();

        return $configuration->isTypeEnabled($postType);
    }

    protected function distributeMenuOrder()
    {
        foreach (Strata::i18n()->getLocales() as $locale) {
            if ($locale->hasPostTranslation($this->post->ID)) {
                $localization = $locale->getTranslatedPost($this->post->ID);
                $localization->menu_order = $this->post->menu_order;
                wp_update_post($localization);
            }
        }
    }

    protected function distributeTemplate()
    {
        foreach (Strata::i18n()->getLocales() as $locale) {
            if ($locale->hasPostTranslation($this->post->ID)) {

                $originalTemplate = get_post_meta($this->post->ID, '_wp_page_template', true);
                if (!empty($originalTemplate)) {
                    update_post_meta($localization->ID, '_wp_page_template', $originalTemplate);
                }
            }
        }
    }
}
