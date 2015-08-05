<?php _e("View in locale", 'polyglot'); ?> :

<select name="polyglot-language-switch" class="postform" onchange="window.location = jQuery(this).val();">
    <?php foreach ($polyglot->getLocales() as $locale) : ?>
         <option value="<?php echo add_query_arg(array("locale" => $locale->getCode())); ?>" <?php if ($polyglot->isCurrentlyActive($locale)) :?>selected="selected"<?php endif; ?>><?php echo __($locale->getNativeLabel()); ?></option>
    <?php endforeach; ?>
</select>
