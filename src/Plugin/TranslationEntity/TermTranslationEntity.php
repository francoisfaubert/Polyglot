<?php
namespace Polyglot\Plugin\TranslationEntity;

use Exception;

class TermTranslationEntity extends TranslationEntity {

    public function loadAssociated()
    {
        if ($this->obj_kind === "stdClass") {
            return get_term_by('id', $this->obj_id, $this->obj_type);
        }

        throw new Exception("TermTranslationEntity was not associated to a term.");
    }


    public function getObjectId()
    {
        if (isset($this->obj_id)) {
            return $this->obj_id;
        }

        return $this->term_id;
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
