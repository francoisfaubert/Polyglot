<?php

namespace Polyglot\I18n\Request\Router;

use Polyglot\I18n\Utility;
use Strata\Utility\Hash;
use Strata\Strata;
use Strata\Model\Taxonomy\Taxonomy;
use WP_Term;

class TaxonomyRouter extends PolyglotRouter {

    public function localizeRoute($route = null)
    {
        global $wp_query;

        if ((bool)$wp_query->is_tax) {
            $term = get_term_by("slug", $wp_query->query_vars['term'], $wp_query->query_vars['taxonomy']);
            return $this->replaceTermLevel($term, $route);
        }

        if ($wp_query->queried_object && get_class($wp_query->queried_object) === "WP_Term") {
            return $this->replaceTermLevel($wp_query->queried_object, $route);
        }

        return $route;
    }

    protected function getModelEntityByString($taxonomy)
    {
        try {
            return Taxonomy::factoryFromKey($taxonomy);
        } catch (Exception $e) {
            // don't care, not a strata model.
        }
    }

    protected function replaceTermLevel(WP_Term $term, $route)
    {
        $localizedTerm = $this->currentLocale->getTranslatedTerm($term->term_id, $term->taxonomy);
        $localizedRoute = $route;

        if (!is_null($localizedTerm)) {
            $originalTerm = $this->defaultLocale->getTranslatedTerm($term->term_id, $term->taxonomy);
            $localizedRoute = Utility::replaceFirstOccurence($localizedTerm->slug, $originalTerm->slug, $localizedRoute);
        }

        // Translate up the tree
        if ((int)$originalTerm->parent > 0) {
            $originalParentTerm = $this->defaultLocale->getTranslatedTerm($term->parent, $term->taxonomy);
            $localizedRoute = $this->replaceTermLevel($originalParentTerm, $localizedRoute);
        }

        if ($this->taxonomyWasLocalizedInStrata($term->taxonomy)) {
            $localizedRoute = $this->replaceDefaultTaxonomySlug($localizedRoute, get_taxonomy($term->taxonomy));
        }

        $localizedRoute = $this->removeLocalizedRoutedSlugs($localizedRoute, $this->getModelEntityByString($term->taxonomy));

        // Remove the locale code
        return Utility::replaceFirstOccurence($this->currentLocale->getHomeUrl(false), "/", $localizedRoute);
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
                Hash::get($taxonomyDetails->i18n, "$localeCode.rewrite.slug"),
                $taxonomyDetails->rewrite['slug'],
                $url
            );
        }

        return $url;
    }
}
