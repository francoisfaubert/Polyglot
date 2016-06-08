<?php

namespace Polyglot\I18n\Locale;

use Exception;

use Strata\Strata;
use Strata\I18n\Locale as StrataLocale;

use Polyglot\I18n\Translation\Tree;

class Locale extends StrataLocale {

    private function proofId($postId = null)
    {
        if (is_null($postId)) {
            return (int)get_the_ID();
        }

        return (int)$postId;
    }

    public function hasPostTranslation($postId = null)
    {
        $tree = Tree::grow($this->proofId($postId), "WP_Post");

        // Tree will be null when a new post is being created.
        if (!$tree->isLocalized()) {
            return $this->isDefault();
        }

        return $tree->hasLocalizationIn($this) || $this->isDefault();
    }

    public function isTranslationOfPost($postId = null)
    {
        $postId = $this->proofId($postId);
        $tree = Tree::grow($this->proofId($postId), "WP_Post");
        return $tree->isLocalizedSetOf($postId);
    }

    public function getTranslatedPost($postId = null)
    {
        $postId = $this->proofId($postId);
        $tree = Tree::grow($this->proofId($postId), "WP_Post");

        if ($tree->isLocalized()) {

            $translationEntity = $tree->getLocalizationIn($this);
            if ($translationEntity) {
                return $translationEntity->getWordpressObject();
            }

            if ($this->isDefault()) {
                return get_post($tree->getRootId());
            }
        }
    }

    public function hasTermTranslation($termId)
    {
        $tree = Tree::grow($termId, "Term");

        // Tree will be null when a new term is being created.
        if (!$tree->isLocalized()) {
            return $this->isDefault();
        }

        return $tree->hasLocalizationIn($this) || $this->isDefault();
    }

    public function isTranslationOfTerm($termId)
    {
        $tree = Tree::grow($termId, "Term");
        return $tree->hasLocalizationIn($this) && $tree->isTranslationSetOf($termId);
    }

    public function getTranslatedTerm($termId, $taxName)
    {
        $tree = Tree::grow($termId, "Term");
        if ($tree->isLocalized()) {
            $translationEntity = $tree->getLocalizationIn($this);
            if ($translationEntity) {
                return $translationEntity->getWordpressObject();
            }

            if ($this->isDefault()) {
                return get_term_by('id', $tree->getRootId(), $taxName);
            }
        }
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

    public function getTranslateTermUrl($originalTerm, $contextualPostType = null)
    {
        $url = 'options-general.php?page=polyglot-plugin&polyglot_action=createTranslationDuplicate&object='.$originalTerm->term_id.'&objectKind=Term&objectType='.$originalTerm->taxonomy.'&locale='.$this->getCode();

        if (is_null($contextualPostType)) {
            return admin_url($url);
        }

        return admin_url($url . "&forwardPostType=" . $contextualPostType);
    }

    public function getEditTermUrl($termId, $taxonomy, $contextualPostType = null)
    {
        $object = $this->getTranslatedTerm($termId, $taxonomy);
        $url = 'edit-tags.php?action=edit&taxonomy='.$object->taxonomy.'&tag_ID='.$object->term_id.'&locale='.$this->getCode();

        if (is_null($contextualPostType)) {
            return admin_url($url);
        }

        return admin_url($url . '&post_type=' . $contextualPostType);
    }
}
