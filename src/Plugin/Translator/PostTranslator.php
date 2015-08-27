<?php
namespace Polyglot\Plugin\Translator;

use Polyglot\Plugin\Polyglot;
use Exception;

class PostTranslator extends TranslatorBase {

    protected $originalKind = "WP_Post";

    public function getTranslatedObject()
    {
        if ((int)$this->translationObjId > 0) {
            return Polyglot::instance()->query()->findPostById($this->translationObjId);
        }

        throw new Exception("Translation is not associated to an object.");
    }

    public function getForwardUrl()
    {
        $locale = $this->getTranslationLocale();
        return $locale->getEditPostUrl($this->translationObjId);
    }

    public function carryOverOriginalData()
    {
        $metaValues = (array)get_post_custom($this->originalId);
        foreach($metaValues as $name => $values) {
            foreach ($values as $idx => $value) {
                // Theorically, this should have allowed us to go up to the original
                // translation's meta, but it won't work because of how the filter works.
                // add_post_meta($this->translationObjId, $name, "__polyglot_meta_inherit_" . $this->originalId . "_$idx");
                add_post_meta($this->translationObjId, $name, $value);
            }
        }
    }
}
