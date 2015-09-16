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
    </header>

<br clear="all">

    <h3><?php _e("Search for string", "polyglot"); ?></h3>
    <?php echo $FormHelper->create(null, array("type" => "GET")); ?>
        <input type="hidden" name="page" value="polyglot-plugin">
        <input type="hidden" name="polyglot_action" value="searchString">
        <?php echo $FormHelper->input("locale", array("name" => "locale", "type" => "hidden", "value" => $locale->getCode())); ?>

        <div>
            <label>
                <?php echo __("Original key string", "polyglot"); ?>
                <?php echo $FormHelper->input("translation[original]"); ?>
            </label>
        </div>

        <?php echo $FormHelper->submit(array("label" => __("Search", "polyglot"), "class" => "button button-primary")); ?>
    <?php echo $FormHelper->end(); ?>

    <h3><?php _e("Search results", "polyglot"); ?></h3>
    <p><?php echo sprintf(__("Searching for '%s'", "polyglot"), $searchQuery); ?></p>


    <?php if (isset($translations) && count($translations)) : ?>

        <?php echo $FormHelper->create(); ?>
            <?php echo $FormHelper->input("mode", array("type" => "hidden", "value" => "edit")); ?>

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
                            <?php echo $FormHelper->input("translations[$count][translation]", array("type" => "textarea", "value" => $translation->getTranslation())); ?>
                        </label>
                    </div>
                </div>

            <?php if (($count+1)%2===0 || ($count+1) >= count($translations)) : ?>
                </div>
            <?php endif; ?>

            <?php $count++; ?>
        <?php endforeach; ?>

            <?php echo $FormHelper->submit(array("label" => __("Save", "polyglot"), "class" => "button button-primary")); ?>
        <?php echo $FormHelper->end(); ?>
    <?php else : ?>
        <p><?php _e('We did not find any strings matching this query', 'polyglot'); ?></p>
    <?php endif; ?>
</div>
