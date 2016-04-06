<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\I18n\I18n;
use Strata\Utility\Hash;
use Strata\Model\CustomPostType\CustomPostType;

use Polyglot\Plugin\Polyglot;
use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Db\Query;

use WP_Post;
use Exception;

class UrlRewriter {

    private $polyglot = null;

    function __construct()
    {
        $this->polyglot = Polyglot::instance();
    }

    public function registerHooks()
    {
        add_filter('post_link', array($this, 'postLink'), 1, 3);
        add_filter('post_type_link', array($this, 'postLink'), 1, 3);
        add_filter('page_link', array($this, 'postLink'), 1, 3);
        add_filter('query_vars', array($this, 'addQueryVars'));
        add_filter('term_link', array($this, 'termLink'));

        add_filter('wp_nav_menu_objects', array($this, 'wpNavMenuObjects'), 1, 2);

        if (!is_admin()) {
            add_action('strata_on_before_url_routing', array($this, "runOriginalRoute"), 5, 1);

            add_action('init', array($this, 'addLocaleRewrites'));
            add_action('widgets_init', array($this, 'forwardCanonicalUrls'));

            add_filter('redirect_canonical', array($this, 'redirectCanonical'), 10, 2);
        }
    }

    public function runOriginalRoute($routedUrl = null)
    {
        $defaultLocale = $this->polyglot->getDefaultLocale();
        $currentLocale = $this->polyglot->getCurrentLocale();

        $originalPost = $defaultLocale->getTranslatedPost();
        $localizedPost = $currentLocale->getTranslatedPost();
        $currentUrl = WP_HOME . $_SERVER['REQUEST_URI'];

        // Account for search pages which behave differently than regular pages
        if (is_search() && ($currentLocale->hasConfig("rewrite.search_base") || $currentLocale->isDefault())) {
            global $wp_rewrite;

            $impliedUrl = str_replace(
                $currentLocale->getHomeUrl() . $currentLocale->getConfig("rewrite.search_base") . "/",
                $defaultLocale->getHomeUrl() . $wp_rewrite->search_base . "/",
                $currentUrl
            );

            return $this->makeUrlFragment($impliedUrl, $defaultLocale);
        }

        // Validate the presence of a localized version because we could
        // be in fallback to the original post.
        // When in fallback mode, we must send the original url stripped
        // locale code which is meaningless at that point.
        if ($localizedPost) {
            if ($this->isLocalizedPost($originalPost, $localizedPost) || $this->isFallbackPost($originalPost, $localizedPost)) {

                // Get permalink will append the current locale url when
                // the configuration allows locales to present content form
                // the default.
                $originalUrl = str_replace($currentLocale->getHomeUrl(), "/", get_permalink($originalPost->ID));

                // At this point we have a working permalink but maybe the
                // original url had additional information afterwards.
                // Ex: A case CPT registered sub pages url.
                $localizedUrl = get_permalink($localizedPost->ID);

                if (!$currentLocale->isDefault()) {
                    $cpt = CustomPostType::factoryFromKey($localizedPost->post_type);
                    $key = "i18n.".$currentLocale->getCode().".rewrite.slug";
                    if ($cpt->hasConfig($key)) {
                        $localizedUrl = str_replace($cpt->getConfig($key), $cpt->getConfig("rewrite.slug"), $localizedUrl);
                        $currentUrl = str_replace($cpt->getConfig($key), $cpt->getConfig("rewrite.slug"), $currentUrl);
                    }
                }

                $remaningBits = str_replace($localizedUrl, "", $currentUrl);
                $originalUrl .= $remaningBits;

                return $this->makeUrlFragment($originalUrl, $defaultLocale);
            }
        } elseif($originalPost) {
            $originalUrl = str_replace($currentLocale->getUrl(), "", get_permalink($originalPost->ID));
            return $this->makeUrlFragment($originalUrl, $defaultLocale);
        }

        return $this->makeUrlFragment($currentUrl, $currentLocale);
    }

    private function makeUrlFragment($impliedUrl, $inLocale)
    {
        if ($inLocale->hasACustomUrl()) {
            $impliedUrl = str_replace($inLocale->getHomeUrl(), "/", $impliedUrl);
        }

        $path = parse_url($impliedUrl, PHP_URL_PATH);
        $query = parse_url($impliedUrl, PHP_URL_QUERY);
        $fragment = parse_url($impliedUrl, PHP_URL_FRAGMENT);

        return $path .
            (empty($query) ? $query : '?' . $query) .
            (empty($fragment) ? $fragment : '#' . $fragment);
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

    private function isLocalizedPost($originalPost, $localizedPost)
    {
        return !is_null($originalPost) && !is_null($localizedPost);
    }

    private function isFallbackPost($originalPost, $localizedPost)
    {
        return !is_null($originalPost) && is_null($localizedPost);
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

    /**
     * Adds all the rewrites required by the current setup of the locale configuration.
     */
    public function addLocaleRewrites()
    {
        $configuration = $this->polyglot->getConfiguration();
        $regex = $this->getLocaleUrlsRegex();

        // Translate the default slugs
        $this->openRewriteForTranslations($regex);

        // Custom Post Types
        $postTypes = $configuration->getPostTypes();
        if (count($postTypes)) {
            foreach ($postTypes as $postTypekey => $config) {
                if ($postTypekey !== 'post' && $postTypekey !== 'page' && $postTypekey !== 'attachment' && $postTypekey !== 'revision') {

                    // Look for Strata configuration that would help translate the slugs.
                    if (preg_match("/^cpt_.*/", $postTypekey)) {
                        try {
                            $cpt = CustomPostType::factory(substr($postTypekey, 4));

                            $localizedSlugs = array_merge(
                                array($cpt->hasConfig("rewrite.slug") ? $cpt->getConfig("rewrite.slug") : $postTypekey),
                                $cpt->extractConfig("i18n.{s}.rewrite.slug")
                            );

                            $this->addCustomPostTypeRewrites($regex, implode("|", $localizedSlugs), $postTypekey);

                        } catch (Exception $e) {
                            Strata::app()->log("Tried to translate $slug, but could not find the associated model.", "[Polyglot:UrlRewriter]");
                        }
                    // Handle vanilla custom post types
                    } else {
                        $slug = $postTypekey;
                        if (isset($config->rewrite) && isset($config->rewrite['slug'])) {
                            $slug = $config->rewrite['slug'];
                        }
                        $this->addCustomPostTypeRewrites($regex, $slug, $postTypekey);
                    }

                }
            }
        }

        // Pages
        if ($configuration->isTypeEnabled('page')) {
            Strata::app()->rewriter->addRule('('.$regex.')/(.?.+?)/?$', 'index.php?pagename=$matches[2]&locale=$matches[1]');
        }

        // Posts
        if ($configuration->isTypeEnabled('post')) {
            Strata::app()->rewriter->addRule('('.$regex.')/([^/]+)/?$', 'index.php?name=$matches[2]&locale=$matches[1]');
        }

        // Rewrite for categories
        if (count($configuration->getTaxonomies())) {
            $this->addCategoryRules();
        }

        // Rewrite for localized homepages.
        $this->addHomepagesRules();
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

    public function termLink($termLink)
    {
        $locale = $this->polyglot->getCurrentLocale();
        if ($locale && !$locale->isDefault()) {
            // Don't replace already formatted urls.
            $regexed = preg_quote($locale->getUrl(), '/');
            if (!preg_match("/(index.php)?\/".$regexed."\//", $termLink)) {
                $home = str_replace("//", "\/\/", preg_quote(WP_HOME));
                $regex = "$home\/(index.php\/)?(.*)?";
                return preg_replace("/$regex/", WP_HOME . "/$1" . $locale->getUrl() . "/$2", $termLink);
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

    private function getLocaleUrls()
    {
        return array_map(function($locale) { return $locale->getUrl(); }, $this->polyglot->getLocales());
    }

    private function getLocaleUrlsRegex()
    {
        return implode("|", $this->getLocaleUrls());
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

    // Allows renaming of the global slugs
    private function openRewriteForTranslations($regex)
    {
        global $wp_rewrite;
        $rewriter = Strata::rewriter();
        $keys = array(
            'pagination_base',
            'author_base',
            'comments_base',
            'feed_base',
            'search_base',
            'category_base',
            'tag_base'
        );

        foreach ($keys as $key) {
            $possibleValues = array();
            foreach ($this->polyglot->getLocales() as $locale) {
                if ($locale->hasConfig("rewrite." . $key)) {
                    $possibleValues[] = $locale->getConfig("rewrite." . $key);
                } elseif ($locale->hasACustomUrl() && property_exists($wp_rewrite, $key)) {
                    $possibleValues[] = $wp_rewrite->{$key};
                }
            }

            if (count($possibleValues)) {
                $rewriter->addRule('('.$regex.')/('.implode("|", $possibleValues).')/(.+)$', 'index.php?s=$matches[3]&locale=$matches[1]');
            }
        }
    }

    /**
     * Adds the basic rules for pointing the default locale
     * directory to the translated version of the homepage.
     */
    private function addHomepagesRules()
    {
        $homepageId = $this->polyglot->query()->getDefaultHomepageId();
        $defaultLocale = $this->polyglot->getDefaultLocale();
        $rewriter = Strata::app()->rewriter;

        foreach ($this->polyglot->getLocales() as $locale) {
            if ($locale->hasACustomUrl()) {
                $localizedPage = $locale->getTranslatedPost($homepageId);

                if (is_null($localizedPage) && $this->shouldFallbackToDefault()) {
                    $localizedPage = $defaultLocale->getTranslatedPost($homepageId);
                }

                if (!is_null($localizedPage)) {
                    $pagename = $localizedPage->post_name;
                    $url = $locale->getUrl();
                    $rewriter->addRule("$url/?$", "index.php?pagename=$pagename");
                }
            }
        }
    }

    private function addCategoryRules()
    {
        $regex = $this->getLocaleUrlsRegex();
        $rewriter = Strata::app()->rewriter;

        $rewriter->addRule('('.$regex.')/category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?category_name=$matches[2]&feed=$matches[3]&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/category/(.+?)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?category_name=$matches[2]&feed=$matches[3]&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/category/(.+?)/page/?([0-9]{1,})/?$', 'index.php?category_name=$matches[2]&paged=$matches[3]&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/category/(.+?)/?$', 'index.php?category_name=$matches[2]&locale=$matches[1]');
    }

    private function addCustomPostTypeRewrites($regex, $slug, $postTypekey)
    {
        $rewriter = Strata::app()->rewriter;

        $rewriter->addRule('('.$regex.')/('.$slug.')/[^/]+/attachment/([^/]+)/?$', 'index.php?attachment=$matches[3]&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/('.$slug.')/[^/]+/attachment/([^/]+)/trackback/?$', 'index.php?attachment=$matches[3]&tb=1&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/('.$slug.')/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?attachment=$matches[3]&feed=$matches[4]&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/('.$slug.')/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$', 'index.php?attachment=$matches[3]&cpage=$matches[4]&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/('.$slug.')/([^/]+)/trackback/?$', 'index.php?'.$postTypekey.'=$matches[3]&tb=1&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/('.$slug.')/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?'.$postTypekey.'=$matches[3]&paged=$matches[4]&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/('.$slug.')/([^/]+)/comment-page-([0-9]{1,})/?$', 'index.php?'.$postTypekey.'=$matches[3]&cpage=$matches[4]&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/('.$slug.')/([^/]+)(/[0-9]+)?/?$', 'index.php?'.$postTypekey.'=$matches[3]&page=$matches[4]&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/('.$slug.')/[^/]+/([^/]+)/trackback/?$', 'index.php?attachment=$matches[3]&tb=1&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/('.$slug.')/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?attachment=$matches[3]&feed=$matches[4]&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/('.$slug.')/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?attachment=$matches[3]&feed=$matches[4]&locale=$matches[1]');
        $rewriter->addRule('('.$regex.')/('.$slug.')/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$', 'index.php?attachment=$matches[3]&feed=$matches[4]&locale=$matches[1]');
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

}
