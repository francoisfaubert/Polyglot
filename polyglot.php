<?php
/*
Plugin Name: Polyglot
Plugin URI: http://polyglot.francoisfaubert.com/
Description: Wordpress localization
Version: 1.0
Author: Francois Faubert
Author URI: http://www.francoisfaubert.com
License: GPL3
 */

if (!class_exists("Strata\Strata")) {
    throw new Exception("Polyglot Localization plugin is expected to be ran in a Strata environment.");
}

if (class_exists("Polyglot\Plugin\Polyglot") && class_exists("Polyglot\Plugin\Adaptor\WordpressAdaptor")) {

    $polyglot = Polyglot\Plugin\Polyglot::instance();

    $plugin = new Polyglot\Plugin\Adaptor\WordpressAdaptor();
    $plugin->register(__FILE__);

    include("helpers.php");

} else {
    Strata\Strata::app()->log("Though Polyglot is enabled, it could not be loaded.", "[Plugins:Polyglot]");
}
