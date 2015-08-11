<div class="post-types">
    <?php foreach ($configuration->getPostTypes() as $postType) : ?>
        <div class="box">
            <?php echo $postType->labels->name; ?>
            <br>
            <?php $enabled = $configuration->isTypeEnabled($postType->name); ?>
            <a href="#" class="button default-button <?php echo $enabled ? "on" : "off" ?>" data-polyglot-ajax="click" data-polyglot-ajax-action="togglePostType" data-polyglot-ajax-param="<?php echo htmlentities($postType->name); ?>" data-polyglot-ajax-target=".post-type-management"><?php _e("Toggle", "polyglot"); ?></a>
        </div>
    <?php endforeach; ?>
</div>
