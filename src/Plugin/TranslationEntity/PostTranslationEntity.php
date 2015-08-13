<?php
namespace Polyglot\Plugin\TranslationEntity;

use Strata\Model\CustomPostType\ModelEntity;
use Exception;

class PostTranslationEntity extends TranslationEntity {

    public function loadAssociated()
    {
        if ($this->obj_kind === "WP_Post" || $this->obj_kind === "WP_Page") {
            return get_post($this->obj_id);
        }

        throw new Exception("PostTranslationEntity was not associated to a post.");
    }

    public function getObjectId()
    {
        return $this->ID;
    }

    public function getObjectKind()
    {
        return "WP_Post";
    }

    public function getObjectType()
    {
        return $this->post_type;
    }

}
