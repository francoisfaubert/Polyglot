<?php

namespace Polyglot\I18n\Permalink;

use Strata\Strata;
use Strata\Model\CustomPostType\ModelEntity;
use Strata\I18n\I18n;
use Strata\I18n\I18n\Locale;

use Polyglot\I18n\Translation\Tree;
use Polyglot\I18n\Utility;

use WP_Post;
use WP_Term;

class PostPermalinkManager extends PermalinkManager {

    /**
     * Ensures post and page links are wrapped in the current active
     * country.
     * @param  string $permalink
     * @param  int $postId
     * @return string
     */
    public function filter_onPostLink($permalink, $postId)
    {
        $postId = is_object($postId) ? $mixed->ID : $postId;

        if (wp_is_post_revision($postId)) {
            return $this->parseIgnoredPostLink($permalink);
        }

        $tree = Tree::grow($postId, "WP_Post");
        if ($tree->isLocalized()) {
            $localizedEntity = $tree->getLocalizedObjectById($postId);
            if ($localizedEntity) {

                $obj = $localizedEntity->getWordpressObject();
                $postLocale = $localizedEntity->getTranslationLocale();
                $permalink = $this->localizePostSlug($permalink, $obj, $postLocale);

                return $this->parseLocalizablePostLink(
                    $permalink,
                    $obj,
                    $postLocale
                );

            } elseif ($tree->isLocalizedSetOf($postId)) {
                if ($this->shouldLocalizeByFallback) {
                    $permalink = $this->localizePostSlug($permalink, get_post($postId), $this->defaultLocale);
                    return $this->parseLocalizablePostLink($permalink, get_post($postId), $this->currentLocale);
                }

                return $this->parseLocalizablePostLink($permalink, get_post($postId), $this->defaultLocale);
            }
        }

        // We haven't found an associated post,
        // therefore the link provided is the correct one.
        return $this->parseIgnoredPostLink($permalink);
    }

    /**
     * Ensures post and page links are wrapped in the current active
     * category.
     * @param  string $permalink
     * @param  WP_Post $post
     * @return string
     */
    public function filter_onCptLink($permalink, WP_Post $post)
    {
        return $this->filter_onPostLink($permalink, $post->ID);
    }

    // Before leaving, check if we are expected to build localized urls when
    // the page does not exist. This ensures the default content is displayed as if it
    // was a localization of the current locale. (ex: en_US could be the invisible fallback for en_CA).
    protected function parseIgnoredPostLink($permalink)
    {
        if ($this->currentLocale->hasACustomUrl()) {
            return Utility::replaceFirstOccurence(
                get_home_url() . "/",
                $this->currentLocale->getHomeUrl(),
                $permalink
            );
        }

        return $permalink;
    }

    protected function parseLocalizablePostLink($permalink, $post, $postLocale)
    {
        $localizedUrl = Utility::replaceFirstOccurence(
            get_home_url(). "/",
            $postLocale->getHomeUrl(),
            $permalink
        );

        // We have a translated url, but if it happens to be the homepage we
        // need to remove the slug
        return $this->removeLocaleHomeKeys($localizedUrl, $postLocale);
    }

    protected function localizePostSlug($permalink, $post, $postLocale)
    {
        try {
            $modelEntity = ModelEntity::factoryFromString($post->post_type);
            $model = $modelEntity->getModel();

            if (!$postLocale->isDefault() && $model->hasConfig("i18n." . $postLocale->getCode() . ".rewrite.slug")) {
                return Utility::replaceFirstOccurence(
                    $model->getConfig("rewrite.slug"),
                    $model->getConfig("i18n." . $postLocale->getCode() . ".rewrite.slug"),
                    $permalink
                );
            }
        } catch(Exception $e) {
            // we dont care
        }

        return $permalink;
    }

    protected function removeLocaleHomeKeys($permalink, $localeContext = null)
    {
        if (is_null($localeContext)) {
            $localeContext = $this->currentLocale;
        }

        $homepageId = Strata::i18n()->query()->getDefaultHomepageId();
        if ($localeContext->isTranslationOfPost($homepageId)) {
            $localizedPage = $localeContext->getTranslatedPost($homepageId);
            if ($localizedPage) {
                return Utility::replaceFirstOccurence($localizedPage->post_name . "/", "", $permalink);
            }
        }

        return $permalink;
    }

    public function getLocalizedFallbackUrl($permalink, $locale)
    {
        // Remove the possible fake url prefix when fallbacking
        if ((bool)Strata::config("i18n.default_locale_fallback")) {
            return str_replace(Strata::i18n()->getCurrentLocale()->getHomeUrl(), $locale->getHomeUrl(), $permalink);
        }

        return str_replace(get_home_url(), $locale->getHomeUrl(), $permalink);
    }
}
