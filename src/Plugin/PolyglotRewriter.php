<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\I18n\I18n;

use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Db\Query;

use WP_Post;
use Exception;

class PolyglotRewriter {


    private $i18n = null;
    private $polyglot = null;

    function __construct()
    {
        $app = Strata::app();
        $this->i18n = $app->i18n;
        $this->polyglot = new Polyglot();
    }

    public function registerHooks()
    {
        add_filter('post_link', array($this, 'postLink'), 1, 3);
        add_filter('page_link', array($this, 'postLink'), 1, 3);
        add_filter('home_url', array($this, 'homeUrl'), 1, 4 );
        add_filter('query_vars', array($this, 'addQueryVars'));

        add_action('init', array($this, 'addLocaleRewrites'));
        add_action('wp_trash_post', array($this, 'onTrash'));
    }

    function addQueryVars($qv)
    {    $qv[] = 'locale';
        return $qv;
    }

    function addLocaleRewrites()
    {
        $this->openRewriteForTranslations();

        $regex = implode("|", $this->getLocaleUrls());

        // Pages
        add_rewrite_rule('index.php/('.$regex.')/(.?.+?)/?$', 'index.php?pagename=$matches[2]&locale=$matches[1]', "top");

        // Posts
        add_rewrite_rule('index.php/('.$regex.')/([^/]+)/?$', 'index.php?name=$matches[2]&locale=$matches[1]', "top");

        $homeUrls = $this->generateLocaleHomeUrlList();
        foreach ($this->i18n->getLocales() as $locale) {
            // Rewrite for localized homepages
            if (array_key_exists($locale->getCode(), $homeUrls)) {
                add_rewrite_rule('index.php/(' . $locale->getUrl() . ')/?$', 'index.php?name='.$homeUrls[$locale->getCode()].'&locale=$matches[1]', "top");
            }
        }

        // @todo : trigger intelligently
        flush_rewrite_rules();
    }

    public function onTrash($postId) {
        $query = new Query();
        $query->unlinkTranslationFor($postId, "WP_Post");
    }

    private function generateLocaleHomeUrlList()
    {
        $slugs = array();

        // We only care if a page is on the front page
        if (get_option('show_on_front') == "page") {
            $defaultHomeId = (int)get_option('page_on_front');
            if ($defaultHomeId > 0) {
                $translatedPages = $this->polyglot->findAllTranslations($this->polyglot->getCachedPostById($defaultHomeId));
                foreach ($translatedPages as $page) {
                    $slugs[$page->translation_locale] = $page->post_name;
                }
            }
        }

        return $slugs;
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
            $locale = $this->i18n->getLocaleByCode($details->translation_locale);

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

    public function homeUrl($url)
    {
        $locale = $this->i18n->getCurrentLocale();
        if (!$locale->isDefault()) {
            $home = str_replace("//", "\/\/", preg_quote(WP_HOME));
            $regex = "$home\/(index.php\/)?(.*)?";

            if (!preg_match("/(index.php)?\/".$locale->getUrl()."\//", $url)) {
                return preg_replace("/$regex/", WP_HOME . "/$1" . $locale->getUrl() . "/$2", $url);
            }
        }
        return $url;
    }

    private function getLocaleUrls()
    {
        $urls = array();
        foreach ($this->i18n->getLocales() as $locale) {
            $urls[] = $locale->getUrl();
        }
        return $urls;
    }

    private function isATranslatedPost(WP_Post $post)
    {
        return  !is_null($post)
                && get_class($post) == "WP_Post"
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

}
