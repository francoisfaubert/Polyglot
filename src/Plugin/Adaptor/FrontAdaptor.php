<?php

namespace Polyglot\Plugin\Adaptor;

use Polyglot\I18n\Permalink\CanonicalManager;
use Polyglot\I18n\Permalink\PermalinkManager;
use Polyglot\I18n\Request\Router\PolyglotRouter;
use Polyglot\I18n\View\BodyClassManager;
use Polyglot\I18n\View\NavMenuManager;
use Strata\Strata;

class FrontAdaptor {

    public static function addFilters()
    {
        $adaptor = new self();
        add_filter('body_class', array($adaptor, "onFilter_body_class"));

        $navMenu = new NavMenuManager();
        add_filter('wp_nav_menu_objects', array($navMenu, 'filter_onNavMenuObjects'), 5, 2);

        $canonical = new CanonicalManager(new PermalinkManager());
        add_action('wp_head', array($canonical, "filter_onWpHead"));
        add_action('widgets_init', array($canonical, 'filter_onWidgetInit'));
        add_filter('redirect_canonical', array($canonical, 'filter_onRedirectCanonical'), 5, 2);

        add_action('strata_on_before_url_routing', function($route) {
            global $wp_query;
            return PolyglotRouter::localizeRouteByQuery($wp_query, $route);
        }, 5, 1);
    }

    public function onFilter_body_class($classes)
    {
        $mng = new BodyClassManager($classes);
        return $mng->localize();
    }
}
