<?php
namespace Polyglot\Admin;

use Polyglot\Plugin\Adaptor\WordpressAdaptor;
use Polyglot\Plugin\Polyglot;

use Polyglot\Admin\Controller\AdminController;
use Polyglot\Admin\Controller\AdminAjaxController;
use Polyglot\Admin\Controller\CallbackController;

use Strata\View\Template;
use Strata\Controller\Request;

class Router  {

    protected $adaptor;

    public function __call($name, $arguments)
    {
        $this->callbackController($name, $arguments);
    }

    public function contextualize($adaptor)
    {
        $this->adaptor = $adaptor;
    }

    public function autoroute()
    {
        $request = new Request();

        if ($request->hasPost('polyglot_ajax_action')) {
            return $this->ajaxController($request->post('polyglot_ajax_action'));
        }

        if ($request->hasGet('polyglot_action')) {
            return $this->adminController($request->get('polyglot_action'));
        }

       $this->adminController("index");
    }


    public function callbackController($action = "index", $arguments = array())
    {
        $ctrl = new CallbackController();
        $ctrl->contextualize($this->adaptor);
        $ctrl->init();
        $ctrl->before();
        call_user_func_array(array($ctrl, $action), $arguments);
        $ctrl->after();
    }

    public function adminController($action = "index", $arguments = array())
    {
        $ctrl = new AdminController();
        $ctrl->contextualize($this->adaptor);
        $ctrl->init();
        $ctrl->before();
        call_user_func_array(array($ctrl, $action), $arguments);
        $ctrl->after();
    }

    private function ajaxController($action = "index")
    {
        $ctrl = new AdminAjaxController();
        $ctrl->contextualize($this->adaptor);
        $ctrl->init();
        $ctrl->before();
        call_user_func(array($ctrl, $action));
        $ctrl->after();
    }

    protected function render($filename, $variables = array(), $extension = '.php')
    {
        $path = implode(DIRECTORY_SEPARATOR, array_merge(array($this->adaptor->getAdminViewPath()), $this->viewPathExtra));
        $filename = $path . DIRECTORY_SEPARATOR . $filename . $extension;

        $this->view->render(array(
            "content" => Template::parseFile($filename, $this->view->getVariables())
        ));
    }
}
