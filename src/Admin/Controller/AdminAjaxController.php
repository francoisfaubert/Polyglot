<?php
namespace Polyglot\Admin\Controller;

use Polyglot\Plugin\Db\Query;

class AdminAjaxController extends BaseController {

    public function viewPostTypeList()
    {
        $this->view->set("configuration", $this->polyglot->getConfiguration());
        $this->render("postTypeList");
    }

    public function viewTaxonomyList()
    {
        $this->view->set("configuration", $this->polyglot->getConfiguration());
        $this->render("taxonomyList");
    }

    public function togglePostType()
    {
        $configuration = $this->polyglot->getConfiguration();
        $configuration->togglePostType($this->request->post("param"));
        $this->viewPostTypeList();
    }

    public function toggleTaxonomy()
    {
        $configuration = $this->polyglot->getConfiguration();
        $configuration->toggleTaxonomy($this->request->post("param"));
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
