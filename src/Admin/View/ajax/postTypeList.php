<div class="post-types">
    <?php foreach ($polyglot->getPostTypes() as $postType) : ?>
        <div class="box">
            <?php echo $postType->labels->name; ?>
            <br>
            <?php $enabled = $polyglot->isTypeEnabled($postType->name); ?>
            <a href="#" class="button default-button <?php echo $enabled ? "on" : "off" ?>" data-polyglot-ajax="click" data-polyglot-ajax-action="togglePostType" data-polyglot-ajax-param="<?php echo htmlentities($postType->name); ?>" data-polyglot-ajax-target=".post-type-management"><?php _e("Toggle", "polyglot"); ?></a>
            <?php if ($enabled) : ?>
                <a href="<?php echo admin_url('options-general.php?page=polyglot-plugin&polyglot_action=editPostTypeLabels&type=' . $postType->name); ?>" class="button default-button" href=""><?php _e("Translate labels", "polyglot"); ?></a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
