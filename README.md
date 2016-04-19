# Polyglot

## Configuration

Declare your application locales under `~/config/strata.php`. You can specify the url key as well as the locale code. It is important that the locale code follows ISO standards. It must have either two characters (`en`) or be the complete version containing the country information (`en_US`).

~~~ php
<?php
$strata = array(
    "routes" => array(),
    "custom-post-types" => array(),

    "i18n" => array(
        "textdomain" => "my_website",
        "default_locale_fallback" => true,
        "locales" => array(
            "en_CA" => array("nativeLabel" => "English", "default" => true),
            "fr_CA" => array("nativeLabel" => "Français", "url" => "francais"),
            "pi" => array("nativeLabel" => "Pirate"),
         )
    )
);

return $strata;
?>
~~~
