<?php
namespace Polyglot\Admin\Controller;

use Strata\Strata;
use Strata\View\Template;

/**
 * Base class of every Polyglot controllers. It automates
 * the creation of controllers that understand the plugin's context
 * and know how to render views.
 */
class BaseController extends \Strata\Controller\Controller {

    /** @var string plugin location */
    protected $adaptor;

    /** @var i18n local reference to Strata's i18n object */
    protected $i18n;

    /**
     * Executed before each controller requests.
     */
    public function before()
    {
        $this->i18n = Strata::i18n();
        $this->view->set("i18n", $this->i18n);
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
        $dirname = dirname(Strata::config('runtime.polyglot.loaderPath'));
        $paths = array($dirname, 'src', 'Admin', 'View');
        return  implode(DIRECTORY_SEPARATOR, $paths) . DIRECTORY_SEPARATOR;
    }
}
