<?php

namespace Polyglot\I18n\Request\Router;

use WP_Term;

class TaxonomyRouter extends PolyglotRouter {

    public function localizeRoute($route = null)
    {
        global $wp_query;

        if ($wp_query->queried_object) {
            $term = $wp_query->queried_object;
            $localizedTerm = $this->currentLocale->getTranslatedTerm($term->term_id, $term->taxonomy);
            $originalTerm = $this->defaultLocale->getTranslatedTerm($term->term_id, $term->taxonomy);
            $route = $this->replaceFirstOccurance($localizedTerm->slug, $originalTerm->slug, $route);
            return $this->replaceFirstOccurance($this->currentLocale->getHomeUrl(false), "/", $route);
        }

        return $route;
    }

}
