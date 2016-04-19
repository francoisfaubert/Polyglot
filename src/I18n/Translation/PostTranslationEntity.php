<?php

namespace Polyglot\I18n\Translation;

use Strata\Strata;
use Exception;

class PostTranslationEntity extends TranslationEntity {

    public function getWordpressObject()
    {
        if (is_null($this->cachedWordpressObject)) {
            $this->cachedWordpressObject = get_post($this->obj_id);
        }

        if (is_null($this->cachedWordpressObject)) {
            throw new Exception("PostTranslationEntity was not associated to a post.");
        }

        return $this->cachedWordpressObject;
    }
}
