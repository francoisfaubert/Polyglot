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
        $configuration = $this->polyglot->getConfiguration();

        if (!is_null($type) && $configuration->isTypeEnabled($type)) {
            add_meta_box("polyglot-localization-metabox", __('Localization', "polyglot"), array($this, 'renderPostMetabox'), $type, 'side', 'high');
        }
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
            $this->view->set("obj_type", "post");
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
            $this->polyglot->contextualizeMappingByTaxonomy($taxonomy);

            $this->view->set("obj_id", $taxonomy->term_id);
            $this->view->set("obj_type", $taxonomy->taxonomy);
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
