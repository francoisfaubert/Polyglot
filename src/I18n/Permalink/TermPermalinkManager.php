<?php

namespace Polyglot\I18n\Permalink;

use Strata\Strata;
use Polyglot\I18n\Utility;
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
            if ($this->currentLocale->hasACustomUrl()) {
                $taxonomyDetails = get_taxonomy($taxonomy);
                $taxonomyRootSlug = $taxonomyDetails->rewrite['slug'];
                return Utility::replaceFirstOccurence(
                    '/' . $taxonomyRootSlug,
                    $this->currentLocale->getHomeUrl(false) . $taxonomyRootSlug,
                    $url
                );
            }
        }

        return $termLink;
    }
}
