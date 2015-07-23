<?php

namespace Polyglot\Plugin;

use Polyglot\Plugin\Adaptor;

class Polyglot {

    protected $wpAdaptor;

    function __construct()
    {
        $this->wpAdaptor = new WordpressAdaptor();
    }

    public function register()
    {
        $this->wpAdaptor->addActions();
    }

}
