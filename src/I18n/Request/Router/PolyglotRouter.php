<?php

namespace Polyglot\I18n\Request\Router;

use Strata\Strata;
use Strata\I18n\I18n;
use Polyglot\I18n\Utility;
use Strata\Utility\Hash;
use Strata\Model\CustomPostType\ModelEntity;
use WP_Query;
use Exception;

abstract class PolyglotRouter {

    public static function localizeRouteByQuery($wp_query, $route = null)
    {
        if (is_null($route)) {
            $route = $_SERVER['REQUEST_URI'];
        }

        if ($wp_query->is_tax()) {
            $router = new TaxonomyRouter(Strata::i18n(), $wp_query);
        } else {
            $router = new PostRouter(Strata::i18n(), $wp_query);
        }

        return $router->localizeRoute($route);
    }

    abstract public function localizeRoute($routedUrl = null);

    protected $currentLocale;
    protected $defaultLocale;
    protected $wp_query;

    public function __construct(I18n $i18n, WP_Query $wp_query)
    {
        $this->wp_query = $wp_query;

        $this->currentLocale = $i18n->getCurrentLocale();
        $this->defaultLocale = $i18n->getDefaultLocale();
    }

    protected function makeUrlFragment($impliedUrl, $inLocale)
    {
        if ($inLocale->hasACustomUrl()) {
            $impliedUrl = Utility::replaceFirstOccurence($inLocale->getHomeUrl(false), "/", $impliedUrl);
        }

        $path = parse_url($impliedUrl, PHP_URL_PATH);
        $query = parse_url($impliedUrl, PHP_URL_QUERY);
        $fragment = parse_url($impliedUrl, PHP_URL_FRAGMENT);

        return $path .
            (empty($query) ? $query : '?' . $query) .
            (empty($fragment) ? $fragment : '#' . $fragment);
    }

    protected function getModelEntityByString($postType)
    {
        try {
            $modelEntity = ModelEntity::factoryFromString($postType);
            return $modelEntity->getModel();
        } catch (Exception $e) {
            // don't care, not a strata model.
        }
    }

    protected function removeLocalizedRoutedSlugs($route, $model)
    {
        if (!$this->currentLocale->isDefault() && is_array($model->routed)) {
            $key = "i18n." . $this->currentLocale->getCode() . ".rewrite";
            if (Hash::check($model->routed, $key)) {
                foreach (Hash::get($model->routed, $key) as $rewriteKey => $rewriteUrl) {
                    if (Hash::check($model->routed, "rewrite.$rewriteKey")) {
                        $defaultValue = Hash::get($model->routed, "rewrite.$rewriteKey");

                        $route = Utility::replaceFirstOccurence(
                            $rewriteUrl,
                            $defaultValue,
                            $route
                        );
                    }
                }
            }
        }

        return $route;
    }
}
