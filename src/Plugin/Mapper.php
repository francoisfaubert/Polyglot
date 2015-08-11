<?php

namespace Polyglot\Plugin;

use Polyglot\Plugin\TranslationEntity;
use WP_Post;

/**
 * Mainly used by the admin area, this class helps map the full translation tree
 * of translatable objects.
 */
class Mapper  {

    private $polyglot;

    function __construct(Polyglot $polyglot)
    {
        $this->polyglot = $polyglot;
    }

    /**
     * From a post, builds up the whole translation hierarchy
     * so that every locale are populated with their translation information.
     * @param  WP_Post $post
     */
    public function assignMappingByPost(WP_Post $post)
    {
        $originalDetails = $this->getOriginalDetails($post);

        if (is_array($originalDetails) && count($originalDetails)) {
            $this->assignTranslationsMap($originalDetails);
            $originalPostId = $originalDetails[0]->translation_of;
        } else {
            $this->assignTranslationsRow($originalDetails);
            $originalPostId = $originalDetails->translation_of;
        }

        // When the process does not return an original id, then
        // we assume $post was the original version.
        if (is_null($originalPostId)) {
            return $this->buildAppendMissingOriginalPostDetails($post);
        }

        $originalPost = $this->polyglot->query()->findCachedPostById($originalPostId);
        $this->buildAppendMissingOriginalPostDetails($originalPost);
        return $originalPost;
    }


/**
 * From an array of taxonomies, map their translations
 * @param  array  $taxonomies [description]
 * @return [type]             [description]
 */
    public function assignMappingByTaxonomies(array $taxonomies)
    {
        //debug($taxonomy);
    }


    protected function buildAppendMissingOriginalPostDetails(WP_Post $post)
    {
        $this->assignOriginalToDefaultLocale($post);
        $translations = $this->polyglot->query()->findAllTranlationsOfOriginal($post);

        if (is_array($translations) && count($translations)) {
            $this->assignTranslationsMap($translations);
        }

        return $post;
    }

    protected function getOriginalDetails(WP_Post $post)
    {
        $query = $this->polyglot->query();
        $details = $query->findDetails($post);
        return $details->isOriginal() ? $details : $query->findOriginalTranslationDetails($post);
    }

    protected function assignTranslationsMap($rows)
    {
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $this->assignTranslationsRow($row);
            }
        } else {
            throw new Exception("Must map an array of TranslationEntities");
        }
    }

    protected function assignTranslationsRow(TranslationEntity $entity)
    {
        $locale = $this->polyglot->getLocaleByCode($entity->translation_locale);
        $locale->setDetails($entity);
    }

    /**
     * The default locale is always blank because it has no translations.
     * @param WP_Post
     * @return TranslationEntity Original translation
     */
    protected function assignOriginalToDefaultLocale(WP_Post $post)
    {
        $defaultLocale = $this->polyglot->getDefaultLocale();
        $translation = $this->polyglot->generateTranslationEntity($post);
        $defaultLocale->setDetails($translation);

        return $translation;
    }

}
