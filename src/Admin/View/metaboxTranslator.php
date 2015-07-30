<ul>
    <li>
        <?php _e("Current Locale", "polyglot"); ?> :
        <?php $currentLocale = $polyglot->getCurrentLocale(); ?>
        <?php if ($currentLocale->hasANativeLabel()) : ?>
            <code><?php echo $currentLocale->getCode(); ?></code>
            <?php _e($currentLocale->getNativeLabel()); ?>
        <?php else : ?>
            <?php $currentLocale->getCode(); ?>
        <?php endif; ?>
    </li>
    <li>
        <a href="#" class="button default-button" data-polyglot-ajax="click-popup" data-polyglot-ajax-action="switchTranslation" data-polyglot-ajax-param="<?php echo get_the_id(); ?>">
            <?php _e("Browse localized versions", "polyglot"); ?>
        </a>
    </li>
</ul>
