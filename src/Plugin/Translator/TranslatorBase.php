<?php
namespace Polyglot\Plugin\Translator;

use Strata\Strata;
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
        return Strata::i18n()->getLocaleByCode($this->translatedTo);
    }

    protected function saveInformationToPolyglot()
    {
        return Strata::i18n()->query()->addTranslation(
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
        $i18n = Strata::i18n();
        $tree = $i18n->query()->findTranlationsOfId($this->originalId, $this->originalKind);
        return $tree && $tree->hasTranslationFor($i18n->getLocaleByCode($this->translatedTo));
    }
}
