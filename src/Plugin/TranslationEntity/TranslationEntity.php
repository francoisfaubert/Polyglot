<?php

namespace Polyglot\Plugin\TranslationEntity;

use Strata\Model\CustomPostType\ModelEntity;
use Exception;

abstract class TranslationEntity extends ModelEntity {

    public static function factory($translation)
    {
        switch($translation->obj_kind) {
            case "WP_Post" :
            case "WP_Page" :return new PostTranslationEntity($translation);
            case "Term" : return new TermTranslationEntity($translation);
        }

        throw new Exception("Unknown translation object type.");
    }

    protected $associatedWPObject;

    public function isOriginal()
    {
        global $polyglot;
        $defaultLocale = $polyglot->getDefaultLocale();
        return is_null($this->translation_of) && $defaultLocale->getCode() === $this->translation_locale;
    }

    abstract function loadAssociatedWPObject();

}
