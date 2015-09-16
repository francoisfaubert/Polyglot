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

    <br clear="all">

    <h3><?php _e("Add strings", "polyglot"); ?></h3>

    <?php if (isset($addedString) && $addedString) : ?>
        <p class="success"><?php _e("String added successfully", "polyglot"); ?></p>
    <?php endif; ?>

    <?php echo $FormHelper->create(); ?>
        <?php echo $FormHelper->input("mode", array("type" => "hidden", "value" => "add")); ?>
        <?php echo $FormHelper->input("translation[context]", array("type" => "hidden", "value" => "")); ?>
        <?php echo $FormHelper->input("translation[plural]", array("type" => "hidden", "value" => "")); ?>
        <?php echo $FormHelper->input("translation[pluralTranslation]", array("type" => "hidden", "value" => "")); ?>
        <div>
            <label>
                <?php echo __("Original key string", "polyglot"); ?>
                <?php echo $FormHelper->input("translation[original]"); ?>
            </label>
        </div>

        <div>
            <label>
                <?php echo __("Translation", "polyglot"); ?>
                <?php echo $FormHelper->input("translation[translation]"); ?>
            </label>
        </div>

        <?php echo $FormHelper->submit(array("label" => __("Add", "polyglot"), "class" => "button button-primary")); ?>
    <?php echo $FormHelper->end(); ?>


    <hr>


    <h3><?php _e("Edit string", "polyglot"); ?></h3> <a class="button" href="<?php echo admin_url('options-general.php?page=polyglot-plugin&polyglot_action=batchEdit&locale=' . $locale->getCode()); ?>"><?php _e("Batch edit all"); ?></a>


    <h4><?php _e("Search for string", "polyglot"); ?></h4>
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



</div>
