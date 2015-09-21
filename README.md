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

## Code Examples:

This early in the creation of the plugin, in lieu of actual documentation, here are common code examples.

### Link to translations of current post

~~~ php
<?php global $polyglot; ?>

<ul>
<?php foreach ($polyglot->getLocales() as $locale) : ?>
    <?php if ($locale->hasTranslation()) :
        $localizedPost = $locale->getTranslatedPost(); ?>
        <li><a href="<?php echo get_permalink($localizedPost->ID); ?>" hreflang="<?php echo $locale->getCode(); ?>"><?php echo $localizedPost->post_title; ?></a></li>
    <?php endif; ?>
<?php endforeach; ?>
</ul>
~~~

### Link to translations at their homepage

~~~ php
<?php global $polyglot; ?>

<ul>
<?php foreach ($polyglot->getLocales() as $locale) : ?>
    <li><a href="<?php echo $locale->getHomeUrl(); ?>" hreflang="<?php echo $locale->getCode(); ?>"><?php echo $locale->getNativeLabel(); ?></a></li>
<?php endforeach;   ?>
</ul>
~~~
