<?php

namespace Polyglot\I18n\Translation;

use Strata\Strata;
use Exception;

class TermMetaManager {

    private $isWorking = false;

    private $term;
    private $logger;

    public function filter_onCreateTerm($term_id, $tt_id, $taxonomy)
    {
        if ($this->isWorking) {
            return;
        }

        $this->onTermSave($term_id, $tt_id, $taxonomy);
    }

    public function filter_onEditTerm($term_id, $tt_id, $taxonomy)
    {
        if ($this->isWorking) {
            return;
        }

        $this->onTermSave($term_id, $tt_id, $taxonomy);
    }

    public function onTermSave($term_id, $tt_id, $taxonomy)
    {
        $this->logger = Strata::app()->getLogger();

        if ($this->isAKnownTaxonomy($taxonomy)) {
            $this->term = get_term_by('id', $term_id, $taxonomy);
            $this->distributeFields();
        }
    }

    protected function isAKnownTaxonomy($taxonomy)
    {
        $configuration = Strata::i18n()->getConfiguration();
        return $configuration->isTaxonomyEnabled($taxonomy);
    }

    protected function distributeFields()
    {
        $defaultLocale = Strata::i18n()->getDefaultLocale();
        $original = $defaultLocale->getTranslatedTerm($this->term->term_id, $this->term->taxonomy);

        $this->isWorking = true;

        if ($original) {
            foreach (Strata::i18n()->getLocales() as $locale) {
                if (!$locale->isDefault() && $locale->hasTermTranslation($original->term_id)) {
                    $localization = $locale->getTranslatedTerm($original->term_id, $original->taxonomy);

                    // Ensure we aren't getting a fallback post
                    if ((int)$localization->term_id !== (int)$original->term_id) {
                        wp_update_term($localization->term_id, $localization->taxonomy, array(
                            "parent" => $original->parent
                        ));

                        if ($this->logger) {
                            $this->logger->log(sprintf("Synced term #%s with #%s's parent information.", $localization->term_id, $original->term_id), "<magenta>Polyglot</magenta>");
                        }
                    }
                }
            }
        }

        $this->isWorking = false;
    }
}
