<?php
namespace Polyglot\Admin\Controller;

use Polyglot\Plugin\Translator\Translator;
use Strata\Shell\Command\I18nCommand;

use Exception;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Application;

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
        $locale = $this->polyglot->getDefaultLocale();
        $modifiedDate = $locale->hasPoFile() ? date("F d Y H:i:s.", filemtime($locale->getPoFilePath())) : null;
        $this->view->set("modifiedDate", $modifiedDate);

        $this->render('index');
    }

    /**
     * Edits the locale translation string.
     * @return null
     */
    public function editLocale()
    {
        $this->view->loadHelper("Form");

        $localeCode = $this->request->get("locale");
        $locale = $this->polyglot->getLocaleByCode($localeCode);
        $this->view->set("locale", $locale);

        $modifiedDate = $locale->hasPoFile() ? date("F d Y H:i:s.", filemtime($locale->getPoFilePath())) : null;
        $this->view->set("modifiedDate", $modifiedDate);

        if ($this->request->isPost()) {
            $newString = array($this->request->post("data.translation"));
            $this->polyglot->saveTranslations($locale, $newString);
            $this->view->set("addedString", true);
        }

        $this->render("editLocale");
    }

    public function batchEdit()
    {
        $this->view->loadHelper("Form");

        $localeCode = $this->request->get("locale");
        $locale = $this->polyglot->getLocaleByCode($localeCode);

        if ($this->request->isPost()) {
            $this->polyglot->saveTranslations($locale, $this->request->post("data.translations"));
        }

        $this->view->set("locale", $locale);

        try {
            $this->view->set("translations", $this->polyglot->getTranslations($localeCode));
        } catch (Exception $e) {
        }

        $this->render("batchEdit");
    }

    public function searchString()
    {
        $this->view->loadHelper("Form");

        $localeCode = $this->request->get("locale");
        $locale = $this->polyglot->getLocaleByCode($localeCode);

        if ($this->request->isPost()) {
            $this->polyglot->saveTranslations($locale, $this->request->post("data.translations"));
        }

        $this->view->set("locale", $locale);
        $query = $this->request->get("data.translation.original");
        $this->view->set("searchQuery", $query);

        $matchingTranslations = array();
        try {
            $translations = $this->polyglot->getTranslations($localeCode);

            foreach ($translations as $translation) {
                $match = preg_quote($query, "/");
                if (preg_match("/$match/i", htmlentities($translation->getOriginal()))) {
                    $matchingTranslations[] = $translation;
                }
            }
        } catch (Exception $e) { }

        $this->view->set("translations", $matchingTranslations);

        $this->render("searchEdit");
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
            $this->view->set("destinationLink", $tanslator->getForwardUrl());
        } catch(Exception $e) {
            $this->view->set("error", $e->getMessage());
        }

        $this->view->set("originalId", $id);
        $this->render("duplicating");
    }

    public function scanProject()
    {
        $this->view->set("output", $this->runCLIExtractCommand());

        if ($this->request->hasGet("backToLocale")) {
            $localeCode = $this->request->get("backToLocale");
            $this->view->set("returnLocale", $this->polyglot->getLocaleByCode($localeCode));
        }

        $this->render("scan");
    }

    private function runCLIExtractCommand()
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->add(new I18nCommand());

        $input = new ArrayInput(array(
           'command' => 'i18n',
           'type' => 'extract',
        ));

        $output = new BufferedOutput();
        $application->run($input, $output);

        return $output->fetch();
    }
}
