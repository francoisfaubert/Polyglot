<?php
namespace Polyglot\Admin\Controller;

use Polyglot\Plugin\Db\Query;

class AdminAjaxController extends BaseController {

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

    public function switchTranslation()
    {
        $targetPost = get_post($this->request->post("param"));
        $this->polyglot->getMapper()->assignMappingByPost($targetPost);

        $this->view->set("originalPost", $this->polyglot->findOriginalPost($targetPost));
        $this->render("switchTranslation");
    }

    protected function getTemplatePath()
    {
        return $this->adaptor->getAdminViewPath() . DIRECTORY_SEPARATOR . "ajax" . DIRECTORY_SEPARATOR;
    }

}
