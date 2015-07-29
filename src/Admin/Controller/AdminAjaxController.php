<?php
namespace Polyglot\Admin\Controller;

use Polyglot\Plugin\Polyglot;
use Polyglot\Admin\Form\StringTranslationForm;

class AdminAjaxController extends BaseController {

    private $polyglot;

    public function before()
    {
        $this->polyglot = new Polyglot();
        $this->view->set("polyglot", $this->polyglot);
    }

    public function viewPostTypeList()
    {
        $this->render("postTypeList");
    }

    public function viewTaxonomyList()
    {
        $this->render("taxonomyList");
    }

    public function togglePostType()
    {
        $this->polyglot->togglePostType($this->request->post("param"));
        $this->viewPostTypeList();
    }

    public function toggleTaxonomy()
    {
        $this->polyglot->toggleTaxonomy($this->request->post("param"));
        $this->viewTaxonomyList();
    }

    protected function getTemplatePath()
    {
        return $this->adaptor->getAdminViewPath() . DIRECTORY_SEPARATOR . "ajax" . DIRECTORY_SEPARATOR;
    }

}
