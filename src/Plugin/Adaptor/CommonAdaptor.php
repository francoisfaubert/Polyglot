<?php

namespace Polyglot\Plugin\Adaptor;

use Polyglot\I18n\Locale\ContextualManager;
use Polyglot\I18n\Translation\TrashManager;
use Polyglot\I18n\Permalink\PostPermalinkManager;
use Polyglot\I18n\Permalink\TermPermalinkManager;
use Polyglot\I18n\Db\QueryRewriter;
use Polyglot\I18n\Request\Rewriter\TaxonomyRewriter;
use Polyglot\I18n\Request\Rewriter\CustomPostTypeRewriter;
use Polyglot\I18n\Request\Rewriter\DefaultWordpressRewriter;
use Polyglot\I18n\Request\Rewriter\HomepageRewriter;
use Polyglot\I18n\Utility;
use Strata\Strata;

class CommonAdaptor {

    public static function addFilters()
    {
        $context = new ContextualManager();
        add_filter('query_vars', array($context, 'filter_onQueryVars'));
        add_filter('strata_i18n_set_current_locale_by_context', array($context, "filter_onSetStrataContext"));

        $trash = new TrashManager();
        $trash->setQuerier(Strata::i18n()->query());
        $trash->addFilters();

        $adaptor = new self();
        add_action('init', array($adaptor, 'filter_onInit'));

        $postPermalink = new PostPermalinkManager();
        add_filter('post_link', array($postPermalink, "filter_onCptLink"), 5, 2);
        add_filter('post_type_link', array($postPermalink, "filter_onCptLink"), 5, 2);
        add_filter('page_link', array($postPermalink, "filter_onPostLink"), 5, 2);

        $termPermalink = new TermPermalinkManager();
        add_filter('term_link', array($termPermalink, 'filter_onTermLink'), 5, 3);

        $querier = new QueryRewriter();
        add_action("pre_get_posts", array($querier, "preGetPosts"));
        add_filter('get_previous_post_where', array($querier, 'filterAdjacentWhere'));
        add_filter('get_next_post_where', array($querier, 'filterAdjacentWhere'));

        // On the ice for the moment.
        // add_action('save_post', array($querier, 'localizePostTerms'), 1, 3);
        // add_filter('wp_insert_post_data', array($querier, 'localizeParentId'), 10, 2);
        // add_action('created_term', array($querier, 'localizeExistingTerms'), 1, 3);


        add_filter('get_terms', array($querier, 'getTerms'), 5, 3);
        add_filter('get_terms_args', array($querier, 'getTermsArgs'), 10, 2);
    }

    public function filter_onInit()
    {
        $strataRewriter = Strata::rewriter();
        $i18n = Strata::i18n();
        $configuration = $i18n->getConfiguration();

        // Taxonomies
        $rewriter = new TaxonomyRewriter($i18n, $strataRewriter);
        $rewriter->setConfiguration($configuration);
        $rewriter->rewrite();

        // Custom Post Types
        $rewriter = new CustomPostTypeRewriter($i18n, $strataRewriter);
        $rewriter->setConfiguration($configuration);
        $rewriter->rewrite();

        // Translate homepages
        $rewriter = new HomepageRewriter($i18n, $strataRewriter);
        $rewriter->setDefaultHomepageId($i18n->query()->getDefaultHomepageId());
        $rewriter->rewrite();

        // Translate the default slugs
        $rewriter = new DefaultWordpressRewriter($i18n, $strataRewriter);
        $rewriter->setConfiguration($configuration);
        $rewriter->rewrite();
    }
}
