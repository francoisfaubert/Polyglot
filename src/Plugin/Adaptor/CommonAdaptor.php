<?php

namespace Polyglot\Plugin\Adaptor;

use Polyglot\I18n\Locale\ContextualManager;
use Polyglot\I18n\Translation\TrashManager;
use Polyglot\I18n\Permalink\PostPermalinkManager;
use Polyglot\I18n\Permalink\TermPermalinkManager;
use Polyglot\I18n\Db\QueryRewriter;

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
        add_action('save_post', array($querier, 'localizePostTerms'), 1, 3);
        add_filter('wp_insert_post_data', array($querier, 'localizeParentId'), 10, 2);
        add_action('created_term', array($querier, 'localizeExistingTerms'), 1, 3);

        if (is_admin()) {
            add_filter('get_terms', array($querier, 'getTerms'), 1, 3);
        } else {
            add_filter('get_terms_args', array($querier, 'getTermsArgs'), 10, 2);
        }
    }
}
