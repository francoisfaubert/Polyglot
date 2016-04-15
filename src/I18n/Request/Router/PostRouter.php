<?php

namespace Polyglot\I18n\Request\Router;

use Strata\Strata;
use Strata\Model\CustomPostType\CustomPostType;

class PostRouter extends PolyglotRouter {

    public function localizeRoute($route = null)
    {
        if ($this->isSearchPage()) {
            return $this->localizeSearchRoute($route);
        }

        $localizedPost = $this->currentLocale->getTranslatedPost();
        $originalPost = $this->defaultLocale->getTranslatedPost();

        if ($localizedPost) {
            if ($this->isTranslatedContent($localizedPost, $originalPost)) {
                return $this->localizeContentRoute($route, $localizedPost, $originalPost);
            }
        }

        if ($originalPost) {
            return $this->localizeContentFallbackRoute($route);
        }

        return $this->makeUrlFragment($route, $this->currentLocale);
    }

    // Account for search pages which behave differently than regular pages
    protected function isSearchPage()
    {
        return is_search() && ($this->currentLocale->hasConfig("rewrite.search_base") || $this->currentLocale->isDefault());
    }

    protected function isTranslatedContent($localizedPost, $originalPost)
    {
        return  $this->isLocalizedPost($originalPost, $localizedPost) ||
                $this->isFallbackPost($originalPost, $localizedPost);
    }

    private function isLocalizedPost($originalPost, $localizedPost)
    {
        return !is_null($originalPost) && !is_null($localizedPost);
    }

    private function isFallbackPost($originalPost, $localizedPost)
    {
        return !is_null($originalPost) && is_null($localizedPost);
    }

    protected function localizeSearchRoute($route)
    {
        global $wp_rewrite;

        $impliedUrl = $this->replaceFirstOccurance(
            $this->currentLocale->getHomeUrl(false) . $this->currentLocale->getConfig("rewrite.search_base") . "/",
            $this->defaultLocale->getHomeUrl(false) . $wp_rewrite->search_base . "/",
            $route
        );

        return $this->makeUrlFragment($impliedUrl, $this->defaultLocale);
    }

    protected function localizeContentRoute($route, $originalPost, $localizedPost)
    {
        // Get permalink will append the current locale url when
        // the configuration allows locales to present content form
        // the default.
        $routedUrl = $this->replaceFirstOccurance($localizedPost->post_name, $originalPost->post_name, $route);
        $originalUrl = $this->replaceFirstOccurance($this->currentLocale->getHomeUrl(false), "/", $routedUrl);

        // At this point we have a working permalink but maybe the
        // original url had additional information afterwards.
        // Ex: A case CPT registered sub pages url.
        if (preg_match('/'.preg_quote($localizedPost->post_name).'\/(.+?)$/', $routedUrl, $matches)) {
            $additionalParameters = $matches[1];

            // Localize back the parameters in the default language
            if (!$this->currentLocale->isDefault()) {
                $cpt = CustomPostType::factoryFromKey($localizedPost->post_type);
                $key = "i18n.".$currentLocale->getCode().".rewrite.slug";
                if ($cpt->hasConfig($key)) {
                    $additionalParameters = $this->replaceFirstOccurance($cpt->getConfig($key), $cpt->getConfig("rewrite.slug"), $additionalParameters);
                }
            }

            $originalUrl .= $additionalParameters;
        }


        return $this->makeUrlFragment($originalUrl, $this->defaultLocale);
    }

    // When in fallback mode, we must send the original url stripped
    // locale code which is meaningless at that point.
    public function localizeContentFallbackRoute($route)
    {
        $originalUrl = $this->replaceFirstOccurance($this->currentLocale->getHomeUrl(), "/", $route);
        return $this->makeUrlFragment($originalUrl, $this->defaultLocale);
    }
}
