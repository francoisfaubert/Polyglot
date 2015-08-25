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
            $locale = $this->polyglot->getLocaleByCode($this->request->get("locale"));


            $newTranslationObjId = $this->polyglot->query()->addTranslation(
                (int)$this->request->get("object"),
                $this->request->get("objectType"),
                $this->request->get("objectKind"),
                $locale->getCode()
            );

            switch ($this->request->get("objectKind")) {
                case 'WP_Post':
                    $post = $this->polyglot->query()->findPostById($newTranslationObjId);
                    $this->view->set("translationObj", $post);
                    $this->view->set("destinationLink", $locale->getEditPostUrl($newTranslationObjId));
                    break;

                case 'Term' :
                    $term = $this->polyglot->query()->findTermById($newTranslationObjId, $this->request->get("objectKind"));
                    $this->view->set("translationObj", $term);
                    $this->view->set("destinationLink", $locale->getEditTermUrl($newTranslationObjId, $this->request->get("objectType")));
                    break;

                default : throw new Exception("Polyglot does not know how to translate this.");
            }

        } catch(Exception $e) {
            $this->view->set("error", $e->getMessage());
        }

        $this->view->set("originalId", $this->request->get("object"));
        $this->render("duplicating");
    }

}
