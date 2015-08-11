<?php

namespace Polyglot\Plugin;

use Exception;
use Strata\Strata;
use Polyglot\Plugin\Db\Query;
use Polyglot\Plugin\TranslationEntity;

class Locale extends \Strata\I18n\Locale  {

    protected $details;

    public function setDetails(TranslationEntity $details)
    {
        $this->details = $details;
    }

    public function hasTranslation()
    {
        return !is_null($this->details);
    }

    public function getAssociatedObject()
    {
        if ($this->hasTranslation()) {
            return $this->details->loadAssociated();
        }

        throw new Exception("This locale has no associated object.");
    }

    public function getHomeUrl()
    {
        if ($this->isDefault) {
            return get_home_url();
        }

        return get_home_url() . "/" . $this->getUrl() . "/";
    }

    public function getEditUrl()
    {
        return admin_url('options-general.php?page=polyglot-plugin&polyglot_action=editLocale&locale='.$this->getCode());
    }

    public function getTranslationPermalink()
    {
        $object = $this->getAssociatedObject();

        // When this localized object is a page confirm
        // wether it's a translation of the home page before
        // returning the permalink. If it is, then return it's home url.
        if ($this->details->obj_type === "page") {
            global $polyglot;
            $homeId = (int)$polyglot->query()->getDefaultHomepageId();
            if ($homeId > 0 && (int)$this->details->translation_of === $homeId) {
                return $this->getHomeUrl();
            }
        }

        return get_the_permalink($object->ID);
    }

    public function getTranslationTitle()
    {
        $object = $this->getAssociatedObject();
        return get_the_title($object->ID);
    }

    public function getObjectTranslateUrl()
    {
        $object = $this->getAssociatedObject();
        return admin_url('options-general.php?page=polyglot-plugin&polyglot_action=createTranslationDuplicate&object='.$object->ID.'&objectKind='.get_class($object).'&objectType='.$object->post_type.'&locale='.$this->getCode());
    }

    public function getObjectEditUrl()
    {
        $object = $this->getAssociatedObject();

        if (isset($object->post_type) && $object->post_type != 'post') {
            return admin_url('post.php?post='.$object->ID.'&post_type='.$object->post_type.'&action=edit&locale=' . $this->getCode());
        }

        return admin_url('post.php?post='.$object->ID.'&action=edit&locale=' . $this->getCode());

    }
}
