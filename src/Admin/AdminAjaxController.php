<?php
namespace Polyglot\Admin;

use Polyglot\Plugin\Polyglot;
use Polyglot\Plugin\Locale;

use Exception;

class AdminAjaxController extends BaseController {

    private $polyglot;
    private $locale;
    protected $viewPathExtra = array('ajax');

    function __construct()
    {
        $this->polyglot = new Polyglot();
        $this->locale = new Locale();

        $this->set("polyglot", $this->polyglot);
        $this->set("locale", $this->locale);
    }

    public function loadSummary()
    {
        $this->polyglot->buildFileAssociation();
        $this->render("summary");
    }

    public function createLocale()
    {
        $this->render("editlocale");
    }

    public function editLocale()
    {
        $this->locale = $this->polyglot->getLocale($this->getPost("localeCode"));
        $this->render("editlocale");
    }

    public function saveLocale()
    {
        try {
            $this->validateAndSaveLocale();
            $this->set("success", true);
        } catch (Exception $e) {
            $this->set("exception", $e->getMessage());
        }

        $this->render("editlocale");
    }

    public function gettextLookup()
    {
        $this->render("gettextlookup");

    }

    protected function validateAndSaveLocale()
    {
        if ($this->isPost('submit')) {
            $this->locale->create($this->getPost('locale'));
            $this->polyglot->saveLocale($this->locale, $this->adaptor);
        }
    }

}
