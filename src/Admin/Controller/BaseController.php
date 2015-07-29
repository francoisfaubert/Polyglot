<?php
namespace Polyglot\Admin\Controller;

use Polyglot\Plugin\Adaptor\WordpressAdaptor;

use Strata\View\Template;

class BaseController extends \Strata\Controller\Controller {

    protected $adaptor;

    public function contextualize($adaptor)
    {
        $this->adaptor = $adaptor;
    }

    protected function render($filename, $variables = array(), $extension = '.php')
    {
        $filename =  $this->getTemplatePath() . $filename . $extension;

        $this->view->render(array(
            "content" => Template::parseFile($filename, $this->view->getVariables())
        ));
    }

    protected function getTemplatePath()
    {
        return $this->adaptor->getAdminViewPath() . DIRECTORY_SEPARATOR;
    }
}
