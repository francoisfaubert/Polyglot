<h2><?php _e("Polyglot", $polyglot->getTextDomain()); ?></h2>

<h3><?php _e("Locale management", $polyglot->getTextDomain()); ?></h3>


<button data-ajax="click-popup" data-ajax-action="createLocale" class="button button-primary" name="save" type="button"><?php _e("Add locale", $polyglot->getTextDomain()); ?></button>
<button data-ajax="click" data-ajax-action="loadSummary" data-ajax-target=".existing-locales" class="button" name="refresh" type="button"><?php _e("Refresh", $polyglot->getTextDomain()); ?></button>
<button data-ajax="click-popup" data-ajax-action="gettextLookup" data-ajax-target=".existing-locales" class="button" name="refresh" type="button"><?php _e("Strings lookup", $polyglot->getTextDomain()); ?></button>

<div class="existing-locales" data-ajax="autoload", data-ajax-action="loadSummary">
    <?php _e("Loading", $polyglot->getTextDomain()); ?>
</div>


<h3><?php _e("String translations", $polyglot->getTextDomain()); ?></h3>
