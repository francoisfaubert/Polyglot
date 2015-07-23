<form action="#" method="post" data-ajax="submit" data-ajax-action="saveLocale">
    <input type="hidden" name="submit" value="1">
    <input type="hidden" name="locale[uniqueId]" value="<?php echo $locale->getId(); ?>">

    <?php if (isset($exception)) :?>
        <div class="error"><p><?php _e($exception, $polyglot->getTextDomain()); ?></p></div>
    <?php endif; ?>
    <?php if (isset($success) && (bool)$success) : ?>
        <div class="updated"><p><?php _e("Locale saved successfully.", $polyglot->getTextDomain()); ?></p></div>
    <?php endif; ?>

    <h2>
        <?php if ($locale->isNew()) : ?>
            <?php _e("New locale", $polyglot->getTextDomain()); ?>
        <?php else : ?>
            <?php _e("Editing", $polyglot->getTextDomain()); ?> : "<?php echo $locale->getDefaultName(); ?>"
        <?php endif; ?>
    </h2>

    <h3><?php _e("Details", $polyglot->getTextDomain()); ?></h3>

    <table class="widefat" cellspacing="0">
        <tbody>
            <tr>
                <th class="column-columnname"><?php _e("Default name", $polyglot->getTextDomain()); ?></th>
                <td><input type="text" name="locale[defaultName]" value="<?php echo $locale->getDefaultName(); ?>"></td>
            </tr>
            <tr>
                <th class="column-columnname"><?php _e("ISO code (ex: en-US)", $polyglot->getTextDomain()); ?></th>
                <td><input type="text" name="locale[code]" value="<?php echo $locale->getCode(); ?>"></td>
            </tr>
            <tr>
                <th class="column-columnname"><?php _e("Is default front language", $polyglot->getTextDomain()); ?></th>
                <td><input type="checkbox" name="locale[isDefault]" value="1" <?php if($locale->isDefault()): ?>checked="checked"<?php endif; ?> ></td>
            </tr>
        </tbody>
    </table>
</form>
