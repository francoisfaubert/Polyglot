<?php

namespace Polyglot\Plugin\Adaptor;

class WidgetAdaptor {

    public static function addFilters()
    {
        add_action('widgets_init', array("\\Polyglot\\Widget\\LanguageMenu", "register"));
    }

}
