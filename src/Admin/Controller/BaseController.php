<?php
namespace Polyglot\Admin\Controller;

use Polyglot\Plugin\Polyglot;
use Strata\Strata;
use Strata\View\Template;

class BaseController extends \Strata\Controller\Controller {


    /** @var string plugin location */
    protected $adaptor;

    /** @var Polyglot local reference to the global object */
    protected $polyglot;

    /** @var i18n local reference to Strata's i18n object */
    protected $i18n;

    /**
     * Executed before each controller requests.
     */
    public function before()
    {
        $this->polyglot = Polyglot::instance();
        $this->view->set("polyglot", $this->polyglot);

        $app = Strata::app();
        $this->view->set("i18n", $app->i18n);
    }

    /**
     * Contextualizes the controller file in order for it
     * to understand where it needs to look to loads template files.
     * @param  string $adaptor The plugin include file path
     */
    public function contextualize($adaptor)
    {
        $this->adaptor = $adaptor;
    }

    /**
     * Renders a templated view file
     * @param  string $filename
     * @param  array  $variables
     * @param  string $extension Defaults to .php
     */
    protected function render($filename, $variables = array(), $extension = '.php')
    {
        $filename =  $this->getTemplatePath() . $filename . $extension;

        $this->view->render(array(
            "content" => Template::parseFile($filename, $this->view->getVariables())
        ));
    }

    /**
     * Generates the template file path base directory.
     * @return string
     */
    protected function getTemplatePath()
    {
        $paths = array(dirname($this->adaptor->loaderPath), 'src', 'Admin', 'View');
        return  implode(DIRECTORY_SEPARATOR, $paths) . DIRECTORY_SEPARATOR;
    }
}
