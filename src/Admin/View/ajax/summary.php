<section class="summary">
    <h3><?php _e("Localization", $polyglot->getTextDomain()); ?></h3>

    <?php  /*if (!$polyglot->hasFoundTranslationsDirectory()) : ?>
        <div class="alert alert-info">
            <p><?php _e(sprintf("Polyglot did not find the translation directory located at '%s'.", WP_LANG_DIR), $polyglot->getTextDomain()); ?></p>
            <p><?php _e(sprintf("We will created it when you generate your first localized translation file if we can write to the directory.", WP_LANG_DIR), $polyglot->getTextDomain()); ?></p>
        </div>

    <?php elseif (!$polyglot->hasFoundTranslations()) : ?>
        <div class="alert alert-info">
            <p><?php _e(sprintf("Polyglot did not find any translation files under '%s'.", WP_LANG_DIR), $polyglot->getTextDomain()); ?></p>
            <p><?php _e(sprintf("We will created them when you generate your first localized translation file if we can write to the directory.", WP_LANG_DIR), $polyglot->getTextDomain()); ?></p>
        </div>
    <?php endif; */ ?>

    <?php $datasource = $polyglot->getConfiguration(); ?>
    <div class="group">
        <div class="col3">
            <h3><?php _e("Enabled Locales", $polyglot->getTextDomain()); ?></h3>
            <div class="overflow">
                <ul>
                <?php $enabled = $datasource->getEnabledLocales(); ?>
                <?php if (count($enabled) > 0) : ?>
                    <?php foreach ($enabled as $locale)  : ?>
                        <li>
                            <em>(<?php echo $locale->getCode(); ?>)</em>
                            <?php _e($locale->getDefaultName(), $polyglot->getTextDomain()); ?><br>
                            .po : <?php if ($locale->hasPo()) : ?>Yes<?php else : ?>No<?php endif; ?>
                            .mo : <?php if ($locale->hasMo()) : ?>Yes<?php else : ?>No<?php endif; ?>
                        </li>
                   <?php endforeach; ?>
                <?php else : ?>
                    <li><?php _e("There are currently no enabled locales.", $polyglot->getTextDomain()); ?></li>
                <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="col3">
            <h3><?php _e("Disabled Locales", $polyglot->getTextDomain()); ?></h3>
            <div class="overflow">
                <ul>
                    <?php $disabled = $datasource->getDisabledLocales(); ?>
                    <?php if (count($disabled) > 0) : ?>
                        <?php foreach ($disabled as $locale)  : ?>
                            <li>
                            <em>(<?php echo $locale->getCode(); ?>)</em>
                            <?php _e($locale->getDefaultName(), $polyglot->getTextDomain()); ?><br>
                            .po : <?php if ($locale->hasPo()) : ?>Yes<?php else : ?>No<?php endif; ?>
                            .mo : <?php if ($locale->hasMo()) : ?>Yes<?php else : ?>No<?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li><?php _e("There are currently no disabled locales.", $polyglot->getTextDomain()); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="col3">
            <h3><?php _e("Unassociated .mo", $polyglot->getTextDomain()); ?></h3>
            <div class="overflow">
                <ul>
                    <?php $disabled = $datasource->getDisabledLocales(); ?>
                    <?php if (count($disabled) > 0) : ?>
                        <?php foreach ($disabled as $localeKey => $localeName)  : ?>
                            <li><em>(<?php echo $localeKey; ?>)</em> <?php _e($localeName, $polyglot->getTextDomain()); ?></li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li><?php _e("There are currently no disabled locales.", $polyglot->getTextDomain()); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</section>
