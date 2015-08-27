<?php
namespace Polyglot\Admin\Controller;

/**
 * Contains every Ajax requests made by Polyglot
 * within the Wordpress backend. Either from within actual
 * plugin files or the different hooks Polyglot has registered
 * throughout the application.
 */
class AdminAjaxController extends BaseController {

    /**
     * Displays the enabled post types in Polyglot's option page.
     * @return null
     */
    public function viewPostTypeList()
    {
        $this->view->set("configuration", $this->polyglot->getConfiguration());
        $this->render("postTypeList");
    }

    /**
     * Displays the enabled taxonomies in Polyglot's option page.
     * @return null
     */
    public function viewTaxonomyList()
    {
        $this->view->set("configuration", $this->polyglot->getConfiguration());
        $this->render("taxonomyList");
    }

    /**
     * Toggles a post on or off so it is handled by Polyglot.
     * @return null
     */
    public function togglePostType()
    {
        $configuration = $this->polyglot->getConfiguration();
        $configuration->togglePostType($this->request->post("param"));
        $this->viewPostTypeList();
    }

    /**
     * Toggles a taxonomy on or off so it is handled by Polyglot.
     * @return null
     */
    public function toggleTaxonomy()
    {
        $configuration = $this->polyglot->getConfiguration();
        $configuration->toggleTaxonomy($this->request->post("param"));
        $this->viewTaxonomyList();
    }


    /**
     * Renders a popup that allows a user to browse the different
     * translations of a term or a post.
     * @return null
     */
    public function switchTranslation()
    {
        $params = $this->parseComplexParams();
        $objId = (int)$params[0];
        $this->view->set("objId", $objId);

        $configuration = $this->polyglot->getConfiguration();

        if (in_array($params[1], $configuration->getEnabledPostTypes())) {
            $this->switchPostTranslation($objId);
        }

        if (in_array($params[1], $configuration->getEnabledTaxonomies())) {
            $this->switchTermTranslation($objId, $params[1]);
        }
    }

    /**
     * Returns the template file path base directory.
     * @return string
     */
    protected function getTemplatePath()
    {
        return parent::getTemplatePath() . "ajax" . DIRECTORY_SEPARATOR;
    }

    /**
     * Parses the request's "param" POST value to decompose the
     * current object ID and kind.
     * @return array (string)ID, (string)Kind.
     */
    protected function parseComplexParams()
    {
        $param = $this->request->post("param");
        return explode("#", $param);
    }

    /**
     * Renders a popup that allows a user to browse the different
     * translations of a post.
     * @return null
     */
    protected function switchPostTranslation($objId)
    {
        $defaultLocale = $this->polyglot->getDefaultLocale();
        $orignalPost = $defaultLocale->getTranslatedPost($objId);

        $this->view->set("originalPost", $orignalPost);
        $this->render("switchPostTranslation");
    }

    /**
     * Renders a popup that allows a user to browse the different
     * translations of a term.
     * @return null
     */
    protected function switchTermTranslation($objId, $objType)
    {
        $defaultLocale = $this->polyglot->getDefaultLocale();
        $orignalTerm = $defaultLocale->getTranslatedTerm($objId, $objType);

        $this->view->set("originalTerm", $orignalTerm);
        $this->render("switchTermTranslation");
    }
}
