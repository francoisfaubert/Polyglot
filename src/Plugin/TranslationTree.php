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

    function __construct($id, $kind, $entityList)
    {
        $this->list = $entityList;
        $this->translationObjId = (int)$id;
        $this->translationObjKind = $kind;
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
        return (int)$mixedId === (int)$this->translationObjId && $mixedKind === $this->translationObjKind;
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
    }

    public function hasTranslatedObject($id, $kind)
    {
        return !is_null($this->getTranslatedObject($id, $kind));
    }

    public function getTranslatedObject($id, $kind)
    {
        foreach ($this->list as $translationEntity) {
            if ((int)$translationEntity->obj_id === (int)$id && $translationEntity->obj_kind === $kind) {
                return $translationEntity;
            }
        }
    }

}
