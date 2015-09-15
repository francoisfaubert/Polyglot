<?php
namespace Polyglot\Plugin\Translator;

use Polyglot\Plugin\Polyglot;
use Exception;

class PostTranslator extends TranslatorBase {

    protected $originalKind = "WP_Post";

    public function getForwardUrl()
    {
        $locale = $this->getTranslationLocale();
        return $locale->getEditPostUrl($this->translationObjId);
    }

    public function copyObject()
    {
        $locale = $this->getTranslationLocale();
        $originalPost = get_post($this->originalId);

        if ($originalPost) {
            $copiedData = array(
                'comment_status' => $originalPost->comment_status,
                'ping_status'    => $originalPost->ping_status,
                'post_author'    => $originalPost->post_author,
                'post_content'   => $originalPost->post_content,
                'post_excerpt'   => $originalPost->post_excerpt,
                'post_name'      => $originalPost->post_name,
                'post_parent'    => $originalPost->post_parent,
                'post_password'  => $originalPost->post_password,
                'post_status'    => 'draft',
                'post_title'     => $originalPost->post_title . " (" . $locale->getCode() . ")",
                'post_type'      => $originalPost->post_type,
                'to_ping'        => $originalPost->to_ping,
                'menu_order'     => $originalPost->menu_order
            );

            return wp_insert_post($copiedData);
        }

        throw new Exception("We could not load the original object.");
    }

    public function carryOverOriginalData()
    {
        $this->copyTaxonomies();
        $this->copyMetas();
    }

    protected function copyTaxonomies()
    {
        $taxonomies = get_object_taxonomies($this->originalType);
        foreach ($taxonomies as $taxonomy) {
            $postTerms = wp_get_object_terms($this->originalId, $taxonomy, array('fields' => 'slugs'));
            wp_set_object_terms($this->translationObjId, $postTerms, $taxonomy, false);
        }
    }

    protected function copyMetas()
    {
        foreach (get_post_meta($this->originalId) as $key => $metas) {
            foreach ($metas as $value) {
                update_post_meta($this->translationObjId, $key, $value);
            }
        }
    }
}
