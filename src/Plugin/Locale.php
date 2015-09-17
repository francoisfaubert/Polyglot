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
            return (int)get_the_ID();
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

        return $tree->hasTranslationFor($this) || ($this->isDefault() && $tree->isTranslationSetOf($postId, "WP_Post"));
    }

    public function isTranslationOfPost($postId = null)
    {
        $postId = $this->proofId($postId);
        $tree = $this->getTranslationTree($postId);
        return !is_null($tree) && $tree->isTranslationSetOf($postId, "WP_Post");
    }

    public function getTranslatedPost($postId = null)
    {
        $tree = $this->getTranslationTree($postId, "WP_Post");
        if ($tree) {
            $translationEntity = $tree->getTranslationFor($this);
            if ($translationEntity) {
                return $translationEntity->loadAssociatedWPObject();
            }

            if ($tree->isTranslationSetOf($postId, "WP_Post")) {
                return get_post($postId);
            }
        }
    }

    public function hasTermTranslation($termId, $taxname)
    {
        if ($this->isDefault()) {
            return false;
        }

        $tree = $this->getTranslationTree($termId, "Term");

        // Tree will be null when a new term is being created.
        if (is_null($tree)) {
            return $this->isDefault();
        }

        return $tree->hasTranslationFor($this) || ($this->isDefault() && $tree->isTranslationSetOf($postId, "WP_Post"));
    }

    public function isTranslationOfTerm($termId, $taxname)
    {
        $tree = $this->getTranslationTree($termId, $taxname);
        return !is_null($tree) && $tree->isTranslationSetOf($postId, $taxname);
    }

    public function getTranslatedTerm($termId, $taxName)
    {
        $tree = $this->getTranslationTree($termId, "Term");
        if ($tree) {
            $translationEntity = $tree->getTranslationFor($this);
            if ($translationEntity) {
                return $translationEntity->loadAssociatedWPObject();
            }

            if ($tree->isTranslationSetOf($termId, "Term")) {
                return get_term_by('id', $termId, $taxName);
            }
        }
    }

    protected function findTranslatedId($objectId, $objectKind)
    {
        $tree = $this->getTranslationTree($objectId, $objectKind);

        // Tree will be null when a new post is being created.
        if (is_null($tree)) {
            return $objectId;
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
        return admin_url('options-general.php?page=polyglot-plugin&polyglot_action=createTranslationDuplicate&object='.$originalPost->ID.'&objectKind=WP_Post&objectType='.$originalPost->post_type.'&locale='.$this->getCode());
    }

    public function getEditPostUrl($postId = null)
    {
        $object = $this->getTranslatedPost($postId);
        return $this->getEditPostByIdAndType($object->ID, $object->post_type);
    }

    public function getEditPostByIdAndType($postId, $postType = "")
    {
        if (empty($postType) && $postType != 'post') {
            return admin_url('post.php?post='.$postId.'&post_type='.$postType.'&action=edit&locale=' . $this->getCode());
        }

        return admin_url('post.php?post='.$postId.'&action=edit&locale=' . $this->getCode());
    }

    public function getTranslateTermUrl($originalTerm)
    {
        return admin_url('options-general.php?page=polyglot-plugin&polyglot_action=createTranslationDuplicate&object='.$originalTerm->term_id.'&objectKind=Term&objectType='.$originalTerm->taxonomy.'&locale='.$this->getCode());
    }

    public function getEditTermUrl($termId, $taxonomy)
    {
        $object = $this->getTranslatedTerm($termId, $taxonomy);
        return admin_url('edit-tags.php?action=edit&taxonomy='.$object->taxonomy.'&tag_ID='.$object->term_id.'&post_type=post');
    }
}
