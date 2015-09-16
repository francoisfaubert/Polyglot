<div class="polyglot-page">

    <header>
        <div class="title">
            <h1><?php _e("Localization", "polyglot"); ?></h1>
            <h2><?php _e('Scanning...', 'polyglot'); ?></h2>
        </div>
    </header>

    <p><?php _e('Polyglot is scanning the project\'s for discoverable string translations.', 'polyglot'); ?></p>

    <div class="output"><?php echo nl2br($output); ?></div>

    <?php if (isset($returnLocale)) : ?>
        <p><?php echo sprintf(__('The scan is complete, you may now <a href="%s">edit your translations</a>', 'polyglot'), $returnLocale->getEditUrl()); ?></p>
    <?php else : ?>
        <?php $url = admin_url('options-general.php?page=polyglot-plugin'); ?>
        <p><?php echo sprintf(__('The scan is complete, you may now <a href="%s">return to the main page</a>', 'polyglot'), $url); ?></p>
    <?php endif; ?>

</div>
