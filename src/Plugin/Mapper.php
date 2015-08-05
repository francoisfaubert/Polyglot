<?php

namespace Polyglot\Plugin;

use Strata\Strata;
use Strata\Utility\Hash;

use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Db\Query;

use WP_Post;
use Exception;
use Gettext\Translations;
use Gettext\Translation;


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

    public function assignMappingByPost(WP_Post $post)
    {
        $translations = null;

        if ($this->polyglot->isTheOriginal($post)) {
            $this->assignPostMap($post);
            $translations = $this->polyglot->findAllTranslations($post);
        } else {
            $originalPost = $this->polyglot->findOriginalPost($post);
            $this->assignPostMap($originalPost);
            $translations = $this->polyglot->findAllTranslations($originalPost);
        }

        if (!is_null($translations)) {
            $this->assignTranslationsMap($translations);
        }
    }

    protected function assignTranslationsMap($rows)
    {
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $this->assignTranslationsRow($row);
            }
        }
    }

    protected function assignPostMap($post)
    {
        $this->assignTranslationsRow($this->polyglot->findTranslationDetails($post));
    }

    protected function assignTranslationsRow($row)
    {
        $locale = $this->polyglot->getLocaleByCode($row->translation_locale);
        $locale->setDbRow($row);
    }

}
