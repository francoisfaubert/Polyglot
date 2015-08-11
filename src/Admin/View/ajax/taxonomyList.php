<div class="taxonomies">
    <?php foreach ($configuration->getTaxonomies() as $taxonomy) : ?>
        <div class="box">
            <?php echo $taxonomy->labels->name; ?>
            <br>
            <?php $enabled = $configuration->isTaxonomyEnabled($taxonomy->name); ?>
            <a href="#" class="button default-button <?php echo $enabled ? "on" : "off" ?>" data-polyglot-ajax="click" data-polyglot-ajax-action="toggleTaxonomy" data-polyglot-ajax-param="<?php echo htmlentities($taxonomy->name); ?>" data-polyglot-ajax-target=".taxonomy-management"><?php _e("Toggle", "polyglot"); ?></a>
        </div>
    <?php endforeach; ?>
</div>
