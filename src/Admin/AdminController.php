<?php
namespace Polyglot\Admin;

use Polyglot\Plugin\Polyglot;

class AdminController extends BaseController {

    public function index()
    {
        if ($this->isGet('loopup')) {
            $this->lookup();

        } else {
            $this->set("polyglot", new Polyglot());
            $this->render('index');
        }
    }


    public function lookup()
    {
        echo "sup";

    }

}
