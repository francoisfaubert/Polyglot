<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\Utility\Hash;
use Strata\Controller\Request;

use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Db\Query;

use Exception;

/**
 * Polyglot extends the default I18n class to add translation support
 * of dynamic objects. Otherwise I18n would only translate strings.
 */
class Polyglot extends \Strata\I18n\I18n {

    /**
     * Returns the active Polyglot instance.
     * @return Polyglot
     */
    public static function instance()
    {
        // At least this creates an elegant bridge between WP and OO programming.
        global $polyglot;
        return is_null($polyglot) ? new self() : $polyglot;
    }

    protected $query = null;
    protected $configuration = null;
    protected $localized = false;

    function __construct()
    {
        $this->throwIfGlobalExists();
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

    /**
     * Returns the list of Locale objects
     * @return array
     */
    public function getLocales()
    {
        if (!$this->localized) {
            $app = Strata::app();
            $orignalLocale = $app->i18n->getCurrentLocale();

            $this->locales = $this->rebuildLocaleList();
            $this->localized = true;

            // Find our version of the original locale and
            // reassign it.
            if ($orignalLocale) {
                $this->setLocale($this->getLocaleByCode($orignalLocale->getCode()));
            }
        }
        return $this->locales;
    }

    /**
     * Overrides the default i18n function in order to use
     * our custom Locale object and have our own set of functions.
     * @return array
     */
    protected function rebuildLocaleList()
    {
        $original = Hash::normalize(Strata::config("i18n.locales"));
        $newLocales = array();

        foreach ($original as $key => $config) {
            $newLocales[$key] = new Locale($key, $config);
        }

        return $newLocales;
    }

    /**
     * If there are multiple instances of Polyglot running at the same time,
     * an exception should be raised.
     * @throws Exception
     */
    private function throwIfGlobalExists()
    {
        /**
         *  Hello,
         *
         *  If this exception is an hindrance to you, please go to our GitHub and
         *  explain what you which to accomplish by creating a second instance of
         *  the Polyglot object.
         *
         *  I am writing this 'throw' early in the life of the plugin and I am still on the
         *  fence on whether it should exist.
         *
         *  I am adding the 'throw' because I think it would slow the website to allow multiple
         *  instances of Polyglot that maintain their own separate caches. I would
         *  rather have a convenient list of API methods available on the global $polyglot object.
         *
         *  That's the idea anyways.
         *  Cheers,
         *
         *  - Frank.
         */
        global $polyglot;
        if (!is_null($polyglot)) {
            throw new Exception("There should only be one active reference to Polyglot. Please use global \$polyglot to get the instance.");
        }
    }
}
