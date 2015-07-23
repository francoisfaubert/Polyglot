<?php
/*
Contributors: Francois Faubert
Donate link: https://polyglot.francoisfaubert.com/
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html
Tags: multiple languages, po, mo, gettext
Requires at least: 4.2
Tested up to: 4.2
Stable tag: 1
Text Domain: polyglot
Domain Path: /localization/
 */


if (class_exists("Polyglot\Plugin\Polyglot")) {
    $plugin = new Polyglot\Plugin\Polyglot();
    $plugin->register();
} else {
    echo "Though Polyglot is enabled, it could not be loaded.";
}
