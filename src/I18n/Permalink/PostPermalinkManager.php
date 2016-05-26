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
use Exception;

use Polyglot\I18n\Locale\ContextualManager;
use Strata\Router\Router;

class PostPermalinkManager extends PermalinkManager {

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

    /**
     * Ensures post and page links are wrapped in the current active
     * country.
     * @param  string $permalink
     * @param  int $postId
     * @return string
     */
    public function filter_onPostLink($permalink, $postId)
    {
        $this->enforceLocale();
        return $this->generatePermalink($permalink, $postId);
    }

    public function generatePermalink($permalink, $postId)
    {
        $postId = is_object($postId) ? $mixed->ID : $postId;

        if (wp_is_post_revision($postId)) {
            return $this->addLocaleHomeUrl($permalink);
        }

        $postAttempingToTranslate = get_post($postId);
        if (!$postAttempingToTranslate) {
            return $this->addLocaleHomeUrl($permalink);
        }

        // Translate the current post_name.
        $tree = Tree::grow($postAttempingToTranslate->ID, "WP_Post");
        if ($tree->isLocalized()) {
            $permalink = $this->translatePostName($postAttempingToTranslate, $permalink);
        }

        if ($this->currentLocale->hasPostTranslation($postAttempingToTranslate->ID)) {
            // Translate up the tree should the post have parents.
            $generationPointer = $postAttempingToTranslate;
            while ($generationPointer && (int)$generationPointer->post_parent > 0) {
                $parent = get_post($generationPointer->post_parent);
                $parentTree = Tree::grow($parent->ID, "WP_Post");
                if ($parentTree->isLocalized($parent->ID)) {
                    $permalink = $this->translatePostName($parent, $permalink);
                    $generationPointer = $parent;
                }
            }

            // Home urls should not display the post_name slug on translated versions.
            if (!$this->currentLocale->isDefault()) {
                $homepageId = Strata::i18n()->query()->getDefaultHomepageId();
                $tree = Tree::grow($homepageId, "WP_Post");
                $localizedHomePage = $tree->getLocalizationIn($this->currentLocale);
                if ($localizedHomePage && (int)$postAttempingToTranslate->ID === (int)$localizedHomePage->getObjectId()) {
                    $permalink = Utility::replaceFirstOccurence(
                        $localizedHomePage->getWordpressObject()->post_name . "/",
                        "",
                        $permalink
                    );
                }
            }
        }

        // Translate the default Wordpress custom post type slug
        $model = $this->getStrataModel($postAttempingToTranslate);
        if (!is_null($model) && $this->shouldRewriteModelSlug($model)) {
            $permalink = $this->localizeDefaultSlug($model, $permalink);
        }

        return $this->addLocaleHomeUrl($permalink);
    }

    public function enforceLocale($locale = null)
    {
        // The current locale gets lost in metabox queries.
        // in the admin
        if (is_null($locale) && is_admin() && !Router::isAjax()) {
            $context = new ContextualManager();
            $locale = $context->getByAdminContext();
        }

        if (!is_null($locale)) {
            $this->currentLocale = $locale;
        }
    }

    private function translatePostName($post, $permalink)
    {
        if ($this->currentLocale->hasPostTranslation($post->ID)) {
            $translation = $this->currentLocale->getTranslatedPost($post->ID);
            return Utility::replaceFirstOccurence(
                $post->post_name,
                $translation->post_name,
                $permalink
            );
        }

        return $permalink;
    }

    // Before leaving, check if we are expected to build localized urls when
    // the page does not exist. This ensures the default content is displayed as if it
    // was a localization of the current locale. (ex: en_US could be the invisible fallback for en_CA).
    protected function addLocaleHomeUrl($permalink)
    {
        if (preg_match('/' . Utility::getLocaleUrlsRegex() . '/', $permalink)) {
            $permalink = preg_replace(
                '#(/(' . Utility::getLocaleUrlsRegex() . ')/)#',
                '/',
                $permalink
            );
        }

        if ($this->currentLocale->hasACustomUrl()) {
            return Utility::replaceFirstOccurence(
                get_home_url() . '/',
                $this->currentLocale->getHomeUrl(),
                $permalink
            );
        }

        return $permalink;
    }

    private function localizeDefaultSlug($model, $permalink)
    {
        return Utility::replaceFirstOccurence(
            $model->getConfig("rewrite.slug"),
            $model->getConfig("i18n." . $this->currentLocale->getCode() . ".rewrite.slug"),
            $permalink
        );
    }

    private function getStrataModel($post)
    {
        if (preg_match('/cpt_/', $post->post_type)) {
            try {
                $modelEntity = ModelEntity::factoryFromString($post->post_type);
                return $modelEntity->getModel();
            } catch(Exception $e) {
                // we dont care not a Strata model
            }
        }
    }

    private function shouldRewriteModelSlug($model)
    {
        if (!$this->currentLocale->isDefault() || Strata::i18n()->shouldFallbackToDefaultLocale()) {
            return $model->hasConfig("i18n." . $this->currentLocale->getCode() . ".rewrite.slug");
        }

        return false;
    }
}
