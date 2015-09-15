<?php
namespace Polyglot\Plugin\TranslationEntity;

use Exception;

class TermTranslationEntity extends TranslationEntity {

    public function loadAssociatedWPObject()
    {
        if (is_null($this->associatedWPObject)) {
            $this->associatedWPObject = get_term_by('id', $this->obj_id, $this->obj_type);
        }

        if (is_null($this->associatedWPObject)) {
            throw new Exception("TermTranslationEntity was not associated to a term.");
        }

        return $this->associatedWPObject;
    }


    public function getObjectId()
    {
        if (isset($this->obj_id)) {
            return $this->obj_id;
        }

        // return $this->term_id;
    }

    public function getObjectKind()
    {
        return "Term";
    }

    public function getObjectType()
    {
        return $this->taxonomy;
    }
}
