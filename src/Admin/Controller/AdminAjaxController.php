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
        $originalPost = $this->polyglot->contextualizeMappingByPost($targetPost);

        $this->view->set("originalPost", $originalPost);
        $this->render("switchTranslation");
    }

    /**
     * Generates the template file path base directory.
     * @return string
     */
    protected function getTemplatePath()
    {
        return parent::getTemplatePath() . "ajax" . DIRECTORY_SEPARATOR;
    }

}
