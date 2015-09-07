<?php
namespace Polyglot\Plugin\Translator;

use Polyglot\Plugin\Polyglot;
use Exception;

class PostTranslator extends TranslatorBase {

    protected $originalKind = "WP_Post";

    public function getTranslatedObject()
    {
        if ((int)$this->translationObjId > 0) {
            return Polyglot::instance()->query()->findPostById($this->translationObjId);
        }

        throw new Exception("Translation is not associated to an object.");
    }

    public function getForwardUrl()
    {
        $locale = $this->getTranslationLocale();
        return $locale->getEditPostUrl($this->translationObjId);
    }

    public function copyObject()
    {
        $locale = $this->getTranslationLocale();
        $originalPost = get_post($this->originalId);
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
        global $wpdb;
        $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id={$this->originalId}");
        if (count($post_meta_infos)!=0) {
            $sql_query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) ";
            foreach ($post_meta_infos as $meta_info) {
                $meta_key = $meta_info->meta_key;
                $meta_value = addslashes($meta_info->meta_value);
                $sql_query_sel[]= "SELECT $this->translationObjId, '$meta_key', '$meta_value'";
            }
            $sql_query.= implode(" UNION ALL ", $sql_query_sel);
            $wpdb->query($sql_query);
        }


global $wp_object_cache;
foreach ($wp_object_cache->cache as $key => $value) {
    if (strstr($key, "acf")) {
        foreach ($value as $acfkey => $acfvalue) {
            wp_cache_delete($acfkey, 'acf' );
        }
    }
}


    }
}
