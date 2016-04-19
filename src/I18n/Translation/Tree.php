<?php

namespace Polyglot\I18n\Translation;

use Strata\Strata;
use Polyglot\I18n\Locale\Locale;

class Tree {

    public static function grow($mixedId, $mixedKind = "WP_Post")
    {
        $tree = new self();
        $tree->setQuerier(Strata::I18n()->query());
        $tree->setContext($mixedId, $mixedKind);
        $tree->expand();
        return $tree;
    }

    public static function plant($mixedId, $mixedKind = "WP_Post", $dataset = array())
    {
        $tree = new self();
        $tree->setQuerier(Strata::I18n()->query());
        $tree->setContext($mixedId, $mixedKind);
        $tree->populate($dataset);
        return $tree;
    }

    private $rootObjectId;
    private $rootObjectKind;
    private $sourceObjectId;

    private $querier;
    private $localizations = null;

    public function setContext($mixedId, $mixedKind = "WP_Post")
    {
        $this->rootObjectKind = $mixedKind;
        $this->sourceObjectId = (int)$mixedId;
        $this->rootObjectId = $this->getOriginalObjectId((int)$mixedId);
    }

    public function setQuerier($querier)
    {
        $this->querier = $querier;
    }

    public function getRootId()
    {
        return $this->rootObjectId;
    }

    public function getRootKind()
    {
        return $this->rootObjectKind;
    }

    public function expand()
    {
        $data = $this->querier->findTranlationsOfId($this->rootObjectId, $this->rootObjectKind);
        $this->populate($data);
    }

    public function populate($data = array())
    {
        $this->localizations = $data;
    }

    public function isLocalized()
    {
        return !is_null($this->localizations);
    }

    public function isLocalizedSetOf($objectId)
    {
        return $this->rootObjectId === (int)$objectId;
    }

    public function getLocalizedObjectById($objectId)
    {
        $objectId = (int)$objectId;
        foreach ($this->localizations as $localization) {
            if ((int)$localization->getObjectId() === $objectId) {
                return $localization;
            }
        }
    }

    public function getLocalizationLocaleById($objectId)
    {
        $object = $this->getLocalizedObjectById($objectId);
        if ($object) {
            return $object->getTranslationLocale();
        }
    }

    public function hasLocalizationIn(Locale $locale)
    {
        return !is_null($this->getLocalizationIn($locale));
    }

    public function getLocalizationIn(Locale $locale)
    {
        $code = $locale->getCode();
        foreach ($this->localizations as $localization) {
            if ($localization->getTranslationLocaleCode() === $code) {
                return $localization;
            }
        }
    }

    private function getOriginalObjectId($mixedId)
    {
        $localizedDetails = $this->querier->findDetailsById($mixedId, $this->rootObjectKind);
        if ($localizedDetails && $localizedDetails->getOriginalObjectId() > 0) {
            return $localizedDetails->getOriginalObjectId();
        }

        // Assume this object is the original since it had no translation
        return (int)$mixedId;
    }
}
