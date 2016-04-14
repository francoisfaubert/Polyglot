<?php

namespace Polyglot\I18n\Request\Rewriter;

use Strata\Strata;
use Strata\Model\CustomPostType\CustomPostType;
use Exception;

class CustomPostTypeRewriter extends PolyglotRewriter {

    protected $configuration;

    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    public function rewrite()
    {
        $postTypes = $this->configuration->getPostTypes();
        foreach ($postTypes as $postTypekey => $config) {
            if ($this->configuration->isTypeEnabled($postTypekey)) {
                if ($this->isASupportedKey($postTypekey)) {
                    if (preg_match("/^cpt_.*/", $postTypekey)) {
                        $this->rewriteStrataPost($postTypekey, $config);
                    } else {
                        $this->rewriteOrdinaryPost($postTypekey, $config);
                    }
                }
            }
        }
    }

    public function rewriteStrataPost($postTypekey, $config)
    {
        try {
            $cpt = CustomPostType::factory(substr($postTypekey, 4));

            $localizedSlugs = array_merge(
                array($cpt->hasConfig("rewrite.slug") ? $cpt->getConfig("rewrite.slug") : $postTypekey),
                $cpt->extractConfig("i18n.{s}.rewrite.slug")
            );

            $this->addCustomPostTypeRewrites(implode("|", $localizedSlugs), $postTypekey);

        } catch (Exception $e) {
            Strata::app()->log("Tried to translate $slug, but could not find the associated model.", "<magenta>Polyglot:UrlRewriter</magenta>");
        }
    }

    public function rewriteOrdinaryPost($postTypekey, $config)
    {
        $slug = $postTypekey;
        if (isset($config->rewrite) && isset($config->rewrite['slug'])) {
            $slug = $config->rewrite['slug'];
        }
        $this->addCustomPostTypeRewrites($slug, $postTypekey);
    }

    private function isASupportedKey($postTypekey)
    {
        return  $postTypekey !== 'post' &&
                $postTypekey !== 'page' &&
                $postTypekey !== 'attachment' &&
                $postTypekey !== 'revision';
    }

    private function addCustomPostTypeRewrites($slug, $postTypekey)
    {
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/[^/]+/attachment/([^/]+)/?$', 'index.php?attachment=$matches[3]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/[^/]+/attachment/([^/]+)/trackback/?$', 'index.php?attachment=$matches[3]&tb=1&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?attachment=$matches[3]&feed=$matches[4]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$', 'index.php?attachment=$matches[3]&cpage=$matches[4]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/([^/]+)/trackback/?$', 'index.php?'.$postTypekey.'=$matches[3]&tb=1&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?'.$postTypekey.'=$matches[3]&paged=$matches[4]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/([^/]+)/comment-page-([0-9]{1,})/?$', 'index.php?'.$postTypekey.'=$matches[3]&cpage=$matches[4]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/([^/]+)(/[0-9]+)?/?$', 'index.php?'.$postTypekey.'=$matches[3]&page=$matches[4]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/[^/]+/([^/]+)/trackback/?$', 'index.php?attachment=$matches[3]&tb=1&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?attachment=$matches[3]&feed=$matches[4]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?attachment=$matches[3]&feed=$matches[4]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$', 'index.php?attachment=$matches[3]&feed=$matches[4]&locale=$matches[1]');
    }
}
