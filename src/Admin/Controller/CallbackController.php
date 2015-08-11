<?php
namespace Polyglot\Admin\Controller;

use Polyglot\Admin\Router;

class CallbackController extends BaseController {

    /**
     * Adds the metabox registration hook.
     * @see Polyglot\Plugin\Adaptor\WordpressAdaptor
     */
    public function addMetaBox()
    {
        $type = get_post_type();
        if (!is_null($type) && $this->polyglot->isTypeEnabled($type)) {
            add_meta_box("polyglot-localization-metabox", __('Localization', "polyglot"), array($this, 'renderMetabox'), $type, 'side', 'high');
        }
    }

    /**
     * Renders the metabox that displays tranlation statuses.
     */
    public function renderMetabox()
    {
        if (get_post_status() == "auto-draft") {
            $this->view->set("invalidStatus", true);
        } else {
            $this->polyglot->contextualizeMappingByPost(get_post());
        }
        $this->render("metaboxTranslator");
    }

    /**
     * Adds the metabox registration on taxonomies
     */
    public function addTaxonomyLocaleSelect($taxonomy)
    {
        if (!is_null($taxonomy) && $this->polyglot->isTaxonomyEnabled($taxonomy->taxonomy)) {
            $this->polyglot->contextualizeMappingByTaxonomy($taxonomy);
            $this->render("metaboxTranslator");
        }
    }


    /**
     * Adds the locale selection box to the post edit page.
     * @see Polyglot\Plugin\Adaptor\WordpressAdaptor
     * @param array $views
     * @return array
     */
    public function addViewEditLocaleSelect($views)
    {
        $views["langfilter"] = $this->render("localeSelect");
        return $views;
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
