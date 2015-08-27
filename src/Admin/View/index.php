<h1><?php _e("Localization", "polyglot"); ?></h1>

<h2><?php _e("Loaded locales", "polyglot"); ?></h2>

<table class="widefat striped">
    <thead>
    <tr>
        <th scope="col" id="polyglot-code" class="manage-column column-title">
            <span><?php _e("Code", 'polyglot'); ?></span>
        </th>
        <th scope="col" id="polyglot-locale" class="manage-column column-title">
            <span><?php _e("Locale", 'polyglot'); ?></span>
        </th>
        <th scope="col" id="polyglot-action" class="manage-column column-title">
            <span></span>
        </th>
    </thead>

<?php $idx = 0; foreach ($polyglot->getLocales() as $code => $locale) : ?>

    <tr class="<?php echo ($idx % 2) === 0 ? "even" : "odd" ?>">
        <td>
            <?php if ($locale->isDefault()) : ?>
                <strong>
            <?php endif; ?>
                <code><?php echo $locale->getCode(); ?></code>
            <?php if ($locale->isDefault()) : ?>
                </strong>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($locale->isDefault()) : ?>
                <strong>
            <?php endif; ?>

            <?php if ($locale->hasANativeLabel()) : ?>
                 <?php echo $locale->getNativeLabel(); ?>
            <?php endif; ?>

            <?php if ($locale->isDefault()) : ?>
                </strong>
            <?php endif; ?>
        </td>
        <td>
            <a class="button default-button" href="<?php echo $locale->getEditUrl(); ?>"><?php _e("Edit dictionary", "polyglot"); ?></a>
        </td>
    </tr>
<?php $idx++; endforeach; ?>
</table>


<section class="dynamic-translations">
    <h2><?php _e("Dynamic translations", "polyglot"); ?></h2>

    <h3><?php _e("Post types"); ?> :</h3>
    <div class="post-type-management" data-polyglot-ajax="autoload" data-polyglot-ajax-action="viewPostTypeList"></div>

    <h3><?php _e("Taxonomies"); ?> :</h3>
    <div class="taxonomy-management" data-polyglot-ajax="autoload" data-polyglot-ajax-action="viewTaxonomyList"></div>
</section>
