<div class="taxonomies">
    <?php foreach ($polyglot->getTaxonomies() as $taxonomy) : ?>
        <div class="box">
            <?php echo $taxonomy->labels->name; ?>
            <br>
            <?php $enabled = $polyglot->isTaxonomyEnabled($taxonomy->name); ?>
            <a href="#" class="button default-button <?php echo $enabled ? "on" : "off" ?>" data-polyglot-ajax="click" data-polyglot-ajax-action="toggleTaxonomy" data-polyglot-ajax-param="<?php echo htmlentities($taxonomy->name); ?>" data-polyglot-ajax-target=".taxonomy-management"><?php _e("Toggle", "polyglot"); ?></a>
            <?php if ($enabled) : ?>
                <a href="<?php echo admin_url('options-general.php?page=polyglot-plugin&polyglot_action=editTaxnomyLabels&type=' . $taxonomy->name); ?>" class="button default-button" href=""><?php _e("Translate labels", "polyglot"); ?></a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
