<?php

namespace Polyglot\Plugin\Adaptor;

use Polyglot\I18n\Locale\ContextualManager;
use Polyglot\I18n\Translation\TrashManager;
use Polyglot\I18n\Translation\PostMetaManager;
use Polyglot\I18n\Translation\TermMetaManager;
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
        add_filter('strata_i18n_set_current_locale_by_context', array($context, "filter_onSetStrataContext"), 3, 1);

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
        add_filter('get_terms', array($querier, 'getTerms'), 5, 3);
        add_filter('get_terms_args', array($querier, 'getTermsArgs'), 10, 2);

        $metaManager = new PostMetaManager();
        add_action('save_post', array($metaManager, 'filter_onSavePost'), 1, 3);

        $metaManager = new TermMetaManager();
        add_action('create_term', array($metaManager, 'filter_onCreateTerm'), 1, 3);
        add_action('edit_term', array($metaManager, 'filter_onEditTerm'), 1, 3);
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

        // // Translate the default slugs
        $rewriter = new DefaultWordpressRewriter($i18n, $strataRewriter);
        $rewriter->setConfiguration($configuration);
        $rewriter->rewrite();
    }
}
