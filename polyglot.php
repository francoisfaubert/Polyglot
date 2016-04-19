<?php
/*
Plugin Name: Polyglot
Plugin URI: http://polyglot.francoisfaubert.com/
Description: Wordpress localization
Version: 1.1
Author: Francois Faubert
Author URI: http://www.francoisfaubert.com
License: GPL3
 */

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists("Strata\Strata")) {
    throw new Exception("Polyglot Localization plugin is expected to be ran within a Strata environment.");
}

if (class_exists("Polyglot\Plugin\Adaptor\PluginAdaptor")) {
    $plugin = new Polyglot\Plugin\Adaptor\PluginAdaptor(__FILE__);
    $plugin->addFilters();
} else {
    Strata\Strata::app()->log("Though Polyglot is enabled, it could not be loaded.", "[Plugins:Polyglot]");
}
