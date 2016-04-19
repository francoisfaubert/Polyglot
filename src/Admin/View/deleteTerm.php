<?php if (isset($success) && $success) : ?>

    <div class="localizing">
        <h2><?php _e("Deleting...", 'polyglot'); ?></h2>
        <script>window.location = '<?php echo $destinationLink; ?>';</script>
    </div>

<?php else : ?>

    <div class="alert">
        <p><?php _e("We could not delete the localized term.", "polyglot"); ?></p>
        <a onclick="window.history.back();" href="#"><?php _e("Go back.", 'polyglot'); ?></a>
    </div>

<?php endif; ?>
