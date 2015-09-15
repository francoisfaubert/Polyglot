<?php
namespace Polyglot\Plugin\TranslationEntity;

use Exception;

class PostTranslationEntity extends TranslationEntity {

    public function loadAssociatedWPObject()
    {
        if (is_null($this->associatedWPObject)) {
            $this->associatedWPObject = get_post($this->obj_id);
        }

        if (is_null($this->associatedWPObject)) {
            throw new Exception("PostTranslationEntity was not associated to a post.");
        }

        return $this->associatedWPObject;
    }

    public function getObjectKind()
    {
        return "WP_Post";
    }

}
