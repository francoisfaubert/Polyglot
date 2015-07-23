<?php

namespace Polyglot\Plugin;

use Polyglot\Plugin\Locale;
use Polyglot\Plugin\Adaptor\WordpressAdaptor;
use Exception;

class FileParser {

    // http://php.net/manual/en/function.scandir.php#113167
    public function rscandir($dir){
        $dirs = array_fill_keys( array_diff( scandir( $dir ), array( '.', '..' ) ), array());
        foreach( $dirs as $d => $v ) {
            if( is_dir($dir."/".$d) ) {
                $dirs[$d] = $this->rscandir($dir."/".$d);
            }
        }
        return $dirs;
    }

    public function createPoFile(Locale $locale, WordpressAdaptor $adaptor)
    {
        $templatePath = $adaptor->getAdminViewPath() . DIRECTORY_SEPARATOR . 'default.po';

        $defaultContents = @file_get_contents($templatePath);
        if ($defaultContents === false) {
            throw new Exception("Unable to read the file template.");
        }

        $destination = $locale->getPoPath();
        $this->createDirectory($destination);

        if (@file_put_contents($destination, $defaultContents) === false) {
             throw new Exception("Could not write the translation file. Make sure the correct file rights have been set.");
        }
    }

    protected function createDirectory($destination)
    {
        $destinationDir = dirname($destination);
        if (!is_dir($destinationDir)) {
            if (@mkdir($destinationDir) === false) {
                throw new Exception("Could not create the translation directory. Make sure the correct file rights have been set.");
            }
        }
    }


}

// #: src/preferences_dialog.c:54
// #, fuzzy
// msgid "Profile Generation"
// msgstr "Profile doesn't exist\n"
