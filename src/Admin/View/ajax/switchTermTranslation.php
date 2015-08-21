<table class="widefat striped post">
<thead>
    <tr>
        <th colspan="2" scope="col" class="manage-column"><?php echo sprintf(__("Translations of '%s'", 'polyglot'), $originalTerm->name); ?></th>
    </tr>
</thead>
<tbody>
<?php $idx = 0; foreach ($polyglot->getLocales() as $code => $locale) : ?>

    <tr class="<?php echo ($idx % 2) === 0 ? "even" : "odd" ?>">
        <td>
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
        </td>
        <td>
            <?php if ($locale->hasTermTranslation($objId, $originalTerm->taxonomy)) : ?>
                <a class="button default-button" href="<?php echo $locale->getEditTermUrl($objId, $originalTerm->taxonomy); ?>">
                    <?php _e('Edit', 'polyglot'); ?>
                </a>
            <?php else : ?>
                <a class="button default-button" href="<?php echo $locale->getTranslateTermUrl($originalTerm); ?>">
                    <?php _e('Translate', 'polyglot'); ?>
                </a>
            <?php endif; ?>
        </td>
    </tr>
<?php $idx++; endforeach; ?>
</tbody>
</table>
