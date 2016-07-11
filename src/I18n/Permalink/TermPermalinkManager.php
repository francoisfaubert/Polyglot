<?php

namespace Polyglot\I18n\Permalink;

use Strata\Strata;
use Polyglot\I18n\Translation\Tree;
use Polyglot\I18n\Utility;
use Strata\Utility\Hash;
use WP_Term;
use WP_Error;

class TermPermalinkManager extends PermalinkManager {

     /**
     * Returns the default term link to add the current locale prefix
     * to the generated link, if applicable.
     * @param  string $url
     * @param  WP_Term $term
     * @param  string $taxonomy
     * @return string
     */
    public function filter_onTermLink($url, WP_Term $term, $taxonomy)
    {
        $configuration = Strata::i18n()->getConfiguration();
        if ($configuration->isTaxonomyEnabled($taxonomy)) {
            return $this->generatePermalink($url, $term, $taxonomy);
        }

        return $url;
    }

    public function enforceLocale($locale = null)
    {
        if (!is_null($locale)) {
            $this->currentLocale = $locale;
        }
    }

    public function generatePermalink($url, $term, $taxonomy)
    {
        $url = $this->localizeTermSlug($url, $term);

        $taxonomyDetails = get_taxonomy($taxonomy);
        if ($taxonomyDetails && $this->taxonomyWasLocalizedInStrata($taxonomy)) {
            $url = $this->replaceParentTaxonomySlug($url, $term);
            $url = $this->replaceLocalizedTaxonomySlug($url, $taxonomyDetails);
            $url = $this->replaceDefaultTaxonomySlug($url, $taxonomyDetails);
        }

        if ($this->currentLocale->hasACustomUrl()) {
            $url = $this->replaceLocaleHomeUrl($url);
        }

        return $url;
    }

    private function replaceLocaleHomeUrl($permalink)
    {
        if (preg_match('/' . Utility::getLocaleUrlsRegex() . '/', $permalink)) {
            $permalink = preg_replace(
                '#(/(' . Utility::getLocaleUrlsRegex() . ')/)#',
                '/',
                $permalink
            );
        }

        if ($this->currentLocale->hasACustomUrl()) {
            return Utility::replaceFirstOccurence(
                get_home_url() . '/',
                $this->currentLocale->getHomeUrl(),
                $permalink
            );
        }
    }

    private function taxonomyWasLocalizedInStrata($wordpressKey)
    {
        return !is_null(Strata::config("runtime.taxonomy.query_vars.$wordpressKey"));
    }

    private function localizeTermSlug($permalink, $termAttemptingToTranslate)
    {
        $translation = null;
        if ($this->currentLocale->hasTermTranslation($termAttemptingToTranslate->term_id)) {
            $translation = $this->currentLocale->getTranslatedTerm($termAttemptingToTranslate->term_id, $termAttemptingToTranslate->taxonomy);
        } elseif($this->shouldLocalizeByFallback) {
            $translation = $this->defaultLocale->getTranslatedTerm($termAttemptingToTranslate->term_id, $termAttemptingToTranslate->taxonomy);
        }

        if (!is_null($translation)) {
            return Utility::replaceFirstOccurence(
                '/' .  $termAttemptingToTranslate->slug . '/',
                '/' . $translation->slug . '/',
                $permalink
            );
        }

        return $permalink;
    }

    private function replaceParentTaxonomySlug($permalink, $term)
    {
        if ((int)$term->parent > 0) {
            $pointer = get_term($term->parent, $term->taxonomy);
            if (!is_a($pointer, 'WP_Error')) {
                $permalink = $this->localizeTermSlug($permalink, $pointer);
                return $this->replaceParentTaxonomySlug($permalink, $pointer);
            }
        }
        return $permalink;
    }

    private function replaceDefaultTaxonomySlug($url, $taxonomyDetails)
    {
        $localizedSlugs = Hash::extract((array)$taxonomyDetails->i18n, "{s}.rewrite.slug");
        foreach ($localizedSlugs as $slug) {
            $url = Utility::replaceFirstOccurence(
                $slug,
                $taxonomyDetails->rewrite['slug'],
                $url
            );
        }

        if (!$this->currentLocale->isDefault()) {
            $localeCode = $this->currentLocale->getCode();
            if (Hash::check((array)$taxonomyDetails->i18n, "$localeCode.rewrite.slug")) {
                return Utility::replaceFirstOccurence(
                    $taxonomyDetails->rewrite['slug'],
                    Hash::get($taxonomyDetails->i18n, "$localeCode.rewrite.slug"),
                    $url
                );
            }
        }

        return $url;
    }

    private function replaceLocalizedTaxonomySlug($url, $taxonomyDetails)
    {
        $localeCode = $this->currentLocale->getCode();

        if (Hash::check((array)$taxonomyDetails->i18n, "$localeCode.rewrite.slug")) {
            return Utility::replaceFirstOccurence(
                $taxonomyDetails->rewrite['slug'],
                Hash::get($taxonomyDetails->i18n, "$localeCode.rewrite.slug"),
                $url
            );
        }

        return $url;
    }

}
