<?php
namespace Polyglot\Admin\Controller;

use Polyglot\Plugin\Polyglot;
use Polyglot\Admin\Router;

class CallbackController extends BaseController {

    private $polyglot;

    public function before()
    {
        $this->polyglot = new Polyglot();
    }

    public function addMetaBox()
    {
        $type = get_post_type();
        if (!is_null($type) && $this->polyglot->isTypeEnabled($type)) {
            add_meta_box("polyglot-localization-metabox", __('Localization', "polyglot"), array($this->getRouter(), 'renderMetabox'), $type, 'side', 'high');
        }
    }

    public function renderMetabox()
    {
        $this->view->set("polyglot", $this->polyglot);
        $this->render("metaboxTranslator");
    }

    private function getRouter()
    {
        $router = new Router();
        $router->contextualize($this->adaptor);
        return $router;
    }

}
