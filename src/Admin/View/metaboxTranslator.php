
<?php if (isset($invalidStatus) && $invalidStatus) : ?>
    <p><?php _e("Localization will be available once the object is saved.", "polyglot"); ?></p>
<?php else : ?>
    <ul>
        <li>
            <?php _e("Current Locale", "polyglot"); ?> :
            <?php $currentLocale = $i18n->getCurrentLocale(); ?>
            <?php if ($currentLocale->hasANativeLabel()) : ?>
                <code><?php echo $currentLocale->getCode(); ?></code>
                <?php _e($currentLocale->getNativeLabel()); ?>
            <?php else : ?>
                <?php $currentLocale->getCode(); ?>
            <?php endif; ?>
        </li>
        <li>
            <a href="#" class="button default-button" data-polyglot-ajax="click-popup" data-polyglot-ajax-action="switchTranslation" data-polyglot-ajax-param="<?php echo $obj_id; ?>#<?php echo $objKind; ?>#<?php echo $obj_type; ?>">
                <?php _e("Browse localized versions", "polyglot"); ?>
            </a>
            <?php if ($objKind === "Term" && !$currentLocale->isDefault()) : ?>


                <a href="<?php echo admin_url('options-general.php?page=polyglot-plugin&polyglot_action=deleteTermLocalization&termId=' . $obj_id . '&taxonomy='. $obj_type); ?>" class="button default-button deletion">
                    <?php _e("Delete this localized version", "polyglot"); ?>
                </a>
            <?php endif; ?>
        </li>
    </ul>
<?php endif; ?>
