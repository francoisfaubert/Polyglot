<?php

namespace Polyglot\Plugin;

use Exception;
use Strata\Strata;
use Polyglot\Plugin\Db\Query;

class Locale extends \Strata\I18n\Locale  {

    protected $nativeLabel;
    protected $code;
    protected $isDefault;
    protected $dbRow;

    public function setDbRow($row)
    {
        $this->dbRow = $row;
    }

    public function isDbSynced()
    {
        return !is_null($this->dbRow);
    }

    public function wasLocalized()
    {
        if ($this->isDbSynced()) {
            return (int)$this->dbRow->obj_id > 0;
        }

        return false;
    }

    public function getObjId()
    {
        if ($this->isDbSynced()) {
            return (int)$this->dbRow->obj_id;
        }
    }


    public function getAssociatedObject()
    {
        if ($this->wasLocalized()) {
            switch($this->dbRow->obj_kind) {
                case "WP_Post" : return get_post($this->getObjId());
                case "WP_Page" : return get_page($this->getObjId());
            }
        }
    }

    public function getEditUrl()
    {
        return admin_url('options-general.php?page=polyglot-plugin&polyglot_action=editLocale&locale='.$this->getCode());
    }

    public function getObjectTranslateUrl($object)
    {
        //$object = $this->getAssociatedObject();
        return admin_url('options-general.php?page=polyglot-plugin&polyglot_action=createTranslationDuplicate&object='.$object->ID.'&objectKind='.get_class($object).'&objectType='.$object->post_type.'&locale='.$this->getCode());
    }

    public function getObjectEditUrl()
    {
        $object = $this->getAssociatedObject();

        if (array_key_exists("post_type", $object) && $object->post_type != 'post') {
            return admin_url('post.php?post='.$object->ID.'&post_type='.$object->post_type.'&action=edit&locale=' . $this->getCode());
        }

        return admin_url('post.php?post='.$object->ID.'&action=edit&locale=' . $this->getCode());

    }
}
