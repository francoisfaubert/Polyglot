<?php

namespace Polyglot\I18n\Request\Rewriter;

use Strata\Strata;
use Strata\Model\Taxonomy\Taxonomy;
use Exception;

class TaxonomyRewriter extends PolyglotRewriter {

    protected $configuration;

    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    public function rewrite()
    {
        foreach ($this->configuration->getTaxonomies() as $taxonomyKey => $config) {
            if ($this->configuration->isTaxonomyEnabled($taxonomyKey)) {
                // Look for Strata configuration that would help translate the slugs.
                if (preg_match("/^tax_.*$/", $taxonomyKey)) {
                    $this->rewriteStrataTaxonomy($taxonomyKey, $config);
                } else {
                    $this->rewriteOrdinaryTaxonomy($taxonomyKey, $config);
                }
            }
        }
    }

    private function rewriteStrataTaxonomy($taxonomyKey, $config)
    {
        try {
            $taxonomy = Taxonomy::factory(substr($taxonomyKey, 4));

            $localizedSlugs = array_merge(
                array($taxonomy->hasConfig("rewrite.slug") ? $taxonomy->getConfig("rewrite.slug") : $taxonomyKey),
                $taxonomy->extractConfig("i18n.{s}.rewrite.slug")
            );

            $this->addTaxonomyRewrites(implode("|", $localizedSlugs), $config->query_var);

        } catch (Exception $e) {
            Strata::app()->log("Tried to translate $taxonomyKey, but could not find the associated model.", "<magenta>Polyglot:UrlRewriter</magenta>");
        }
    }

    private function rewriteOrdinaryTaxonomy($taxonomyKey, $config)
    {
        $slug = $taxonomyKey;
        if (isset($config->rewrite) && isset($config->rewrite['slug'])) {
            $slug = $config->rewrite['slug'];
        }
        $this->addTaxonomyRewrites($slug, $taxonomyKey);
    }


    private function addTaxonomyRewrites($slug, $taxonomyKey)
    {
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?'.$taxonomyKey.'=$matches[3]&feed=$matches[4]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/([^/]+)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?'.$taxonomyKey.'=$matches[3]&feed=$matches[4]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/([^/]+)/embed/?$', 'index.php?'.$taxonomyKey.'=$matches[3]&embed=true&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?'.$taxonomyKey.'=$matches[3]&paged=$matches[4]&locale=$matches[1]');
        $this->rewriter->addRule('('.$this->urlRegex.')/('.$slug.')/([^/]+)/?$', 'index.php?'.$taxonomyKey.'=$matches[3]&locale=$matches[1]');
    }

}
