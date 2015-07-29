<?php
namespace Polyglot\Admin\Controller;

use Polyglot\Plugin\Polyglot;
use Polyglot\Admin\Form\StringTranslationForm;

class AdminController extends BaseController {

    private $polyglot;

    public function before()
    {
        $this->polyglot = new Polyglot();
        $this->view->set("polyglot", $this->polyglot);
    }

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

}
