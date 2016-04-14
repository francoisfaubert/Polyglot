<?php

namespace Polyglot\I18n\Request\Rewriter;

use Strata\Strata;
use Strata\Model\Taxonomy\Taxonomy;
use Exception;

/**
 * Adds the basic rules for pointing the default locale
 * directory to the translated version of the homepage.
 */
class HomepageRewriter extends PolyglotRewriter {

    private $homepageId;

    public function setDefaultHomepageId($id)
    {
        $this->homepageId = $id;
    }

    public function rewrite()
    {
        foreach ($this->i18n->getLocales() as $locale) {
            if ($locale->hasACustomUrl()) {
                $localizedPage = $locale->getTranslatedPost($this->homepageId);
                if (is_null($localizedPage) && $this->shouldFallbackToDefault()) {
                    $localizedPage = $this->defaultLocale->getTranslatedPost($this->homepageId);
                }

                if (!is_null($localizedPage)) {
                    $pagename = $localizedPage->post_name;
                    $url = $locale->getUrl();
                    $this->rewriter->addRule("$url/?$", "index.php?pagename=$pagename");
                }
            }
        }
    }

    private function shouldFallbackToDefault()
    {
        return (bool)Strata::config("i18n.default_locale_fallback");
    }
}
