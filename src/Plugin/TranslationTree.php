<?php

namespace Polyglot\Plugin;

use Polyglot\Plugin\Polyglot;
use Polyglot\Plugin\Locale;

use Polyglot\Plugin\TranslationEntity\TranslationEntity;
use Exception;

class TranslationTree  {

    private $list;
    private $translationObjId;
    private $translationObjKind;

    function __construct($entityList)
    {
        $this->list = $entityList;

        if (count($this->list)) {
            $this->translationObjId = $entityList[0]->translation_of;
            $this->translationObjKind = $entityList[0]->obj_kind;
        }
    }

    public function getId()
    {
        return $this->translationObjId;
    }

    public function getKind()
    {
        return $this->translationObjKind;
    }

    public function isTranslationSetOf($mixedId, $mixedKind)
    {
        return $mixedId === $this->translationObjId && $mixedKind === $this->translationObjKind;
    }

    public function hasTranslationFor(Locale $locale)
    {
        return !is_null($this->getTranslationFor($locale));
    }

    public function getTranslationFor(Locale $locale)
    {
        foreach ($this->list as $translationEntity) {
            if ($translationEntity->translation_locale === $locale->getCode()) {
                return $translationEntity;
            }
        }

        return null;
    }

}
