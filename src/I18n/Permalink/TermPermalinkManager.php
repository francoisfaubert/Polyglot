<?php

namespace Polyglot\I18n\Permalink;

use Strata\Strata;
use Polyglot\I18n\Utility;
use Strata\Utility\Hash;
use WP_Term;

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
            return $this->generatePermalink($url, $taxonomy);
        }

        return $url;
    }

    public function enforceLocale($locale = null)
    {
        if (!is_null($locale)) {
            $this->currentLocale = $locale;
        }
    }

    public function generatePermalink($url, $taxonomy)
    {
        $taxonomyDetails = get_taxonomy($taxonomy);
        if ($taxonomyDetails) {
            if ($this->taxonomyWasLocalizedInStrata($taxonomy)) {
                $url = $this->replaceLocalizedTaxonomySlug($url, $taxonomyDetails);
            }
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


    private function replaceDefaultTaxonomySlug($url, $taxonomyDetails)
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
