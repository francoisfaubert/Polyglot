<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\I18n\I18n;

use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Db\Query;

use WP_Post;
use Exception;

class PolyglotRewriter {

    private $polyglot = null;

    function __construct()
    {
        global $polyglot;
        $this->polyglot = $polyglot;
    }

    public function registerHooks()
    {
        add_filter('post_link', array($this, 'postLink'), 1, 3);
        add_filter('page_link', array($this, 'postLink'), 1, 3);
        // add_filter('home_url', array($this, 'homeUrl'), 1, 4 );
         add_filter('query_vars', array($this, 'addQueryVars'));

        add_action('init', array($this, 'addLocaleRewrites'));
        add_action('wp_trash_post', array($this, 'onTrash'));
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

        // Rewrite for localized homepages.
        $this->addHomepagesRules();

        // @todo : trigger intelligently
        flush_rewrite_rules();
    }

    public function onTrash($postId)
    {
        $this->query()->unlinkTranslationFor($postId, "WP_Post");
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
        if (is_object($mixed)) {
            $post = $mixed;
        } else {
            $post = $this->polyglot->getCachedPostById((int)$mixed);
        }

        if ($this->isATranslatedPost($post)) {
            $details = $this->polyglot->findTranslationDetails($post);
            $locale = $this->polyglot->getLocaleByCode($details->translation_locale);

            if (!$locale->isDefault()) {
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

    // public function homeUrl($url, $pageId = '')
    // {
    //     if (!empty($pageId)) {
    //         $post = $this->polyglot->getCachedPostById((int)$pageId);
    //         $locale = $this->polyglot->findPostLocale($post);
    //     } else {
    //         $locale = $this->i18n->getCurrentLocale();
    //     }

    //     if (!$locale->isDefault()) {
    //         $home = str_replace("//", "\/\/", preg_quote(WP_HOME));
    //         $regex = "$home\/(index.php\/)?(.*)?";

    //         if (!preg_match("/(index.php)?\/".$locale->getUrl()."\//", $url)) {
    //             return preg_replace("/$regex/", WP_HOME . "/$1" . $locale->getUrl() . "/$2", $url);
    //         }
    //     }
    //     return $url;
    // }

    private function getLocaleUrls()
    {
        return array_map(function($locale) { return $locale->getUrl(); }, $this->polyglot->getLocales());
    }

    private function getLocaleUrlsRegex()
    {
        return implode("|", $this->getLocaleUrls());
    }

    private function generateLocaleHomeUrlList()
    {
        $slugs = array();
        $defaultHomeId = $this->getDefaultHomepageId();

        // We only care if a page is on the front page
        if ($defaultHomeId > 0) {
            $translatedPages = $this->polyglot->findAllTranslationsOf($this->polyglot->getCachedPostById($defaultHomeId));
            foreach ($translatedPages as $page) {
                $slugs[$page->translation_locale] = $page->post_name;
            }
        }

        return $slugs;
    }

    private function hasHomePage()
    {
        return get_option('show_on_front') == "page";
    }

    private function getDefaultHomepageId()
    {
        if ($this->hasHomePage()) {
            return (int)get_option('page_on_front');
        }

        return -1;
    }

    private function isATranslatedPost(WP_Post $post)
    {
        return  !is_null($post)
                && $this->polyglot->isTypeEnabled($post->post_type)
                && $this->polyglot->hasTranslationDetails($post);
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
        $homeUrls = $this->generateLocaleHomeUrlList();
        foreach ($this->polyglot->getLocales() as $locale) {
            $code = $locale->getCode();
            if (array_key_exists($code, $homeUrls)) {
                $pagename = $homeUrls[$code];
                $url = $locale->getUrl();
                add_rewrite_rule("$url/?$", "index.php?pagename=$pagename", "top");
                add_rewrite_rule("index.php/$url/?$", "index.php?pagename=$pagename", "top");
            }
        }
    }

}
