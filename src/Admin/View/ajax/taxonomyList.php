<div class="taxonomies">
    <?php foreach ($configuration->getTaxonomies() as $taxonomy) : ?>
        <div class="box">
            <a href="#" class="button default-button <?php echo $configuration->isTaxonomyEnabled($taxonomy->name) ? "on" : "off" ?>" data-polyglot-ajax="click" data-polyglot-ajax-action="toggleTaxonomy" data-polyglot-ajax-param="<?php echo htmlentities($taxonomy->name); ?>" data-polyglot-ajax-target=".taxonomy-management">
                <?php echo $taxonomy->labels->name; ?>
            </a>
        </div>
    <?php endforeach; ?>
</div>
