<?php
namespace Polyglot\Plugin\Translator;

use Polyglot\Plugin\Polyglot;
use Exception;

class TermTranslator extends TranslatorBase {

    protected $originalKind = "Term";

    public function getTranslatedObject()
    {
        if ((int)$this->translationObjId > 0) {
            return Polyglot::instance()->query()->findTermById($this->translationObjId, $this->originalType);
        }

        throw new Exception("Translation is not associated to an object.");
    }

    public function getForwardUrl()
    {
        $locale = $this->getTranslationLocale();
        return $locale->getEditTermUrl($this->translationObjId, $this->originalType);
    }

    public function carryOverOriginalData()
    {

    }
}
