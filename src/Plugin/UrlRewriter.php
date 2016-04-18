<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\I18n\I18n;
use Strata\Utility\Hash;
use Strata\Model\CustomPostType\CustomPostType;
use Strata\Model\Taxonomy\Taxonomy;


use Polyglot\Plugin\Polyglot;
use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Db\Query;

use Polyglot\I18n\Request\Router\PolyglotRouter;
use Polyglot\I18n\Request\Rewriter\TaxonomyRewriter;
use Polyglot\I18n\Request\Rewriter\CustomPostTypeRewriter;
use Polyglot\I18n\Request\Rewriter\DefaultWordpressRewriter;
use Polyglot\I18n\Request\Rewriter\HomepageRewriter;

use WP_Post;
use WP_Term;
use Exception;

class UrlRewriter {

    private $polyglot = null;

    function __construct()
    {
        $this->polyglot = Polyglot::instance();
    }

    public function registerHooks()
    {
        add_filter('post_link', array($this, 'postLink'), 1, 2);
        add_filter('post_type_link', array($this, 'postLink'), 1, 2);
        add_filter('page_link', array($this, 'postLink'), 1, 2);
        add_filter('query_vars', array($this, 'addQueryVars'));
        add_filter('term_link', array($this, 'termLink'), 1, 3);

        add_filter('wp_nav_menu_objects', array($this, 'wpNavMenuObjects'), 1, 2);

        if (!is_admin()) {
            add_action('strata_on_before_url_routing', array($this, "runOriginalRoute"), 5, 1);

            add_action('init', array($this, 'addLocaleRewrites'));
            add_action('widgets_init', array($this, 'forwardCanonicalUrls'));

            add_filter('redirect_canonical', array($this, 'redirectCanonical'), 10, 2);
        }
    }

    public function runOriginalRoute($route = null)
    {
        global $wp_query;
        return PolyglotRouter::localizeRouteByQuery($wp_query, $route);
    }

    /**
     * Adds all the rewrites required by the current setup of the locale configuration.
     */
    public function addLocaleRewrites()
    {
        $configuration = $this->polyglot->getConfiguration();
        $strataRewriter = Strata::rewriter();
        $i18n = Strata::i18n();

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
        $rewriter->setDefaultHomepageId($this->polyglot->query()->getDefaultHomepageId());
        $rewriter->rewrite();

        // Translate the default slugs
        $rewriter = new DefaultWordpressRewriter($i18n, $strataRewriter);
        $rewriter->setConfiguration($configuration);
        $rewriter->rewrite();
    }

    public function wpNavMenuObjects($sortedMenuItems, $args)
    {
        $currentLocale = $this->polyglot->getCurrentLocale();
        $defaultLocale = $this->polyglot->getDefaultLocale();

        if (!$currentLocale->isDefault()) {

            $count = 1; // really starts at 1?!
            $textdomain = $this->polyglot->getTextdomain();
            $currentPageId = (int)get_the_ID();
            foreach ($sortedMenuItems as $wpPost) {
                if (is_a($wpPost, '\WP_Post')) {
                    if ($currentLocale->hasPostTranslation($wpPost->object_id)) {

                        $translatedInfo = $currentLocale->getTranslatedPost($wpPost->object_id);
                        $defaultInfo = $defaultLocale->getTranslatedPost($wpPost->object_id);

                        // The title isn't carried away, if it matches the post title,
                        // then use the translation. Otherwise, pass it along gettext

                        if ($defaultInfo->post_title === $wpPost->title) {
                            $sortedMenuItems[$count]->title = $translatedInfo->post_title;
                        } else {
                            $sortedMenuItems[$count]->title = __($sortedMenuItems[$count]->title, $textdomain);
                        }

                        $sortedMenuItems[$count]->url = get_permalink($translatedInfo->ID);

                        // Because we don't want to lose the added menu data of the previous item,
                        // replace every matching key from this translation.
                        foreach ($translatedInfo as $key => $data) {
                            $sortedMenuItems[$count]->{$key} = $data;
                        }

                        if ($currentPageId === (int)$translatedInfo->ID) {
                            $sortedMenuItems[$count]->current = true;
                            $sortedMenuItems[$count]->classes[] = "active";
                        }
                    }
                }
                $count++;
            }
        }

        return $sortedMenuItems;
    }


    /**
     * Declares the query parameter for the locale.
     * @see query_vars
     * @param array $qv
     * @return array
     */
    public function addQueryVars($qv)
    {
        $qv[] = 'locale';
        return $qv;
    }

    public function redirectCanonical($redirectUrl, $requestedUrl = null)
    {
        foreach ($this->polyglot->getLocales() as $locale) {
            // If WP wants to redirect to the root locale page, prevent the redirect
            if ($locale->getHomeUrl() === $requestedUrl) {
                return $requestedUrl;
            }
        }

        return $redirectUrl;
    }

    public function forwardCanonicalUrls()
    {
        $homepageId = $this->polyglot->query()->getDefaultHomepageId();
        $currentLocale = $this->polyglot->getCurrentLocale();

        // Check for a localized homepage
        if ($currentLocale->isTranslationOfPost($homepageId)) {
            $localizedPage = $currentLocale->getTranslatedPost($homepageId);
            if ($localizedPage) {
                if ($_SERVER['REQUEST_URI'] === '/' . $currentLocale->getUrl() . '/' .$localizedPage->post_name . '/') {
                    wp_redirect(WP_HOME . '/' . $currentLocale->getUrl() . '/', 301);
                    exit;
                }
            }
        }

    }

    /**
     * Appends the current language URL identifier to the
     * post link.
     * @param  string  $postLink
     * @param  integer $postId
     * @return string            Localized URL.
     */
    public function postLink($postLink, $mixed = 0)
    {
        $postId = is_object($mixed) ? $mixed->ID : $mixed;

        // Try to find an associated post translation.
        $tree = $this->getTranslationTree($postId);

        if ($tree) {
            $translationEntity = $tree->getTranslatedObject($postId, "WP_Post");
            if ($translationEntity) {
                $post = $translationEntity->loadAssociatedWPObject();
                $postLocale = $this->polyglot->getLocaleByCode($translationEntity->translation_locale);
                if (!is_null($postLocale) && !is_null($post) && !wp_is_post_revision($post->ID)) {
                    return $this->parseLocalizablePostLink($postLink, $post, $postLocale);
                }
             } elseif ($tree->isTranslationSetOf($postId, "WP_Post") && !wp_is_post_revision($postId)) {

                $currentLocale = $this->polyglot->getCurrentLocale();
                if (!$currentLocale->isDefault() && $this->shouldFallbackToDefault()) {
                    return $this->parseLocalizablePostLink($postLink, get_post($postId), $currentLocale);
                }

                return $this->parseLocalizablePostLink($postLink, get_post($postId), $this->polyglot->getDefaultLocale());
            }
        }

        // We haven't found an associated post,
        // therefore the link provided is the correct one.
        return $this->parseIgnoredPostLink($postLink);
    }

    public function getLocalizedFallbackUrl($originalUrl, $locale)
    {
        // Remove the possible fake url prefix when fallbacking
        if ((bool)Strata::config("i18n.default_locale_fallback")) {
            return str_replace(Strata::i18n()->getCurrentLocale()->getHomeUrl(), $locale->getHomeUrl(), $originalUrl);
        }

        return str_replace(get_home_url(), $locale->getHomeUrl(), $originalUrl);
    }

    /**
     * Returns the default term link to add the current locale prefix
     * to the generated link, if applicable.
     * @param  string $url
     * @param  WP_Term $term
     * @param  string $taxonomy
     * @return string
     */
    public function termLink($url, WP_Term $term, $taxonomy)
    {
        $configuration = $this->polyglot->getConfiguration();
        if ($configuration->isTaxonomyEnabled($taxonomy)) {
            $locale = $this->polyglot->getCurrentLocale();
            if ($locale && $locale->hasACustomUrl()) {
                $taxonomyDetails = get_taxonomy($taxonomy);
                $taxonomyRootSlug = $taxonomyDetails->rewrite['slug'];
                return $this->replaceFirstOccurance('/' . $taxonomyRootSlug, $locale->getHomeUrl(false) . $taxonomyRootSlug, $url);
            }
        }

        return $termLink;
    }

    protected function parseIgnoredPostLink($postLink)
    {
        $currentLocale = $this->polyglot->getCurrentLocale();

        // Before leaving, check if we are expected to build localized urls when
        // the page does not exist. This ensures the default content is displayed as-if it
        // was a localization of the current locale. (ex: en_US could be the invisible fallback for en_CA).
        if ($this->shouldFallbackToDefault() && $currentLocale->hasACustomUrl()) {
            $regexedBaseHomeUrl = str_replace("//", "\/\/", preg_quote(WP_HOME, "/"));
            return preg_replace("/^$regexedBaseHomeUrl/", WP_HOME . "/" . $currentLocale->getUrl(), $postLink);
        }
        return $postLink;
    }

    protected function parseLocalizablePostLink($postLink, $post, $postLocale)
    {
        // If not already present, add the locale url keys
        $regexedBaseHomeUrl = str_replace("//", "\/\/", preg_quote(WP_HOME, "/"));
        $replacementUrl = !$postLocale->hasACustomUrl() ? '' : "/" . $postLocale->getUrl();
        $localizedUrl = preg_replace("/^$regexedBaseHomeUrl/", WP_HOME . $replacementUrl, $postLink);

        // We have a translated url, but if it happens to be the homepage we
        // need to remove the slug
        return $this->removeLocaleHomeKeys($localizedUrl, $postLocale);
    }

    protected function removeLocaleHomeKeys($url, $localeContext = null)
    {
        if (is_null($localeContext)) {
            $localeContext = $this->polyglot->getCurrentLocale();
        }

        // Check for a localized homepage
        $homepageId = $this->polyglot->query()->getDefaultHomepageId();
        if ($localeContext->isTranslationOfPost($homepageId)) {
            $localizedPage = $localeContext->getTranslatedPost($homepageId);
            if ($localizedPage) {
                return str_replace($localizedPage->post_name . "/", "", $url);
            }
        }

        return $url;
    }

    private function shouldFallbackToDefault()
    {
        return (bool)Strata::config("i18n.default_locale_fallback");
    }

    private function isATranslatedPost($post)
    {
        if (is_null($post)) {
            return false;
        }

        $configuration = $this->polyglot->getConfiguration();
        if (!$configuration->isTypeEnabled($post->post_type)) {
            return false;
        }

        return count($this->polyglot->query()->findDetailsById($post->ID)) > 0;
    }


    private function getTranslationTree($mixedId, $mixedKind = "WP_Post")
    {
        $originalId = $this->getOriginalObjectId($mixedId, $mixedKind);
        return Polyglot::instance()->query()->findTranlationsOfId($originalId, $mixedKind);
    }

    private function getOriginalObjectId($mixedId, $mixedKind)
    {
        $localizedDetails = Polyglot::instance()->query()->findDetailsById($mixedId, $mixedKind);
        if ($localizedDetails && !is_null($localizedDetails->translation_of)) {
            return (int)$localizedDetails->translation_of;
        }

        // Assume this object is the original since it had no translation
        return (int)$mixedId;
    }

    private function replaceFirstOccurance($from, $to, $subject)
    {
        $from = '/' . preg_quote($from, '/') . '/';
        return preg_replace($from, $to, $subject, 1);
    }
}
