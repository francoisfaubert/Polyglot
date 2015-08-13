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

        if ($params[1] == "post") {
            $defaultLocale = $this->polyglot->getDefaultLocale();
            $orignalPost = $defaultLocale->getTranslatedPost($objId);

            $this->view->set("originalTitle", $orignalPost->post_title);
            $this->view->set("objId", $objId);
            $this->view->set("mode", "post");

        } else {
            // $taxonomies = $this->polyglot->query()->findCachedTaxonomyById($params[1], $params[0]);
            // $taxonomy = $taxonomies[0];
            // $originalObject = $this->polyglot->contextualizeMappingByTaxonomy($taxonomy);
            // $this->view->set("originalTitle", $taxonomy->name);
            // $this->view->set("mode", "term");
        }

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

    protected function parseComplexParams()
    {
        $param = $this->request->post("param");
        return explode("#", $param);
    }

}
