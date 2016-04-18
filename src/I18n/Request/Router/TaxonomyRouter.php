<?php

namespace Polyglot\I18n\Request\Router;

use WP_Term;

class TaxonomyRouter extends PolyglotRouter {

    public function localizeRoute($route = null)
    {
        global $wp_query;

        if ($wp_query->queried_object && get_class($wp_query->queried_object) === "WP_Term") {
            return $this->replaceTermLevel($wp_query->queried_object, $route);
        }

        return $route;
    }

    protected function replaceTermLevel(WP_Term $term, $route)
    {
        $localizedTerm = $this->currentLocale->getTranslatedTerm($term->term_id, $term->taxonomy);
        $originalTerm = $this->defaultLocale->getTranslatedTerm($term->term_id, $term->taxonomy);

        $route = $this->replaceFirstOccurance($localizedTerm->slug, $originalTerm->slug, $route);
        $localizedRoute = $this->replaceFirstOccurance($this->currentLocale->getHomeUrl(false), "/", $route);

        if ((int)$originalTerm->parent > 0) {
            $originalParentTerm = $this->defaultLocale->getTranslatedTerm($term->parent, $term->taxonomy);
            $localizedRoute = $this->replaceTermLevel($originalParentTerm, $localizedRoute);
        }

        return $localizedRoute;
    }
}
