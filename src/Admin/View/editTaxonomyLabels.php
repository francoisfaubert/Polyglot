<a href="<?php echo admin_url('options-general.php?page=polyglot-plugin'); ?>"><?php _e("Back to Locale list"); ?></a>

<?php echo $formHelper->create(); ?>

    <?php foreach ($polyglot->getLocales() as $locale) : ?>

        <?php
            $count = 0;
            $code = $locale->getCode();
            $translations = $polyglot->getTranslations($code);
        ?>

        <h3>
            <?php echo $code; ?>
            <?php if ($locale->hasANativeLabel()) : ?>
                 - <strong><?php echo $locale->getNativeLabel(); ?></strong>
            <?php endif; ?>
        </h3>

        <?php foreach ($taxonomies->labels as $key => $value) : ?>

            <?php if ($count % 2 === 0) : ?>
                <div class="group">
            <?php endif; ?>

                    <?php
                        // Either use the existing translation data or build up fresh one
                        $translation = $translations->find($value, $value);
                        if ($translation === false) {
                            $translation = new Gettext\Translation($value, $value);
                            $translation->setTranslation("");
                        }
                    ?>

                    <div class="col">
                        <blockquote>
                            "<?php echo htmlentities($translation->getOriginal()); ?>"
                        </blockquote>

                        <?php echo $formHelper->input("translations[$code][$count][id]", array("type" => "hidden", "value" => htmlentities($translation->getId()))); ?>
                        <?php echo $formHelper->input("translations[$code][$count][original]", array("type" => "hidden", "value" => htmlentities($translation->getOriginal()))); ?>
                        <?php echo $formHelper->input("translations[$code][$count][context]", array("type" => "hidden", "value" => htmlentities($translation->getContext()))); ?>
                        <?php echo $formHelper->input("translations[$code][$count][plural]", array("type" => "hidden", "value" => "")); ?>
                        <?php echo $formHelper->input("translations[$code][$count][pluralTranslation]", array("type" => "hidden", "value" => "")); ?>

                        <div>
                            <label>
                                <?php echo __("Translation", "polyglot"); ?>
                                <?php echo $formHelper->input("translations[$code][$count][translation]", array("value" => $translation->getTranslation())); ?>
                            </label>
                        </div>

                        <div>
                            <label>
                                <?php echo __("Comments", "polyglot"); ?>
                                <?php echo $formHelper->input("translations[$code][$count][comments]", array("type" => "textarea", "value" => $translation->getTranslation())); ?>
                            </label>
                        </div>
                    </div>

                <?php if (($count+1)%2===0 || ($count+1) >= count($translations)) : ?>
                    </div>
                <?php endif; ?>

                <?php $count++; ?>

        <?php endforeach; ?>

    <?php endforeach; ?>

<?php echo $formHelper->submit(array("label" => __("Save", "polyglot"))); ?>
<?php echo $formHelper->end(); ?>
