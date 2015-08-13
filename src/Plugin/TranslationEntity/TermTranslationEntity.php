<?php

namespace Polyglot\Plugin\TranslationEntity;

use Strata\Model\CustomPostType\ModelEntity;
use Exception;

 // Tags & taxonomies
class TermTranslationEntity extends TranslationEntity {

    public function loadAssociated()
    {
        if ($this->obj_kind === "stdClass") {
            return get_term_by('id', $this->obj_id, $this->obj_type);
        }

        throw new Exception("TermTranslationEntity was not associated to a term.");
    }

    public function getObjId()
    {
        return $this->term_id;
    }

    public function getKind()
    {
        return "Term";
    }
}
