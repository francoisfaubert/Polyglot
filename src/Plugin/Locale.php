<?php

namespace Polyglot\Plugin;

use Exception;

use Strata\Strata;
use Strata\I18n\Locale as StrataLocale;

use Polyglot\Plugin\Polyglot;
use Polyglot\Plugin\Db\Query;
use Polyglot\Plugin\TranslationEntity;

class Locale extends StrataLocale {

    /**
     * [getTranslations description]
     * @param  [type] $mixedId   [description]
     * @param  string $mixedKind [description]
     * @return TranslationTree
     */
    private function getTranslationTree($mixedId, $mixedKind = "WP_Post")
    {
        $originalId = $this->getOriginalObjectId($mixedId, $mixedKind);
        return Polyglot::instance()->query()->findTranlationsOfId($originalId, $mixedKind);
    }

    private function getOriginalObjectId($mixedId, $mixedKind)
    {
        $localizedDetails = Polyglot::instance()->query()->findDetailsById($mixedId, $mixedKind);
        if ($localizedDetails && !is_null($localizedDetails->translation_of)) {
            return (int)$localizedDetails->translation_of;
        }

        // Assume this object is the original since it had no translation
        return (int)$mixedId;
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

        // Tree will be null when a new post is being created.
        if (is_null($tree)) {
            return $this->isDefault();
        }

        return !is_null($this->getTranslatedPost($postId));
    }

    public function isTranslationOfPost($postId = null)
    {
        $postId = $this->proofId($postId);
        $tree = $this->getTranslationTree($postId);
        return !is_null($tree) && $tree->isTranslationSetOf($postId, "WP_Post");
    }

    public function getTranslatedPost($postId = null)
    {
        $id = (int)$this->findTranslatedId($this->proofId($postId), "WP_Post");
        if ($id > 0) {
            return Polyglot::instance()->query()->findPostById($id);
        }
    }

    public function hasTermTranslation($termId, $taxname)
    {
        $tree = $this->getTranslationTree($termId, "Term");

        // Tree will be null when a new post is being created.
        if (is_null($tree)) {
            return $this->isDefault();
        }

        return !is_null($this->getTranslatedTerm($termId, $taxname));
    }

    public function isTranslationOfTerm($termId, $taxname)
    {
        $tree = $this->getTranslationTree($termId, $taxname);
        return !is_null($tree) && $tree->isTranslationSetOf($postId, $taxname);
    }

    public function getTranslatedTerm($termId, $taxName)
    {
        $id = (int)$this->findTranslatedId($termId, "Term");
        if ($id > 0) {
            return Polyglot::instance()->query()->findTermById($id, $taxName);
        }
    }

    protected function findTranslatedId($objectId, $objectKind)
    {
        $tree = $this->getTranslationTree($objectId, $objectKind);

        // Tree will be null when a new post is being created.
        if (is_null($tree)) {
            return $this->isDefault() ? $objectId : null;
        }

        // Load the translation when it exists
        if (!$this->isDefault() && $tree->hasTranslationFor($this)) {
            $translationEntity = $tree->getTranslationFor($this);
            return $translationEntity->getObjectId();
        }
        // Where there is no translation but this is the default locale
        // load up the base id of the translation tree which maps to the
        // base original object id.
        if ($this->isDefault() && $tree->getId() > 0) {
            return $tree->getId();
        }
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


    public function getTranslatePostUrl($originalPost)
    {
        return admin_url('options-general.php?page=polyglot-plugin&polyglot_action=createTranslationDuplicate&object='.$originalPost->ID.'&objectKind='.$originalPost->getObjectKind().'&objectType='.$originalPost->post_type.'&locale='.$this->getCode());
    }

    public function getEditPostUrl($postId = null)
    {
        $object = $this->getTranslatedPost($postId);

        if (isset($object->post_type) && $object->post_type != 'post') {
            return admin_url('post.php?post='.$object->ID.'&post_type='.$object->post_type.'&action=edit&locale=' . $this->getCode());
        }

        return admin_url('post.php?post='.$object->ID.'&action=edit&locale=' . $this->getCode());
    }

    public function getTranslateTermUrl($originalTerm)
    {
        return admin_url('options-general.php?page=polyglot-plugin&polyglot_action=createTranslationDuplicate&object='.$originalTerm->term_id.'&objectKind='.$originalTerm->getObjectKind().'&objectType='.$originalTerm->taxonomy.'&locale='.$this->getCode());
    }

    public function getEditTermUrl($termId, $taxonomy)
    {
        $object = $this->getTranslatedTerm($termId, $taxonomy);
        return admin_url('edit-tags.php?action=edit&taxonomy='.$object->getObjectType().'&tag_ID='.$object->getObjectId().'&post_type=post');
    }
}
