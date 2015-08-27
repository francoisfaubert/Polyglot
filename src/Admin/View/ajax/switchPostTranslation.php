<h3><?php echo sprintf(__("Translations of '%s'", 'polyglot'), $originalPost->post_title); ?></h3>

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
            <?php if ($locale->hasPostTranslation($objId)) : ?>
                <a class="button default-button" href="<?php echo $locale->getEditPostUrl($objId); ?>">
                    <?php _e('Edit', 'polyglot'); ?>
                </a>
            <?php else : ?>
                <a class="button default-button" href="<?php echo $locale->getTranslatePostUrl($originalPost); ?>">
                    <?php _e('Translate', 'polyglot'); ?>
                </a>
            <?php endif; ?>
        </td>
    </tr>
<?php $idx++; endforeach; ?>
</table>
