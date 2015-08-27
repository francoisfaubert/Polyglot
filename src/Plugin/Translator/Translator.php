<?php
namespace Polyglot\Plugin\Translator;

use Exception;

class Translator {

    public static function factory($kind)
    {
        switch ($kind) {
            case 'WP_Post': return new PostTranslator();
            case 'Term' : return new TermTranslator();
        }

        throw new Exception("Polyglot does not know how to translate this.");
    }
}
