<div class="polyglot-page">
    <a class="back" href="<?php echo admin_url('options-general.php?page=polyglot-plugin'); ?>"><?php _e("Back to Locale list"); ?></a>

    <header>
        <div class="title">
            <h1><?php _e("Localization", "polyglot"); ?></h1>
            <h2>
                <code><?php echo $locale->getCode(); ?></code>
                <?php if ($locale->hasANativeLabel()) : ?>
                     - <strong><?php echo $locale->getNativeLabel(); ?></strong>
                <?php endif; ?>
            </h2>
        </div>

        <div class="scanner">
            <a class="button" href="<?php echo admin_url('options-general.php?page=polyglot-plugin&polyglot_action=scanProject&backToLocale='.$locale->getCode()); ?>"><?php _e("Scan project"); ?></a>
            <?php if (!isset($modifiedDate) || is_null($modifiedDate)) : ?>
                <p><?php _e("The project has never been scanned for translatable string.", "polyglot"); ?></p>
            <?php else : ?>
                <p><?php echo sprintf(__("The last scan was performed on %s", "polyglot"), $modifiedDate); ?></p>
            <?php endif; ?>
        </div>
    </header>


    <?php if (isset($translations)) : ?>
        <h4><?php _e("Edit strings", "polyglot"); ?></h4>

        <?php echo $FormHelper->create(); ?>
        <?php $count = 0; ?>
        <?php foreach ($translations as $id => $translation) : ?>

            <?php if ($count % 2 === 0) : ?>
                <div class="group">
            <?php endif; ?>

                <div class="col">
                    <blockquote>
                        "<?php echo htmlentities($translation->getOriginal()); ?>"
                    </blockquote>

                    <?php $references = $translation->getReferences(); ?>
                    <?php if (count($references)) : ?>
                        <div class="references"><?php echo basename($references[0][0]); ?>#<?php echo $references[0][1]; ?></div>
                    <?php endif; ?>

                    <?php echo $FormHelper->input("translations[$count][id]", array("type" => "hidden", "value" => htmlentities($translation->getId()))); ?>
                    <?php echo $FormHelper->input("translations[$count][original]", array("type" => "hidden", "value" => htmlentities($translation->getOriginal()))); ?>
                    <?php echo $FormHelper->input("translations[$count][context]", array("type" => "hidden", "value" => htmlentities($translation->getContext()))); ?>
                    <?php echo $FormHelper->input("translations[$count][plural]", array("type" => "hidden", "value" => "")); ?>
                    <?php echo $FormHelper->input("translations[$count][pluralTranslation]", array("type" => "hidden", "value" => "")); ?>

                    <div>
                        <label>
                            <?php echo __("Translation", "polyglot"); ?>
                            <?php echo $FormHelper->input("translations[$count][translation]", array("value" => $translation->getTranslation())); ?>
                        </label>
                    </div>

                    <div>
                        <label>
                            <?php echo __("Comments", "polyglot"); ?>
                            <?php echo $FormHelper->input("translations[$count][comments]", array("type" => "textarea", "value" => $translation->getTranslation())); ?>
                        </label>
                    </div>
                </div>

            <?php if (($count+1)%2===0 || ($count+1) >= count($translations)) : ?>
                </div>
            <?php endif; ?>

            <?php $count++; ?>
        <?php endforeach; ?>


        <?php echo $FormHelper->submit(array("label" => __("Save", "polyglot"))); ?>
        <?php echo $FormHelper->end(); ?>
    <?php endif; ?>
</div>
