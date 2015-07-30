<h3><?php echo sprintf(__("Translations of %s", 'polyglot'), $originalPost->post_title); ?></h3>

<table class="widefat">
<?php foreach ($polyglot->getLocales() as $idx => $locale) : ?>
    <tr class="<?php echo ($idx % 2) === 0 ? "even" : "odd" ?>">
        <td>
            <code><?php echo $locale->getCode(); ?></code>
            <?php if ($locale->hasANativeLabel()) : ?>
                 <?php echo $locale->getNativeLabel(); ?>
            <?php endif; ?>
        </td>
        <td>
            <?php if ($locale->wasLocalized() || $locale->isDefault()) : ?>

                <a class="button default-button" href="<?php echo $locale->getObjectEditUrl(); ?>">
                    <?php _e('Edit', 'polyglot'); ?>
                </a>

            <?php else : ?>
                <a class="button default-button" href="<?php echo $locale->getObjectTranslateUrl($originalPost); ?>">
                    <?php _e('Translate', 'polyglot'); ?>
                </a>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; ?>
</table>
