<?php

namespace Polyglot\I18n\Translation;

use Strata\Strata;
use Exception;

class PostMetaManager {

    private $isWorking = false;
    private $post;
    private $logger;

    public function filter_onSavePost($postId)
    {
        if (!$this->isWorking) {
            $this->logger = Strata::app()->getLogger();
            if ($this->isAKnownPost($postId) && Strata::i18n()->currentLocaleIsDefault()) {
                $this->isWorking = true;
                $this->distributePostFields();
                $this->distributeTemplate();
            }

            $this->isWorking = false;
        }
    }

    protected function isAKnownPost($postId = null)
    {
        if (wp_is_post_revision($postId)) {
            return false;
        }

        $this->post = get_post($postId);

        if (!$this->post || $this->post->post_status === "trash")  {
            return false;
        }

        $configuration = Strata::i18n()->getConfiguration();
        return $configuration->isTypeEnabled($this->post->post_type);
    }

    protected function distributePostFields()
    {
        foreach (Strata::i18n()->getLocales() as $locale) {
            if (!$locale->isDefault() && $locale->hasPostTranslation($this->post->ID)) {

                $localization = $locale->getTranslatedPost($this->post->ID);
                if ((int)$localization->ID !== (int)$this->post->ID) {

                    $parentPostId = $this->post->post_parent;
                    if ($locale->hasPostTranslation($parentPostId)) {
                        $parentTranslation = $locale->getTranslatedPost($parentPostId);
                        $parentPostId = $parentTranslation->ID;
                    }

                    $newData = array(
                        "ID" => $localization->ID,
                        "menu_order" => $this->post->menu_order,
                        "post_parent" => $parentPostId,
                    );

                    if ($this->logger) {
                        $this->logger->log(sprintf("Synced post #%s with #%s's post_parent and menu_order.", $localization->ID, $this->post->ID), "<magenta>Polyglot</magenta>");
                    }

                    wp_update_post($newData);
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
