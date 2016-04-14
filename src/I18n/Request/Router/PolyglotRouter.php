<?php

namespace Polyglot\I18n\Request\Router;

use Strata\Strata;
use Strata\I18n\I18n;
use WP_Query;

abstract class PolyglotRouter {

    public static function localizeRouteByQuery(WP_Query $wp_query, $route = null)
    {
        if (is_null($route)) {
            $route = $_SERVER['REQUEST_URI'];
        }

        if ($wp_query->is_tax()) {
            $router = new TaxonomyRouter(Strata::i18n());
        } else {
            $router = new PostRouter(Strata::i18n());
        }


        return $router->localizeRoute($route);
    }

    abstract public function localizeRoute($routedUrl = null);

    protected $currentLocale;
    protected $defaultLocale;

    public function __construct(I18n $i18n)
    {
        $this->currentLocale = $i18n->getCurrentLocale();
        $this->defaultLocale = $i18n->getDefaultLocale();
    }

    protected function makeUrlFragment($impliedUrl, $inLocale)
    {
        if ($inLocale->hasACustomUrl()) {
            $impliedUrl = $this->replaceFirstOccurance($inLocale->getHomeUrl(false), "/", $impliedUrl);
        }

        $path = parse_url($impliedUrl, PHP_URL_PATH);
        $query = parse_url($impliedUrl, PHP_URL_QUERY);
        $fragment = parse_url($impliedUrl, PHP_URL_FRAGMENT);

        return $path .
            (empty($query) ? $query : '?' . $query) .
            (empty($fragment) ? $fragment : '#' . $fragment);
    }

    protected function replaceFirstOccurance($from, $to, $subject)
    {
        $from = '/' . preg_quote($from, '/') . '/';
        return preg_replace($from, $to, $subject, 1);
    }
}
