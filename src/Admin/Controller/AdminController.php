<?php
namespace Polyglot\Admin\Controller;

use Polyglot\Plugin\Translator\Translator;
use Exception;

/**
 * Receives all the actions required by the plugin's administration
 * area.
 */
class AdminController extends BaseController {

    /**
     * Basic entry point
     * @return null
     */
    public function index()
    {
        $this->render('index');
    }

    /**
     * Edits the locale translation string.
     * @return null
     */
    public function editLocale()
    {
        // $this->view->set("formHelper", $form->getHelper());
        // $form = new StringTranslationForm($this->request, $this->view);

        $this->loadHelper("Form");

        $localeCode = $this->request->get("locale");
        $locale = $this->polyglot->getLocaleByCode($localeCode);

        if ($this->request->isPost()) {
            $this->polyglot->saveTranslations($locale, $this->request->post("data.translations"));
        }

        $this->view->set("locale", $locale);
        $this->view->set("translations", $this->polyglot->getTranslations($localeCode));

        $this->render("editLocale");
    }

    /**
     * Transition page that duplicates the translated object.
     * @return null
     */
    public function createTranslationDuplicate()
    {
        $id = (int)$this->request->get("object");
        $kind = $this->request->get("objectKind");
        $type = $this->request->get("objectType");
        $localeCode = $this->request->get("locale");

        try {
            $tanslator = Translator::factory($kind);
            $tanslator->translate($id, $type, $localeCode);

            $this->view->set("translationObj", $tanslator->getTranslatedObject());
            $this->view->set("destinationLink", $tanslator->getForwardUrl());
        } catch(Exception $e) {
            $this->view->set("error", $e->getMessage());
        }

        $this->view->set("originalId", $id);
        $this->render("duplicating");
    }

}
