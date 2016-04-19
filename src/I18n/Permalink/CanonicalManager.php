<?php

namespace Polyglot\I18n\Permalink;

use Strata\Strata;
use Strata\I18n\I18n;
use Polyglot\I18n\Locale\Locale;

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
        $this->printMetaTags();
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
     * Home urls should not display the post_name slug on translated versions.
     */
    public function forwardCanonicalUrls()
    {
        $i18n = Strata::i18n();
        $homepageId = $i18n->query()->getDefaultHomepageId();
        $currentLocale = $i18n->getCurrentLocale();

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
        $currentPost = get_post();
        if ($currentPost) {
            foreach (Strata::i18n()->getLocales() as $locale) {
                $this->generateMetaTagsForPostByLocale($currentPost, $locale);
            }
        }
    }

    protected function generateMetaTagsForPostByLocale($post, Locale $locale)
    {
        if ($locale->hasPostTranslation($post->ID)) {
            $translatedPost = $locale->getTranslatedPost($post->ID);
            if ($translatedPost && $translatedPost->post_status === "publish") {

                $localizedUrl = get_permalink($translatedPost->ID);
                if (Strata::i18n()->shouldFallbackToDefaultLocale()) {
                    $localizedUrl = $this->permalinkManager->getLocalizedFallbackUrl($localizedUrl, $locale);
                }

                $this->alternates[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', $locale->getCode(),  $localizedUrl);
            }

        } else {
            $this->generateMetaTagsForFallbackPostByLocale($post, $locale);
        }
    }

    protected function generateMetaTagsForFallbackPostByLocale($post, Locale $locale)
    {
        // When we are fallbacking to default local on missing content but this
        // is not the default locale, we need canonicals too.
        $i18n = Strata::i18n();
        if ($i18n->shouldFallbackToDefaultLocale() && $post->post_status === "publish") {

            $currentLocale = $i18n->getCurrentLocale();
            $defaultLocale = $i18n->getDefaultLocale();
            $originalPost = $defaultLocale->getTranslatedPost($post->ID);
            $originalUrl = get_permalink($originalPost);
            $localizedFakeUrl = $this->permalinkManager->getLocalizedFallbackUrl($originalUrl, $locale);

            $alternates[] = sprintf('<link rel="alternate" hreflang="%s" href="%s">', $locale->getCode(), $localizedFakeUrl);

            // On a forced translation page, if the current locale is pretending to exist but
            // fallbacks to the global, say it's a canonical of that global translation.
            if (!$currentLocale->isDefault() && $currentLocale->getCode() === $locale->getCode()) {
                $localizedFakeUrl = $this->permalinkManager->getLocalizedFallbackUrl($originalUrl, $defaultLocale);
                $canonicals[] = sprintf('<link rel="canonical" href="%s">', $localizedFakeUrl);
            }
        }
    }
}
