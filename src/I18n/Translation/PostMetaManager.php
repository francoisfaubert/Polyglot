<?php

namespace Polyglot\I18n\Translation;

use Strata\Strata;
use Exception;

class PostMetaManager {

    private $post;
    private $logger;

    public function filter_onSavePost($postId)
    {
        $this->logger = Strata::app()->getLogger();

        if ($this->isAKnownPost($postId)) {
            $this->distributePostFields();
            $this->distributeTemplate();
        }
    }

    protected function isAKnownPost($postId = null)
    {
        if (wp_is_post_revision($postId)) {
            return false;
        }

        $this->post = get_post($postId);

        $configuration = Strata::i18n()->getConfiguration();
        return $configuration->isTypeEnabled($this->post->post_type);
    }

    protected function distributePostFields()
    {
        foreach (Strata::i18n()->getLocales() as $locale) {
            if (!$locale->isDefault() && $locale->hasPostTranslation($this->post->ID)) {

                $localization = $locale->getTranslatedPost($this->post->ID);
                if ((int)$localization->ID !== (int)$this->post->ID) {
                    $localization->menu_order = $this->post->menu_order;

                    $parentPostId = $this->post->post_parent;
                    if ($locale->hasPostTranslation($parentPostId)) {
                        $parentTranslation = $locale->getTranslatedPost($parentPostId);
                        $parentPostId = $parentTranslation->ID;
                    }

                    $localization->post_parent = $parentPostId;

                    if ($this->logger) {
                        $this->logger->log(sprintf("Synced post #%s with #%s's post_parent and menu_order.", $localization->ID, $this->post->ID), "<magenta>Polyglot</magenta>");
                    }

                    wp_update_post($localization);
                }
            }
        }
    }

    protected function distributeTemplate()
    {
        foreach (Strata::i18n()->getLocales() as $locale) {
            if (!$locale->isDefault() && $locale->hasPostTranslation($this->post->ID)) {

                $originalTemplate = get_post_meta($this->post->ID, '_wp_page_template', true);

                if (!empty($originalTemplate)) {
                    $localization = $locale->getTranslatedPost($this->post->ID);

                    if ((int)$localization->ID !== (int)$this->post->ID) {
                        update_post_meta($localization->ID, '_wp_page_template', $originalTemplate);

                        if ($this->logger) {
                            $this->logger->log(sprintf("Synced post #%s with #%s's template.", $localization->ID, $this->post->ID), "<magenta>Polyglot</magenta>");
                        }
                    }
                }
            }
        }
    }
}
