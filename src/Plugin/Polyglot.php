<?php

namespace Polyglot\Plugin;

use Polyglot\Plugin\Adaptor\WordpressAdaptor;
use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Cache;

use Exception;

class Polyglot {

    const TEXT_DOMAIN = 'polyglot';

    protected $cache = null;


    public function getTextDomain()
    {
        return self::TEXT_DOMAIN;
    }

    public function getConfiguration()
    {
        if (is_null($this->cache)) {
            $this->cache = new Cache();
            $this->cache->pull();
        }

        return $this->cache;
    }

    public function buildFileAssociation()
    {
        if ($this->hasFoundTranslationsDirectory()) {
            $parser = new FileParser();
            $files = $parser->rscandir(WP_LANG_DIR);

            $this->moFiles = array();
        }
    }

    public function hasFoundTranslationsDirectory()
    {
        return file_exists(WP_LANG_DIR);
    }

    public function hasFoundTranslations()
    {
        return is_array($this->moFiles) && count($this->moFiles);
    }

    public function saveLocale(Locale $locale, WordpressAdaptor $adaptor)
    {
        if (!$locale->isValid()) {
            throw new Exception("The local contains invalid information.");
        }

        $configuration = $this->getConfiguration();

        if ($configuration->isDuplicateCode($locale)) {
            throw new Exception(sprintf("Another locale has been saved with this code (%s).", $locale->getCode()));
        }

        if (!$locale->hasPo()) {
            $parser = new FileParser();
            $parser->createPoFile($locale, $adaptor);
        }

        if (!$configuration->saveLocale($locale)) {
            throw new Exception("Could not save to the database.");
        }
    }
}
