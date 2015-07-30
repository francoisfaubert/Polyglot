<?php
namespace Polyglot\Admin\Controller;

use Exception;
use Polyglot\Plugin\Db\Query;
use Polyglot\Admin\Form\StringTranslationForm;

class AdminController extends BaseController {

    public function index()
    {
        $this->render('index');
    }

    public function editLocale()
    {
        $form = new StringTranslationForm($this->request, $this->view);
        $localeCode = $this->request->get("locale");
        $locale = $this->polyglot->getLocaleByCode($localeCode);

        if ($this->request->isPost()) {
            $this->polyglot->saveTranslations($locale, $this->request->post("data.translations"));
        }

        $this->view->set("formHelper", $form->getHelper());
        $this->view->set("locale", $locale);
        $this->view->set("translations", $this->polyglot->getTranslations($localeCode));

        $this->render("editLocale");
    }

    public function editPostTypeLabels()
    {
        $form = new StringTranslationForm($this->request, $this->view);

        if ($this->request->isPost()) {
            foreach ($this->request->post("data.translations") as $code => $translation) {
                $locale = $this->polyglot->getLocaleByCode($code);
                $this->polyglot->saveTranslations($locale, $translation);
            }
        }

        $this->view->set("formHelper", $form->getHelper());
        $this->view->set("postType", get_post_type_object($this->request->get("type")));

        $this->render("editPostTypeLabels");
    }

    public function editTaxnomyLabels()
    {
        $form = new StringTranslationForm($this->request, $this->view);

        if ($this->request->isPost()) {
            foreach ($this->request->post("data.translations") as $code => $translation) {
                $locale = $this->polyglot->getLocaleByCode($code);
                $this->polyglot->saveTranslations($locale, $translation);
            }
        }

        $this->view->set("formHelper", $form->getHelper());
        $this->view->set("taxonomy", get_taxonomy($this->request->get("type")));

        $this->render("editPostTypeLabels");
    }

    public function createTranslationDuplicate()
    {
        try {
            $query = new Query();
            $newTranslationObjId = $query->addPostTranslation((int)$this->request->get("object"), $this->request->get("objectType"), $this->request->get("objectKind"), $this->request->get("locale"));
            $this->view->set("translationId", $newTranslationObjId);
            $this->view->set("targetLocale", $this->request->get("locale"));

        } catch(Exception $e) {
            $this->view->set("error", $e->getMessage());
        }

        $this->view->set("originalId", $this->request->get("object"));
        $this->render("duplicating");

    }

}
