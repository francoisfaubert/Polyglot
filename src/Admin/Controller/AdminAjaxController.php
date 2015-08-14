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
        $params = $this->parseComplexParams();
        $objId = (int)$params[0];
        $this->view->set("objId", $objId);

        return ($params[1] == "post") ?
            $this->switchPostTranslation($objId) :
            $this->switchTermTranslation($objId, $params[1]);

    }

    /**
     * Generates the template file path base directory.
     * @return string
     */
    protected function getTemplatePath()
    {
        return parent::getTemplatePath() . "ajax" . DIRECTORY_SEPARATOR;
    }

    protected function parseComplexParams()
    {
        $param = $this->request->post("param");
        return explode("#", $param);
    }

    protected function switchPostTranslation($objId)
    {
        $defaultLocale = $this->polyglot->getDefaultLocale();
        $orignalPost = $defaultLocale->getTranslatedPost($objId);

        $this->view->set("originalPost", $orignalPost);
        $this->render("switchPostTranslation");
    }

    protected function switchTermTranslation($objId, $objType)
    {
        $term = $this->polyglot->query()->findTermById($objId, $objType);

        $this->view->set("originalTerm", $term);
        $this->render("switchTermTranslation");
    }

}
