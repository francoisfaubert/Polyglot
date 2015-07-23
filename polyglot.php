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
if (class_exists("Polyglot\Plugin\Polyglot") && class_exists("Polyglot\Plugin\Adaptor\WordpressAdaptor")) {
    $plugin = new Polyglot\Plugin\Adaptor\WordpressAdaptor();
    $plugin->register(__FILE__);
} else {
    echo "Though Polyglot is enabled, it could not be loaded.";
}
