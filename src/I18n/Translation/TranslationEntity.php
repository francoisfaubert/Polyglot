<?php

namespace Polyglot\I18n\Translation;

use Strata\Strata;

abstract class TranslationEntity {
    public static function factory($translation)
    {
        switch($translation->obj_kind) {
            case "WP_Post" :
            case "WP_Page" :return new PostTranslationEntity($translation);
            case "Term" : return new TermTranslationEntity($translation);
        }

        throw new Exception("Unknown translation object type.");
    }


    abstract public function getWordpressObject();

    protected $polyglot_ID;
    protected $obj_kind;
    protected $obj_type;
    protected $obj_id;
    protected $translation_of;
    protected $translation_locale;
    protected $cachedWordpressObject;

    public function __construct($ref = null)
    {
        if (!is_null($ref)) {
            foreach (get_object_vars($ref) as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    public function getObjectKind()
    {
        return $this->obj_kind;
    }

    public function getObjectType()
    {
        return $this->obj_type;
    }

    public function getObjectId()
    {
        return (int)$this->obj_id;
    }

    public function getOriginalObjectId()
    {
        return (int)$this->translation_of;
    }

    public function getId()
    {
        return (int)$this->polyglot_ID;
    }

    public function getTranslationLocale()
    {
        return Strata::i18n()->getLocaleByCode($this->translation_locale);
    }

    public function getTranslationLocaleCode()
    {
        return $this->translation_locale;
    }

    // public function isOriginal()
    // {
    //     global $polyglot;
    //     $defaultLocale = $polyglot->getDefaultLocale();
    //     return is_null($this->translation_of) && $defaultLocale->getCode() === $this->translation_locale;
    // }

}
