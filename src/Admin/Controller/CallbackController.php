<?php
namespace Polyglot\Admin\Controller;

use Polyglot\Admin\Router;
use Polyglot\Plugin\Db\Query;

class CallbackController extends BaseController {

    public function addMetaBox()
    {
        $type = get_post_type();
        if (!is_null($type) && $this->polyglot->isTypeEnabled($type)) {
            add_meta_box("polyglot-localization-metabox", __('Localization', "polyglot"), array($this->getRouter(), 'renderMetabox'), $type, 'side', 'high');
        }
    }

    public function renderMetabox()
    {
        if (get_post_status() == "auto-draft") {
            $this->view->set("invalidStatus", true);
        } else {
            $this->polyglot->getMapper()->assignMappingByPost(get_post());
        }
        $this->render("metaboxTranslator");
    }

    public function addViewEditLocaleSelect($views)
    {
        $views["langfilter"] = $this->render("localeSelect");
        return $views;
    }

    private function getRouter()
    {
        $router = new Router();
        $router->contextualize($this->adaptor);
        return $router;
    }

}
