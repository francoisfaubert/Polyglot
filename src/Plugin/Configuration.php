<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\Utility\Hash;
use Strata\Controller\Request;

use Polyglot\Plugin\Locale;
use Polyglot\Plugin\TranslationEntity;

use Polyglot\Plugin\Db\Query;

use WP_Post;
use Exception;


/**
 * Handles saving the configuration values in the WP backend
 */
class Configuration {

    private $datasource = null;

    function __construct()
    {
        $this->datasource = get_option("polyglot_configuration", $this->getDefaultConfiguration());
    }

    public function isTypeEnabled($postType)
    {
        return in_array($postType, $this->getEnabledPostTypes());
    }

    public function isTaxonomyEnabled($taxonomy)
    {
        return in_array($taxonomy, $this->getEnabledTaxonomies());
    }

    public function toggleTaxonomy($taxonomy)
    {
        if (is_null($taxonomy)) {
            return;
        }

        $config = $this->datasource;

        if (!$this->isTaxonomyEnabled($taxonomy)) {
            $config["taxonomies"][] = $taxonomy;
        }
        elseif(($key = array_search($taxonomy, $config["taxonomies"])) !== false) {
            unset($config["taxonomies"][$key]);
            $config = array_filter($config);
        }

        $this->datasource["taxonomies"] = $config["taxonomies"];
        $this->updateConfiguration();
    }

    public function togglePostType($postType)
    {
        if (is_null($postType)) {
            return;
        }

        $config = $this->datasource;

        if (!$this->isTypeEnabled($postType)) {
            $config["post-types"][] = $postType;
        }
        elseif(($key = array_search($postType, $config["post-types"])) !== false) {
            unset($config["post-types"][$key]);
            $config = array_filter($config);
        }

        $this->datasource["post-types"] = $config["post-types"];
        $this->updateConfiguration();
    }


    protected function updateConfiguration()
    {
        return update_option("polyglot_configuration", $this->datasource);
    }

    public function getOptions()
    {
        return $this->datasource['options'];
    }

    public function getEnabledPostTypes()
    {
        return $this->datasource['post-types'];
    }

    public function getEnabledTaxonomies()
    {
        return $this->datasource['taxonomies'];
    }

    protected function getDefaultConfiguration()
    {
        return array(
            "options" => array(),
            "post-types" => array(),
            "taxonomies" => array()
        );
    }

    public function getPostTypes()
    {
        $unsupported = array("nav_menu_item", "revision");
        return $this->filterKeys($unsupported, get_post_types(array(), "objects"));
    }

    public function getTaxonomies()
    {
        $unsupported = array('nav_menu', 'link_category', 'post_format');
        return $this->filterKeys($unsupported, get_taxonomies(array(), "objects"));
    }

    private function filterKeys($remove, $originalSet)
    {
        $filtered = array();
        foreach ($originalSet as $key => $value) {
            if (!in_array($key, $remove)) {
                $filtered[$key] = $value;
            }
        }
        return $filtered;
    }

}
