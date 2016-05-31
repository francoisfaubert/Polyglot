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
        $ref = new self();
        add_action('wp_loaded', array($ref, 'filter_onInit'), 20);
        add_filter('strata_on_before_url_routing', array($ref, "onStrataRoute"), 5 , 1);
    }

    public function onFilter_body_class($classes)
    {
        $mng = new BodyClassManager($classes);
        return $mng->localize();
    }

    public function filter_onInit()
    {
        add_filter('body_class', array($this, "onFilter_body_class"));

        $navMenu = new NavMenuManager();
        add_filter('wp_nav_menu_objects', array($navMenu, 'filter_onNavMenuObjects'), 5, 2);

        $canonical = new CanonicalManager(new PermalinkManager());
        add_action('wp_head', array($canonical, "filter_onWpHead"));
        add_action('widgets_init', array($canonical, 'filter_onWidgetInit'));
        add_filter('redirect_canonical', array($canonical, 'filter_onRedirectCanonical'), 5, 2);
    }

    public function onStrataRoute($route)
    {
        global $wp_query;
        $route = PolyglotRouter::localizeRouteByQuery($wp_query, $route);
        return $route;
    }
}
