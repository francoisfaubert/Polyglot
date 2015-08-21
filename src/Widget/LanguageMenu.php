<?php
namespace Polyglot\Widget;

use WP_Widget;
use Polyglot\Plugin\Polyglot;
use Strata\View\Template;

class LanguageMenu extends WP_Widget {

    public static function register()
    {
        register_widget(get_called_class());
    }

    function __construct()
    {
        $key = 'polyglot_locale_menu';
        $menuTitle = __('Locale Menu', 'polyglot');
        $config = array('description' => __('A list of link that allow a user to change the current locale of the website.', 'polyglot'));

        parent::__construct($key, $menuTitle, $config);
    }


    public function widget($args, $instance)
    {
        echo Template::parseFile($this->getTemplateSource(), array("polyglot" => Polyglot::instance(), "pluginSetup" => $args,  "config" => $instance));
    }

    public function form($instance)
    {
        $translateHome =  isset($instance['translateToCurrentPage']) && (bool)$instance['translateToCurrentPage'];
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('translateToCurrentPage'); ?>"><?php _e( 'Should translations link to the current page?' ); ?></label>
            <select class="widefat" id="<?php echo $this->get_field_id('translateToCurrentPage'); ?>" name="<?php echo $this->get_field_name('translateToCurrentPage'); ?>">
                <option <?php if ($translateHome) : ?>selected="selected"<?php endif; ?> value="1"><?php _e('Yes', 'polyglot'); ?></option>
                <option <?php if (!$translateHome) : ?>selected="selected"<?php endif; ?> value="0"><?php _e('No', 'polyglot'); ?></option>
            </select>
        </p>
    <?php
    }

    public function update($newInstance, $oldInstance)
    {
        return array(
            'translateToCurrentPage' => (bool)$newInstance['translateToCurrentPage']
        );
    }

    private function getTemplateSource()
    {
        return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'menu.php';
    }
}
