<?php

namespace Polyglot\I18n\Permalink;

use Strata\Strata;
use Strata\I18n\I18n;
use Strata\I18n\I18n\Locale;
use Strata\Router\Router;

use Polyglot\I18n\Locale\ContextualManager;
use Polyglot\I18n\Translation\Tree;
use Polyglot\I18n\Utility;

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

    public function enforceLocale($locale = null)
    {
         // The current locale gets lost in metabox queries.
        // // in the admin
        if (is_null($locale) && is_admin() && !Router::isAjax()) {
            $context = new ContextualManager();
            $locale = $context->getByAdminContext();
        }

        if (!is_null($locale)) {
            $this->currentLocale = $locale;
        }
    }
}
