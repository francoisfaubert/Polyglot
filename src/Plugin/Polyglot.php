<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\Utility\Hash;
use Polyglot\Plugin\Adaptor\WordpressAdaptor;
use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Cache;

use Exception;

use Gettext\Translations;
use Gettext\Translation;

class Polyglot {

    private $locales = array();
    private $cache;

    function __construct()
    {
        $locales = Hash::normalize(Strata::config("i18n.locales"));

        foreach ($locales as $key => $config) {
            $this->locales[$key] = new Locale($key, $config);
        }
    }

    public function hasActiveLocales()
    {
        return count($this->locales) > 0;
    }

    public function getLocales()
    {
        return $this->locales;
    }

    public function getLocaleByCode($code)
    {
        if (array_key_exists($code, $this->locales)) {
            return $this->locales[$code];
        }
    }

    public function getTranslations($localeCode)
    {
        $locale = $this->getLocaleByCode($localeCode);

        if (!$locale->hasPoFile()) {
            throw new Exception("$localeCode is not a supported locale.");
        }

        return Translations::fromPoFile($locale->getPoFilePath());
    }

    public function saveTranslations(Locale $locale, array $postedTranslations)
    {
        $poFile = $locale->getPoFilePath();
        $originalTranslations = Translations::fromPoFile($poFile);
        $newTranslations = new Translations();

        foreach ($postedTranslations as $t) {
            $original = html_entity_decode($t['original']);
            $context = html_entity_decode($t['context']);

            $translation = $originalTranslations->find($context, $original);
            if ($translation === false) {
                $translation = new Translation($context, $original, $t['plural']);
            }

            $translation->setTranslation($t['translation']);
            $translation->setPluralTranslation($t['pluralTranslation']);
            $newTranslations[] = $translation;
        }

        $originalTranslations->mergeWith($newTranslations, Translations::MERGE_HEADERS | Translations::MERGE_COMMENTS | Translations::MERGE_ADD | Translations::MERGE_LANGUAGE);
        $originalTranslations->toPoFile($poFile);
    }

    public function isTypeEnabled($postType)
    {
        return in_array($postType, $this->getEnabledPostTypes());
    }

    public function isTaxonomyEnabled($taxonomy)
    {
        return in_array($taxonomy, $this->getEnabledTaxonomies());
    }

    public function toggleTaxonomy($taxonomy)
    {
        $config = $this->getConfiguration();

        if (!$this->isTaxonomyEnabled($taxonomy)) {
            $config["taxonomies"][] = $taxonomy;
        }
        elseif(($key = array_search($taxonomy, $config["taxonomies"])) !== false) {
            unset($config["taxonomies"][$key]);
        }

        $this->cache["taxonomies"] = $config["taxonomies"];
        $this->updateConfiguration();
    }

    public function togglePostType($postType)
    {
        $config = $this->getConfiguration();

        if (!$this->isTypeEnabled($postType)) {
            $config["post-types"][] = $postType;
        }
        elseif(($key = array_search($postType, $config["post-types"])) !== false) {
            unset($config["post-types"][$key]);
        }

        $this->cache["post-types"] = $config["post-types"];
        $this->updateConfiguration();
    }


    public function getConfiguration()
    {
        if (is_null($this->cache)) {
            $this->cache = get_option("polyglot_configuration", $this->getDefaultConfiguration());
        }

        return $this->cache;
    }

    protected function updateConfiguration()
    {
        return update_option("polyglot_configuration", $this->cache);
    }

    public function getOptions()
    {
        return $this->getConfiguration()['options'];
    }

    public function getEnabledPostTypes()
    {
        return $this->getConfiguration()['post-types'];
    }

    public function getEnabledTaxonomies()
    {
        return $this->getConfiguration()['taxonomies'];
    }

    public function getDefaultConfiguration()
    {
        return array(
            "options" => array(),
            "post-types" => array(),
            "taxonomies" => array()
        );
    }

    public function getPostTypes()
    {
        return get_post_types(array(), "object");
    }

    public function getTaxonomies()
    {
        return get_taxonomies(array(), "objects");
    }

    public function isCurrentlyActive(Locale $locale)
    {
        $current = $this->getCurrentLocale();
        return $locale->getCode() === $current->getCode();
    }

    public function getCurrentLocale()
    {
        $locales = $this->getLocales();
        return array_pop($locales);
    }
}
