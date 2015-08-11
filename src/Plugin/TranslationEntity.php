<?php

namespace Polyglot\Plugin;

use Strata\Model\CustomPostType\ModelEntity;
use Exception;

class TranslationEntity extends ModelEntity {

    public function isOriginal()
    {
        global $polyglot;
        $defaultLocale = $polyglot->getDefaultLocale();
        return is_null($this->translation_of) && $defaultLocale->getCode() === $this->translation_locale;
    }

    public function loadAssociated()
    {
        switch($this->obj_kind) {
            case "WP_Post" : return get_post($this->obj_id);
            case "WP_Page" : return get_page($this->obj_id);
        }
    }


}
