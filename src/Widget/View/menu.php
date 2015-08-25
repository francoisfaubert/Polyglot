<div class="polyglot_locale_widget">
    <ul>
    <?php foreach ($polyglot->getLocales() as $locale) : ?>

        <?php if ($config['translateToCurrentPage'] && $locale->hasPostTranslation()) : ?>
            <?php $translatedPost = $locale->getTranslatedPost(); ?>
            <?php if ($translatedPost) : ?>
                <li><a href="<?php echo get_the_permalink($translatedPost->ID); ?>" hreflang="<?php echo $locale->getCode(); ?>"><?php echo $translatedPost->post_title; ?></a></li>
            <?php endif; ?>
        <?php else : ?>
            <li><a href="<?php echo $locale->getHomeUrl(); ?>" hreflang="<?php echo $locale->getCode(); ?>"><?php echo $locale->getNativeLabel(); ?></a></li>
        <?php endif; ?>
    <?php endforeach;   ?>
    </ul>
</div>
