<?php
namespace Polyglot\Plugin\TranslationEntity;

use Exception;

class PostTranslationEntity extends TranslationEntity {

    public function loadAssociatedWPObject()
    {
        if (is_null($this->associatedWPObject) && ($this->obj_kind === "WP_Post" || $this->obj_kind === "WP_Page")) {
            $this->associatedWPObject = get_post($this->obj_id);
        }

        if (is_null($this->associatedWPObject)) {
            throw new Exception("PostTranslationEntity was not associated to a post.");
        }

        return $this->associatedWPObject;
    }

    public function getObjectId()
    {
        // Accesss the property like a function because
        // of the get/set internals of the Translation entity.

        // $id = $this->ID;
        // if (!empty($id)) {
        //     return $id;
        // }

        return $this->obj_id;
    }

    public function getObjectKind()
    {
        return "WP_Post";
    }

    public function getObjectType()
    {
        // Accesss the property like a function because
        // of the get/set internals of the Translation entity.
        // $post_type = $this->post_type;

        // if (!empty($post_type)) {
        //     return $post_type;
        // }

        return $this->obj_type;
    }

}
