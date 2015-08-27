<?php
namespace Polyglot\Plugin\Translator;

use Polyglot\Plugin\Polyglot;
use Exception;

abstract class TranslatorBase {

    protected $originalId;
    protected $originalType;
    protected $originalKind;
    protected $translatedTo;
    protected $translationObjId;

    public function translate($id, $type, $localeCode)
    {
        $this->originalId = $id;
        $this->originalType = $type;
        $this->translatedTo = $localeCode;

        if ($this->isValidLocale()) {
            $this->translationObjId = $this->copyObject();

            if ((int)$this->translationObjId > 0) {
                return $this->carryOverOriginalData();
            }
        }

        throw new Exception(__("Polyglot could not translate the object.", 'polyglot'));
    }

    protected function getTranslationLocale()
    {
        return Polyglot::instance()->getLocaleByCode($this->translatedTo);
    }

    protected function copyObject()
    {
        return Polyglot::instance()->query()->addTranslation(
            $this->originalId,
            $this->originalType,
            $this->originalKind,
            $this->translatedTo
        );
    }

    private function isValidLocale()
    {
        return !is_null($this->getTranslationLocale());
    }


    abstract public function carryOverOriginalData();
    abstract public function getTranslatedObject();
}
