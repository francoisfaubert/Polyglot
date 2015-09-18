<table class="widefat striped">
    <thead>
        <tr>
            <th scope="col" class="manage-column column-title">
                <span><?php _e("Taxonomies", 'polyglot'); ?></span>
            </th>
            <th scope="col" class="manage-column column-title"></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($configuration->getTaxonomies() as $idx => $taxonomy) : ?>
            <tr class="<?php echo ($idx % 2) === 0 ? "even" : "odd" ?>">
                <td>
                    <?php echo $taxonomy->labels->name; ?>
                </td>
                <td>
                    <a href="#" class="button default-button <?php echo $configuration->isTaxonomyEnabled($taxonomy->name) ? "on" : "off" ?>" data-polyglot-ajax="click" data-polyglot-ajax-action="toggleTaxonomy" data-polyglot-ajax-param="<?php echo htmlentities($taxonomy->name); ?>" data-polyglot-ajax-target=".taxonomy-management">
                        <?php echo $configuration->isTaxonomyEnabled($taxonomy->name) ? "Localized" : "Not localized" ?>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
