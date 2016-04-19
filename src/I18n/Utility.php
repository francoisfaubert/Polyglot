<?php

namespace Polyglot\I18n;

use Strata\Strata;

class Utility {

    /**
     * Replaces the first occurrence of $from by $to in $subject
     * @param  string $from
     * @param  string $to
     * @param  string $subject
     * @return string
     */
    public static function replaceFirstOccurence($from, $to, $subject)
    {
        $from = '/' . preg_quote($from, '/') . '/';
        return preg_replace($from, $to, $subject, 1);
    }

    public static function getLocaleUrlsRegex()
    {
        $urls = self::getLocaleUrls();
        $escaped = array();

        foreach($urls as $url) {
            $escaped[] = preg_quote($url);
        }

        return implode("|", $escaped);
    }

    public static function getLocaleUrls()
    {
        return array_map(function($locale) { return $locale->getUrl(); }, Strata::i18n()->getLocales());
    }
}
