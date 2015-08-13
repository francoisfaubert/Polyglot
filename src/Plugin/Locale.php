<?php

namespace Polyglot\Plugin;

use Exception;

use Strata\Strata;
use Strata\I18n\Locale as StrataLocale;

use Polyglot\Plugin\Polyglot;
use Polyglot\Plugin\Db\Query;
use Polyglot\Plugin\TranslationEntity;

class Locale extends StrataLocale {

    protected $translations = null;
    private $polyglot;

    function __construct($code, $config = array())
    {
        parent::__construct($code, $config);
        $this->polyglot = Polyglot::instance();
    }

    /**
     * [getTranslations description]
     * @param  [type] $mixedId   [description]
     * @param  string $mixedKind [description]
     * @return TranslationTree
     */
    private function getTranslationTree($mixedId, $mixedKind = "WP_Post")
    {
        $originalId = $this->getOriginalObjectId($mixedId, $mixedKind);
        $translations = $this->polyglot->query()->findOriginalTranslationDetailsId($originalId, $mixedKind);

        if (is_null($translations)) {
            $translations = $this->polyglot->query()->findAllTranlationsOfOriginalId($originalId, $mixedKind);
        }

        return $translations;
    }

    private function getOriginalObjectId($mixedId, $mixedKind)
    {
        switch ($mixedKind) {
            case "WP_Post" : $obj = $this->polyglot->query()->findPostById($mixedId); break;
            case "stdClass" : $obj = $this->polyglot->query()->findTaxonomyById($mixedId, $mixedKind); break;
        }

        $localizedDetails = $this->polyglot->query()->findDetails($obj);
        if ($localizedDetails && !is_null($localizedDetails->translation_of)) {
            return $localizedDetails->translation_of;
        }

        // Assume this object is the original since it had no translation
        return $mixedId;
    }

    private function proofId($postId = null)
    {
        if (is_null($postId)) {
            return get_the_ID();
        }

        return (int)$postId;
    }

    public function hasPostTranslation($postId = null)
    {
        $postId = $this->proofId($postId);
        $tree = $this->getTranslationTree($postId);

        return !is_null($this->getTranslatedPost($postId));
    }

    public function isTranslationOfPost($postId = null)
    {
        $postId = $this->proofId($postId);
        $tree = $this->getTranslationTree($postId);
        return $tree->isTranslationSetOf($postId, "WP_Post");
    }

    public function getTranslatedPost($postId = null)
    {
        $postId = $this->proofId($postId);
        $tree = $this->getTranslationTree($postId);

        // Load the translation when it exists
        if ($tree->hasTranslationFor($this)) {
            $postTranslationEntity = $tree->getTranslationFor($this);
            return $this->polyglot->query()->findPostById($postTranslationEntity->obj_id);
        }

        // Load the post if it happens that this post is
        // the owner of this translation set. (@todo I don't think this
        // is every true since we don't save translation of the default locale)
        if ($tree->isTranslationSetOf($postId, "WP_Post")) {
            return $this->polyglot->query()->findPostById($postId);
        }

        // When all else failed but the tree is loaded, assume it because the translated post is the
        // original owner of the set.
        if ($tree->getId() > 0) {
            return $this->polyglot->query()->findPostById($tree->getId());
        }

        // When a tree couldn't be loaded, then the current post is the original
        return $this->polyglot->query()->findPostById($postId);
    }

    public function getHomeUrl()
    {
        if ($this->isDefault()) {
            return get_home_url();
        }

        return get_home_url() . "/" . $this->getUrl() . "/";
    }

    public function getEditUrl()
    {
        return admin_url('options-general.php?page=polyglot-plugin&polyglot_action=editLocale&locale='.$this->getCode());
    }

    public function getTranslatePostUrl($postId = null)
    {
        $object = $this->getTranslatedPost($postId);
        return admin_url('options-general.php?page=polyglot-plugin&polyglot_action=createTranslationDuplicate&object='.$object->getObjectId().'&objectKind='.$object->getObjectKind().'&objectType='.$object->getObjectType().'&locale='.$this->getCode());
    }

    public function getEditPostUrl($postId = null)
    {
        $object = $this->getTranslatedPost($postId);

        if (isset($object->post_type) && $object->post_type != 'post') {
            return admin_url('post.php?post='.$object->ID.'&post_type='.$object->post_type.'&action=edit&locale=' . $this->getCode());
        }

        return admin_url('post.php?post='.$object->ID.'&action=edit&locale=' . $this->getCode());

    }
}
