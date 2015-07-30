<?php if (isset($translationId)) : ?>

    <p><?php _e("Localizing...", 'polyglot'); ?></p>
    <script>window.location = '<?php echo admin_url('post.php?post='.$translationId.'&action=edit&locale='.$targetLocale); ?>';</script>
<?php else : ?>

    <div class="alert">
        <p><?php echo $error; ?></p>

        <a href="<?php echo admin_url('edit.php?post='.$originalId.'&action=edit'); ?>"><?php _e("Go back to the original post.", 'polyglot'); ?></a>
    </div>

<?php endif; ?>
