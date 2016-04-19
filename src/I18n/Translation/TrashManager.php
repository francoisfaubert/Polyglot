<?php

namespace Polyglot\I18n\Translation;

use Strata\Strata;

class TrashManager {

    private $querier;

    public function setQuerier($querier)
    {
        $this->querier = $querier;
    }

    public function addFilters()
    {
        $this->setupPostTrash();
        $this->setupTermTrash();
    }

    protected function setupPostTrash()
    {
        add_action('wp_trash_post', array($this, 'onTrashPost'));
    }

    protected function removePostTrash()
    {
        remove_action('wp_trash_post', array($this, 'onTrashPost'));
    }

    protected function setupTermTrash()
    {
        add_action('delete_term_taxonomy', array($this, 'onTrashTerm'));
    }

    protected function removeTermTrash()
    {
        remove_action('delete_term_taxonomy', array($this, 'onTrashTerm'));
    }

    public function onTrashTerm($termId)
    {
        // We need to remove the listener because it would start infinite loops.
        $this->removeTermTrash();
        $this->querier->unlinkTranslationFor($termId, "Term");
        $this->querier->unlinkTranslation($termId, "Term");
        $this->setupTermTrash();
    }

    public function onTrashPost($postId)
    {
        // We need to remove the listener because it would start infinite loops.
        $this->removePostTrash();
        $this->querier->unlinkTranslationFor($postId, "WP_Post");
        $this->querier->unlinkTranslation($postId, "WP_Post");
        $this->setupPostTrash();
    }
}
