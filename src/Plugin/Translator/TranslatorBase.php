<?php
namespace Polyglot\Plugin\Translator;

use Polyglot\Plugin\Polyglot;
use Exception;

abstract class TranslatorBase {

    abstract public function carryOverOriginalData();
    abstract public function copyObject();

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

            if ($this->translationExists()) {
                throw new Exception(__("There is already a translation saved for this entity.", 'polyglot'));
            }

            $this->translationObjId = $this->copyObject();
            $this->saveInformationToPolyglot();

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

    protected function saveInformationToPolyglot()
    {
        return Polyglot::instance()->query()->addTranslation(
            $this->originalId,
            $this->originalType,
            $this->originalKind,
            $this->translatedTo,
            $this->translationObjId
        );
    }

    private function isValidLocale()
    {
        return !is_null($this->getTranslationLocale());
    }

    private function translationExists()
    {
        $polyglot = Polyglot::instance();
        $tree = $polyglot->query()->findTranlationsOfId($this->originalId, $this->originalKind);
        return $tree && $tree->hasTranslationFor($polyglot->getLocaleByCode($this->translatedTo));
    }
}
