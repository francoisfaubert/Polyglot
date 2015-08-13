<div class="post-types">
    <?php foreach ($configuration->getPostTypes() as $postType) : ?>
        <div class="box">
            <a href="#" class="button default-button <?php echo $configuration->isTypeEnabled($postType->name) ? "on" : "off" ?>" data-polyglot-ajax="click" data-polyglot-ajax-action="togglePostType" data-polyglot-ajax-param="<?php echo htmlentities($postType->name); ?>" data-polyglot-ajax-target=".post-type-management">
                <?php echo $postType->labels->name; ?>
            </a>
        </div>
    <?php endforeach; ?>
</div>
