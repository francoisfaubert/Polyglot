<?php
namespace Polyglot\Admin\Controller;

use Polyglot\Plugin\Polyglot;
use Polyglot\Plugin\Adaptor\WordpressAdaptor;

use Strata\Strata;
use Strata\View\Template;

class BaseController extends \Strata\Controller\Controller {

    protected $adaptor; // plugin location
    protected $polyglot; // polyglot plugin dynamic post associations
    protected $i18n; // strata static file configuration

    public function before()
    {
        $this->polyglot = new Polyglot();
        $this->view->set("polyglot", $this->polyglot);

        $app = Strata::app();
        $this->view->set("i18n", $app->i18n);
    }

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
