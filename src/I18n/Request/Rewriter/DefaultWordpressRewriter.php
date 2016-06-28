<?php

namespace Polyglot\I18n\Request\Rewriter;

use Strata\Strata;
use Strata\Model\Taxonomy\Taxonomy;
use Exception;

class DefaultWordpressRewriter extends PolyglotRewriter {

    private $translatePages = false;
    private $translatePosts = false;

    public function setConfiguration($configuration)
    {
        $this->translatePages = (bool)$configuration->isTypeEnabled('page');
        $this->translatePosts = (bool)$configuration->isTypeEnabled('post');
    }

    public function rewrite()
    {
        $this->translateBases();

        if ($this->translatePages) {
           $this->rewriter->addRule('('.$this->urlRegex.')/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?pagename=$matches[2]&locale=$matches[1]&paged=$matches[3]');
           $this->rewriter->addRule('('.$this->urlRegex.')/(.?.+?)/?$', 'index.php?pagename=$matches[2]&locale=$matches[1]');
        }

        if ($this->translatePosts) {
           $this->rewriter->addRule('('.$this->urlRegex.')/([^/]+)/?$', 'index.php?name=$matches[2]&locale=$matches[1]');
        }

        $this->addCategoryRules();
    }

    private function translateBases()
    {
        global $wp_rewrite;

        $locales = $this->i18n->getLocales();
        $keys = array(
            'pagination_base',
            'author_base',
            'comments_base',
            'feed_base',
            'search_base',
            'category_base',
            'tag_base'
        );

        foreach ($keys as $key) {
            $possibleValues = array();
            foreach ($locales as $locale) {
                if ($locale->hasConfig("rewrite." . $key)) {
                    $possibleValues[] = $locale->getConfig("rewrite." . $key);
                } elseif (isset($wp_rewrite) && $locale->hasACustomUrl() && property_exists($wp_rewrite, $key)) {
                    $possibleValues[] = $wp_rewrite->{$key};
                }
            }

            if (count($possibleValues)) {
                $this->rewriter->addRule('('.$this->urlRegex.')/('.implode("|", $possibleValues).')/(.+)$', 'index.php?s=$matches[3]&locale=$matches[1]');
            }
        }
    }


    private function addCategoryRules()
    {
        $this->rewriter->addRule('('.$this->urlRegex.')/category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?category_name=$matches[2]&feed=$matches[3]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/category/(.+?)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?category_name=$matches[2]&feed=$matches[3]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/category/(.+?)/page/?([0-9]{1,})/?$', 'index.php?category_name=$matches[2]&paged=$matches[3]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/category/(.+?)/?$', 'index.php?category_name=$matches[2]&locale=$matches[1]');
    }


}
