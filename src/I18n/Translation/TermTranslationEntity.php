<?php

namespace Polyglot\I18n\Translation;

use Strata\Strata;
use Exception;

class TermTranslationEntity extends TranslationEntity {

    public function getWordpressObject()
    {
        if (is_null($this->cachedWordpressObject)) {
            $this->cachedWordpressObject = get_term_by('id', $this->obj_id, $this->obj_type);
        }

        if (is_null($this->cachedWordpressObject)) {
            throw new Exception("TermTranslationEntity was not associated to a term.");
        }

        return $this->cachedWordpressObject;
    }

    public function getObjectKind()
    {
        return "Term";
    }
}
