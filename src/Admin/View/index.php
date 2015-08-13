<h1><?php _e("Localization", "polyglot"); ?></h1>

<h2><?php _e("Active locales", "polyglot"); ?></h2>

<?php if ($polyglot->hasActiveLocales()) : ?>
    <div class="active-locales">
    <?php foreach ($polyglot->getLocales() as $locale) : ?>
        <div class="box">

            <?php if ($locale->isDefault()) : ?>
                 <strong>
            <?php endif; ?>

            <code><?php echo $locale->getCode(); ?></code>

            <?php if ($locale->hasANativeLabel()) : ?>
                 <?php echo $locale->getNativeLabel(); ?>
            <?php endif; ?>

            <?php if ($locale->isDefault()) : ?>
                 </strong>
            <?php endif; ?>

            <br>

            <a class="button default-button" href="<?php echo $locale->getEditUrl(); ?>"><?php _e("Translate", "polyglot"); ?></a>
        </div>
    <?php endforeach; ?>
    </div>
<?php else : ?>
    <p><?php _e("No active locales have been found in your Strata configuration file."); ?></p>
<?php endif; ?>

<section class="dynamic-translations">
    <h2><?php _e("Dynamic translations", "polyglot"); ?></h2>

    <h3><?php _e("Post types"); ?> :</h3>
    <div class="post-type-management" data-polyglot-ajax="autoload" data-polyglot-ajax-action="viewPostTypeList"></div>

    <h3><?php _e("Taxonomies"); ?> :</h3>
    <div class="taxonomy-management" data-polyglot-ajax="autoload" data-polyglot-ajax-action="viewTaxonomyList"></div>
</section>
