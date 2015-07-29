<ul>
    <li>
        <?php _e("Current Locale", "polyglot"); ?> :<br>
        <?php $currentLocale = $polyglot->getCurrentLocale(); ?>
        <a href="#" class="button default-button">
            <?php if ($currentLocale->hasANativeLabel()) : ?>
                <code><?php echo $currentLocale->getCode(); ?></code>
                <?php _e($currentLocale->getNativeLabel()); ?>
            <?php else : ?>
                <?php $currentLocale->getCode(); ?>
            <?php endif; ?>
        </a>
    </li>
    <li>
        <?php _e("Other localized versions", "polyglot"); ?>: <br>

        <select name="translations">
            <option name=""><?php _e("Choose a locale to edit", "polyglot"); ?></option>
            <?php foreach ($polyglot->getLocales() as $locale) : ?>
                <?php if (!$polyglot->isCurrentlyActive($locale)) : ?>
                    <option name="<?php echo $locale->getCode(); ?>">
                        <?php echo $locale->getCode(); ?>
                        <?php if ($locale->hasANativeLabel()) : ?>
                             - <strong><?php _e($locale->getNativeLabel()); ?></strong>
                        <?php endif; ?>
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </li>
</ul>

<p><?php _e("* Marks missing translations.", "polyglot"); ?>
