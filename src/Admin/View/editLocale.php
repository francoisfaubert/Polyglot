<a href="<?php echo admin_url('options-general.php?page=polyglot-plugin'); ?>"><?php _e("Back to Locale list"); ?></a>

<h3>
    <?php echo $locale->getCode(); ?>
    <?php if ($locale->hasANativeLabel()) : ?>
         - <strong><?php echo $locale->getNativeLabel(); ?></strong>
    <?php endif; ?>
</h3>

<h4><?php _e("Edit strings", "polyglot"); ?></h4>

<?php echo $formHelper->create(); ?>
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

            <?php echo $formHelper->input("translations[$count][id]", array("type" => "hidden", "value" => htmlentities($translation->getId()))); ?>
            <?php echo $formHelper->input("translations[$count][original]", array("type" => "hidden", "value" => htmlentities($translation->getOriginal()))); ?>
            <?php echo $formHelper->input("translations[$count][context]", array("type" => "hidden", "value" => htmlentities($translation->getContext()))); ?>
            <?php echo $formHelper->input("translations[$count][plural]", array("type" => "hidden", "value" => "")); ?>
            <?php echo $formHelper->input("translations[$count][pluralTranslation]", array("type" => "hidden", "value" => "")); ?>

            <div>
                <label>
                    <?php echo __("Translation", "polyglot"); ?>
                    <?php echo $formHelper->input("translations[$count][translation]", array("value" => $translation->getTranslation())); ?>
                </label>
            </div>

            <div>
                <label>
                    <?php echo __("Comments", "polyglot"); ?>
                    <?php echo $formHelper->input("translations[$count][comments]", array("type" => "textarea", "value" => $translation->getTranslation())); ?>
                </label>
            </div>
        </div>

    <?php if (($count+1)%2===0 || ($count+1) >= count($translations)) : ?>
        </div>
    <?php endif; ?>

    <?php $count++; ?>
<?php endforeach; ?>


<?php echo $formHelper->submit(array("label" => __("Save", "polyglot"))); ?>
<?php echo $formHelper->end(); ?>
