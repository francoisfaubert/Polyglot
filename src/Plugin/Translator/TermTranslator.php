<?php
namespace Polyglot\Plugin\Translator;

use Polyglot\Plugin\Polyglot;
use Exception;

class TermTranslator extends TranslatorBase {

    protected $originalKind = "Term";

    public function getForwardUrl()
    {
        $locale = $this->getTranslationLocale();
        return $locale->getEditTermUrl($this->translationObjId, $this->originalType);
    }

    public function carryOverOriginalData()
    {

    }

    public function copyObject()
    {
        $term = get_term_by("id", $this->originalId, $this->originalType);
        $translationTitle = $term->name . " (".$this->translatedTo.")";
        $result = wp_insert_term( $translationTitle, $this->originalType);

        if (is_a($result, 'WP_Error')) {
            $error = array_values($result->errors);
            throw new Exception($error[0][0]);
        }

        $this->translationObjId = $result['term_id'];
        return $this->translationObjId;
    }
}
