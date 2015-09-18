<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\I18n\I18n;

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
        add_filter('page_link', array($this, 'postLink'), 1, 3);
        add_filter('query_vars', array($this, 'addQueryVars'));
        add_filter('term_link', array($this, 'termLink'));

        if (!is_admin()) {

            $locale = $this->polyglot->getCurrentLocale();
            if (!$locale->isDefault()) {
                add_action('strata_on_before_url_routing', array($this, "runOriginalRoute"), 1, 1);
            }

            add_action('widgets_init', array($this, 'addLocaleRewrites'));
            add_action('widgets_init', array($this, 'forwardCanonicalUrls'));

            add_filter('redirect_canonical', array($this, 'redirectCanonical'), 10, 2);

        }
    }

    public function runOriginalRoute($routedUrl)
    {
        $defaultLocale = $this->polyglot->getDefaultLocale();
        $currentLocale = $this->polyglot->getCurrentLocale();
        $originalPost = $defaultLocale->getTranslatedPost();

        if ($originalPost) {

            // Get permalink will append the current locale url when
            // the configuration allows locales to present content form
            // the default.
            $originalUrl = str_replace("/" . $currentLocale->getUrl(), "", get_permalink($originalPost->ID));

            $originalPath =
                parse_url($originalUrl, PHP_URL_PATH) .
                parse_url($originalUrl, PHP_URL_QUERY) .
                parse_url($originalUrl, PHP_URL_FRAGMENT);

            return $originalPath;
        }

        return $routedUrl;
    }

    /**
     * Declares the query parameter for the locale.
     * @see query_vars
     * @param array $qv
     * @return array
     */
    public function addQueryVars($qv)
    {    $qv[] = 'locale';
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
        $regex = $this->getLocaleUrlsRegex();

        // Translate the default slugs
        $this->openRewriteForTranslations();

        // Pages
        add_rewrite_rule('('.$regex.')/(.?.+?)/?$', 'index.php?pagename=$matches[2]&locale=$matches[1]', "top");
        add_rewrite_rule('index.php('.$regex.')/(.?.+?)/?$', 'index.php?pagename=$matches[2]&locale=$matches[1]', "top");

        // Posts
        add_rewrite_rule('('.$regex.')/([^/]+)/?$', 'index.php?name=$matches[2]&locale=$matches[1]', "top");
        add_rewrite_rule('index.php/('.$regex.')/([^/]+)/?$', 'index.php?name=$matches[2]&locale=$matches[1]', "top");

        // Rewrite for categories
        $this->addCategoryRules();

        // Rewrite for localized homepages.
        $this->addHomepagesRules();

        // @todo : trigger intelligently
        flush_rewrite_rules();
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

        // When there's no localized hoempage but we have to fallback to the
        // default locale, then
        if ((bool)Strata::app()->getConfig("i18n.default_locale_fallback")) {
            // doesn't work.
            // global $post;
            // $post = get_post($homepageId);
            // setup_postdata($post);
            // exit;
            // debug($post);
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
        $post = null;
        $postLocale = null;
        $currentLocale = $this->polyglot->getCurrentLocale();

        // Try to find an associated post translation.
        $postId = is_object($mixed) ? $mixed->ID : $mixed;
        $tree = $this->getTranslationTree($postId);
        if ($tree) {
            $translationEntity = $tree->getTranslatedObject($postId, "WP_Post");
            if ($translationEntity) {
                $post = $translationEntity->loadAssociatedWPObject();
                $postLocale = $this->polyglot->getLocaleByCode($translationEntity->translation_locale);
            }
        }

        // We haven't found an associated post,
        // therefore the link provided is the correct one.
        if (is_null($post)) {
            // Before leaving, check if we are expected to build localized urls when
            // the page does not exist.
            if ((bool)Strata::app()->getConfig("i18n.default_locale_fallback")) {
                if (!$currentLocale->isDefault()) {
                    $regexedBaseHomeUrl = str_replace("//", "\/\/", preg_quote(WP_HOME, "/"));
                    return preg_replace("/^$regexedBaseHomeUrl/", WP_HOME . "/" . $currentLocale->getUrl(), $postLink);
                }
            }

            return $postLink;
        }

        if (isset($postLocale) && !$postLocale->isDefault()) {
            $translation = $postLocale->getTranslatedPost($post->ID);
            if ($translation && $translation->post_type !== "revision") {
                $regexedBaseHomeUrl = str_replace("//", "\/\/", preg_quote(WP_HOME, "/"));

                $localizedUrl = preg_replace("/^$regexedBaseHomeUrl/", WP_HOME . "/" . $postLocale->getUrl(), $postLink);

                // We have a translated url, but if it happens to be the homepage we
                // need to remove the slug
                $homepageId = $this->polyglot->query()->getDefaultHomepageId();

                // Check for a localized homepage
                if ($currentLocale->isTranslationOfPost($homepageId)) {
                    $localizedPage = $currentLocale->getTranslatedPost($homepageId);
                    $localizedUrl = str_replace($localizedPage->post_name . "/", "", $localizedUrl);
                }

                return $localizedUrl;
            }
        }

        return $postLink;
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
    private function openRewriteForTranslations()
    {
        global $wp_rewrite;

        $wp_rewrite->pagination_base = __('page', I18n::DOMAIN);
        $wp_rewrite->author_base = __('author', I18n::DOMAIN);
        $wp_rewrite->comments_base = __('comments', I18n::DOMAIN);
        $wp_rewrite->feed_base = __('feed', I18n::DOMAIN);
        $wp_rewrite->search_base = __('search', I18n::DOMAIN);
        $wp_rewrite->set_category_base( __('category', I18n::DOMAIN) . "/");
        $wp_rewrite->set_tag_base( __('tag', I18n::DOMAIN) . "/" );
    }

    /**
     * Adds the basic rules for pointing the default locale
     * directory to the translated version of the homepage.
     */
    private function addHomepagesRules()
    {
        $homepageId = $this->polyglot->query()->getDefaultHomepageId();

        foreach ($this->polyglot->getLocales() as $locale) {
            $localizedPage = $locale->getTranslatedPost($homepageId);
            if (!is_null($localizedPage)) {
                $pagename = $localizedPage->post_name;
                $url = $locale->getUrl();
                add_rewrite_rule("$url/?$", "index.php?pagename=$pagename", "top");
                add_rewrite_rule("index.php/$url/?$", "index.php?pagename=$pagename", "top");
            }
        }
    }

    private function addCategoryRules()
    {
        $regex = $this->getLocaleUrlsRegex();

        add_rewrite_rule('('.$regex.')/category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?category_name=$matches[2]&feed=$matches[3]&locale=$matches[1]', 'top');
        add_rewrite_rule('('.$regex.')/category/(.+?)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?category_name=$matches[2]&feed=$matches[3]&locale=$matches[1]', 'top');
        add_rewrite_rule('('.$regex.')/category/(.+?)/page/?([0-9]{1,})/?$', 'index.php?category_name=$matches[2]&paged=$matches[3]&locale=$matches[1]', 'top');
        add_rewrite_rule('('.$regex.')/category/(.+?)/?$', 'index.php?category_name=$matches[2]&locale=$matches[1]', 'top');
        add_rewrite_rule('index.php/('.$regex.')/category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$', 'index.php?category_name=$matches[2]&feed=$matches[3]&locale=$matches[1]', 'top');
        add_rewrite_rule('index.php/('.$regex.')/category/(.+?)/(feed|rdf|rss|rss2|atom)/?$', 'index.php?category_name=$matches[2]&feed=$matches[3]&locale=$matches[1]', 'top');
        add_rewrite_rule('index.php/('.$regex.')/category/(.+?)/page/?([0-9]{1,})/?$', 'index.php?category_name=$matches[2]&paged=$matches[3]&locale=$matches[1]', 'top');
        add_rewrite_rule('index.php/('.$regex.')/category/(.+?)/?$', 'index.php?category_name=$matches[2]&locale=$matches[1]', 'top');
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
