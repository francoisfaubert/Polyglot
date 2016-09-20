<?php

namespace Polyglot\I18n\Permalink;

use Strata\Strata;
use Strata\I18n\I18n;
use Polyglot\I18n\Locale\Locale;
use Polyglot\I18n\Utility;
use Exception;

class CanonicalManager {

    private $alternates = array();
    private $canonicals = array();
    private $permalinkManager;

    public function __construct(PermalinkManager $permalinkManager)
    {
        $this->permalinkManager = $permalinkManager;
    }

    public function filter_onWpHead()
    {
        if (!is_404() && !is_search()) {
            $this->printMetaTags();
        }
    }

    public function filter_onWidgetInit()
    {
        $this->forwardCanonicalUrls();
    }

    public function filter_onRedirectCanonical($redirectUrl, $requestedUrl = null)
    {
        return $this->redirectCanonical($redirectUrl, $requestedUrl);
    }

    public function redirectCanonical($redirectUrl, $requestedUrl = null)
    {
        foreach (Strata::i18n()->getLocales() as $locale) {
            // If WP wants to redirect to the root locale page, prevent the redirect
            if ($locale->getHomeUrl() === $requestedUrl) {
                return $requestedUrl;
            }
        }

        return $redirectUrl;
    }

    /**
     *
     */
    public function forwardCanonicalUrls()
    {
        $i18n = Strata::i18n();
        $homepageId = $i18n->query()->getDefaultHomepageId();
        $currentLocale = $i18n->getCurrentLocale();

        // Home urls should not display the post_name slug on translated versions.
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
     * Appends meta tags with additional localization information and links to localized versions.
     * @return html (it actually echoes it)
     * @see wp_head
     * @filters strata_polyglot_canonicals_meta_before_print, strata_polyglot_alternates_meta_before_print.
     */
    public function printMetaTags()
    {
        $this->generateMetaTags();

        $alternates = apply_filters("strata_polyglot_alternates_meta_before_print", $this->alternates);
        $canonicals = apply_filters("strata_polyglot_canonicals_meta_before_print", $this->canonicals);

        echo
            implode("\n", $alternates) . "\n" .
            implode("\n", $canonicals) . "\n";
    }


    protected function generateMetaTags()
    {
        if (!is_archive() && !is_search()) {
            $currentPost = get_post();
            if ($currentPost) {
                return $this->generatePostTags($currentPost);
            }
        }

        global $wp_query;
        $taxonomy = $wp_query->queried_object;
        if (is_a($taxonomy, "WP_Term")) {
            return $this->generateTaxonomyTags($taxonomy);
        }
    }

    private function generatePostTags($currentPost)
    {
        $shouldFallback = (bool)Strata::config("i18n.default_locale_fallback");
        $defaultLocale = Strata::i18n()->getDefaultLocale();
        $currentLocale = Strata::i18n()->getCurrentLocale();
        $permalinkManager = new PostPermalinkManager();
        $currentPermalink = get_permalink($currentPost->ID);

        // Keep the default url handy
        $defaultFallbackUrl = "";
        if ($shouldFallback) {
            $permalinkManager->enforceLocale($defaultLocale);
            $defaultFallbackUrl = $permalinkManager->generatePermalink($currentPermalink, $currentPost->ID);
        }

        foreach (Strata::i18n()->getLocales() as $locale) {
            $permalinkManager->enforceLocale($locale);

            try {
                $localizedUrl = $permalinkManager->generatePermalink($currentPermalink, $currentPost->ID);

                $destinationIsTheSame = $localizedUrl === $currentPermalink;
                $isNotDefaultButIsNotTheCurrent = !$locale->isDefault() && $locale->getCode() !== $currentLocale->getCode();

                if ($isNotDefaultButIsNotTheCurrent && $destinationIsTheSame && $shouldFallback) {
                    $this->alternates[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', $locale->getCode(), $defaultFallbackUrl);
                    $this->canonicals[] = sprintf('<link rel="canonical" href="%s">', $defaultFallbackUrl);
                } else {
                    $this->alternates[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', $locale->getCode(), $localizedUrl);
                }
            } catch (Exception $e) {

            }
        }
    }


    private function generateTaxonomyTags($taxonomy)
    {
        $shouldFallback = (bool)Strata::config("i18n.default_locale_fallback");
        $defaultLocale = Strata::i18n()->getDefaultLocale();
        $currentLocale = Strata::i18n()->getCurrentLocale();
        $permalinkManager = new TermPermalinkManager();
        $currentPermalink = get_term_link($taxonomy, $taxonomy->taxonomy);

        // Keep the default url handy
        $defaultFallbackUrl = "";
        if ($shouldFallback) {
            $permalinkManager->enforceLocale($defaultLocale);
            $defaultFallbackUrl = $permalinkManager->generatePermalink($currentPermalink, $taxonomy, $taxonomy->taxonomy);
        }

        foreach (Strata::i18n()->getLocales() as $locale) {
            $permalinkManager->enforceLocale($locale);

            try {
                $localizedUrl = $permalinkManager->generatePermalink($currentPermalink, $taxonomy, $taxonomy->taxonomy);

                $destinationIsTheSame = $localizedUrl === $currentPermalink;
                $isNotDefaultButIsNotTheCurrent = !$locale->isDefault() && $locale->getCode() !== $currentLocale->getCode();

                if ($isNotDefaultButIsNotTheCurrent && $destinationIsTheSame && $shouldFallback) {
                    $this->alternates[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', $locale->getCode(), $defaultFallbackUrl);
                    $this->canonicals[] = sprintf('<link rel="canonical" href="%s">', $defaultFallbackUrl);
                } else {
                    $this->alternates[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', $locale->getCode(), $localizedUrl);
                }
            } catch (Exception $e) {

            }
        }
    }
}
