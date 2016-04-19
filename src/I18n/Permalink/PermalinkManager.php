<?php

namespace Polyglot\I18n\Permalink;

use Strata\Strata;
use Strata\I18n\I18n;
use Strata\I18n\I18n\Locale;
use Polyglot\I18n\Translation\Tree;

use WP_Post;
use WP_Term;

class PermalinkManager {

    protected $currentLocale;
    protected $defaultLocale;
    protected $shouldLocalizeByFallback;

    public function __construct()
    {
        $i18n = Strata::i18n();
        $this->currentLocale = $i18n->getCurrentLocale();
        $this->defaultLocale = $i18n->getDefaultLocale();
        $this->shouldLocalizeByFallback = !$i18n->currentLocaleIsDefault() && $i18n->shouldFallbackToDefaultLocale();
    }

    public function getLocalizedFallbackUrl($originalUrl, $locale)
    {
        // Remove the possible fake url prefix when fallbacking
        if ((bool)Strata::config("i18n.default_locale_fallback")) {
            return str_replace(Strata::i18n()->getCurrentLocale()->getHomeUrl(), $locale->getHomeUrl(), $originalUrl);
        }

        return str_replace(get_home_url(), $locale->getHomeUrl(), $originalUrl);
    }
}
