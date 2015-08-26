<?php
namespace Polyglot\Admin\Controller;

use Strata\Strata;
use Polyglot\Admin\Router;

class CallbackController extends BaseController {

    /**
     * Adds the metabox registration hook.
     * @see Polyglot\Plugin\Adaptor\WordpressAdaptor
     */
    public function addMetaBox()
    {
        $type = get_post_type();
        $configuration = $this->polyglot->getConfiguration();

        if (!is_null($type) && $configuration->isTypeEnabled($type)) {
            add_meta_box("polyglot-localization-metabox", __('Localization', "polyglot"), array($this, 'renderPostMetabox'), $type, 'side', 'high');

            $locale = $this->polyglot->getCurrentLocale();
            if (!$locale->isDefault()) {
                remove_meta_box("formatdiv", "post", "side");
                // remove_meta_box("categorydiv", "post", "side");
                remove_meta_box("tagsdiv-post_tag", "post", "side");

                remove_meta_box("formatdiv", "page", "side");
                // remove_meta_box("categorydiv", "page", "side");
                remove_meta_box("pageparentdiv", "page", "side");
                remove_meta_box("tagsdiv-post_tag", "page", "side");
            }
        }
    }

    public function addLocalizationColumn($columns)
    {
        // $columns['polyglot_locales'] = __('i18n', 'polyglot');
        return $columns;
    }

    public function renderLocalizationColumn($column, $post_id)
    {
        echo "dude";
        // if ($column === "polyglot_locales") {
        //     $this->view->set("obj_id", $post_id);
        //     // $this->view->set("obj_type", get_post_type());
        //     $this->render("buttonTranslator");
        // }
    }

    /**
     * Renders the metabox that displays tranlation statuses.
     */
    public function renderPostMetabox()
    {
        if (get_post_status() == "auto-draft") {
            $this->view->set("invalidStatus", true);
        } else {
            $this->view->set("obj_id", get_the_ID());
            $this->view->set("obj_type", get_post_type());
        }
        $this->render("metaboxTranslator");
    }


    /**
     * Adds the metabox registration on taxonomies
     */
    public function addTaxonomyLocaleSelect($taxonomy)
    {
        $configuration = $this->polyglot->getConfiguration();

        if (!is_null($taxonomy) && $configuration->isTaxonomyEnabled($taxonomy->taxonomy)) {
            $this->view->set("obj_id", $taxonomy->term_id);
            $this->view->set("obj_type", $taxonomy->taxonomy);
            $this->render("metaboxTranslator");
        }
    }

    /**
     * Returns a router object that is aware of the current plugin context
     * for routing urls.
     * @return Router
     */
    private function getRouter()
    {
        $router = new Router();
        $router->contextualize($this->adaptor);
        return $router;
    }

}
