<table class="widefat striped">
    <thead>
        <tr>
            <th scope="col" id="polyglot-post-type" class="manage-column column-title">
                <span><?php _e("Post types", 'polyglot'); ?></span>
            </th>
            <th scope="col" id="polyglot-toggle" class="manage-column column-title"></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($configuration->getPostTypes() as $idx => $postType) : ?>
            <tr class="<?php echo ($idx % 2) === 0 ? "even" : "odd" ?>">
                <td>
                    <?php echo $postType->labels->name; ?>
                </td>
                <td>
                    <a href="#" class="button default-button <?php echo $configuration->isTypeEnabled($postType->name) ? "on" : "off" ?>" data-polyglot-ajax="click" data-polyglot-ajax-action="togglePostType" data-polyglot-ajax-param="<?php echo htmlentities($postType->name); ?>" data-polyglot-ajax-target=".post-type-management">
                        <?php echo $configuration->isTypeEnabled($postType->name) ? "Localized" : "Not localized" ?>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
