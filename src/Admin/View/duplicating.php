<?php if (isset($destinationLink)) : ?>

    <div class="localizing">
        <p><?php _e("Localizing...", 'polyglot'); ?></p>
        <script>window.location = '<?php echo $destinationLink; ?>';</script>
    </div>

<?php else : ?>

    <div class="alert">
        <p><?php echo $error; ?></p>
        <a onclick="window.history.back();" href="#"><?php _e("Go back.", 'polyglot'); ?></a>
    </div>

<?php endif; ?>
