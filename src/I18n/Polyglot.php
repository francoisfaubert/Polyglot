<?php

namespace Polyglot\I18n;

use Strata\Strata;
use Strata\Utility\Hash;
use Strata\Controller\Request;

use Polyglot\I18n\Locale\Locale;
use Polyglot\I18n\Db\Query;

use Exception;

/**
 * Polyglot extends the default I18n class to add translation support
 * of dynamic objects. Otherwise I18n would only translate strings.
 */
class Polyglot extends \Strata\I18n\I18n {

    protected $query = null;
    protected $configuration = null;
    protected $localized = false;

    function __construct()
    {
        $this->stealI18nInformation();
    }

    public function shouldFallbackToDefaultLocale()
    {
        return (bool)Strata::config("i18n.default_locale_fallback");
    }

    /**
     * Returns a object than handles and caches queries
     * @return Query
     */
    public function query()
    {
        if (is_null($this->query)) {
            $this->query = new Query($this);
        }

        return $this->query;
    }

    /**
     * Returns an object that maps all the configuration values
     * @return Configuration
     */
    public function getConfiguration()
    {
        if (is_null($this->configuration)) {
            $this->configuration = new Configuration();
        }

        return $this->configuration;
    }

    protected function stealI18nInformation()
    {
        if (!$this->localized) {
            $app = Strata::app();
            if ($app->hasConfig("i18n.locales")) {
                $orignalLocaleCode = $app->i18n->getCurrentLocaleCode();
                $this->locales = $this->rebuildLocaleList();
                $this->localized = true;

                // Find our version of the original locale and
                // reassign it.
                if ($orignalLocaleCode) {
                    $this->setLocale($this->getLocaleByCode($orignalLocaleCode));
                } elseif (is_null($this->setCurrentLocaleByContext())) {
                    $this->setLocale($this->getDefaultLocale());
                }

                $app->i18n = $this;
            }
        }
    }


    /**
     * Overrides the default i18n function in order to use
     * our custom Locale object and have our own set of functions.
     * @return array
     */
    protected function rebuildLocaleList()
    {
        $app = Strata::app();
        $original = Hash::normalize($app->getConfig("i18n.locales"));
        $newLocales = array();

        foreach ($original as $key => $config) {
            $newLocales[$key] = new Locale($key, $config);
        }

        return $newLocales;
    }
}
