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
            add_action('widgets_init', array($this, 'addLocaleRewrites'));
            add_action('widgets_init', array($this, 'forwardCanonicalUrls'));

            add_filter('redirect_canonical', array($this, 'redirectCanonical'), 10, 2);
        }
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

        if ($currentLocale->isTranslationOfPost($homepageId)) {
            $localizedPage = $currentLocale->getTranslatedPost();
            if ($_SERVER['REQUEST_URI'] === '/' . $locale->getUrl() . '/' .$localizedPage->page_name . '/') {
                wp_redirect(WP_HOME . '/' . $locale->getUrl() . '/', 301);
                exit;
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
        $post = is_object($mixed) ? $mixed : $this->polyglot->query()->findPostById((int)$mixed);

        if ($this->isATranslatedPost($post)) {
            $details = $this->polyglot->query()->findDetailsById($post->ID);
            $locale = $this->polyglot->getLocaleByCode($details->translation_locale);

            if ($locale && !$locale->isDefault()) {
                // Don't replace already formatted urls.
                if (!preg_match("/(index.php)?\/".$locale->getUrl()."\//", $postLink)) {
                    $home = str_replace("//", "\/\/", preg_quote(WP_HOME));
                    $regex = "$home\/(index.php\/)?(.*)?";
                    return preg_replace("/$regex/", WP_HOME . "/$1" . $locale->getUrl() . "/$2", $postLink);
                }
            }
        }

        return $postLink;
    }

    public function termLink($termLink)
    {
        $locale = $this->polyglot->getCurrentLocale();
        if ($locale && !$locale->isDefault()) {
            // Don't replace already formatted urls.
            if (!preg_match("/(index.php)?\/".$locale->getUrl()."\//", $termLink)) {
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

}
