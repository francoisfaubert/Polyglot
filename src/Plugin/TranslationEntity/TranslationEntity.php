<?php

namespace Polyglot\Plugin\TranslationEntity;

use Strata\Model\CustomPostType\ModelEntity;
use Exception;

abstract class TranslationEntity extends ModelEntity {

    abstract function loadAssociatedWPObject();

    public static function factory($translation, $config = array())
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

    public function getObjectId()
    {
        return $this->obj_id;
    }

    public function getObjectType()
    {
        return $this->obj_type;
    }

}
