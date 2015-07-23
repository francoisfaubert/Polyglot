<?php
namespace Polyglot\Admin;

use Polyglot\Plugin\Adaptor\WordpressAdaptor;

class BaseController {

    protected $adaptor;
    protected $viewVars = array();
    protected $viewPathExtra = array();

    public function contextualize($adaptor)
    {
        $this->adaptor = $adaptor;
    }

    public function set($name, $value)
    {
        $this->viewVars[$name] = $value;
    }

    public function autoroute()
    {
        if (array_key_exists('polyglot_ajax_action', $_POST)) {
            if (method_exists($this, $_POST['polyglot_ajax_action'])) {
                call_user_func(array($this, $_POST['polyglot_ajax_action']));
            }
        }
    }

    protected function render($filename, $variables = array(), $extension = '.php')
    {
        ob_start();
        extract($this->viewVars);

        $path = implode(DIRECTORY_SEPARATOR, array_merge(array($this->adaptor->getAdminViewPath()), $this->viewPathExtra));

        include($path . DIRECTORY_SEPARATOR . $filename . $extension);
        echo ob_get_clean();

        if (is_admin() && defined('DOING_AJAX') && DOING_AJAX) {
            exit();
        }
    }

    protected function isPost($key)
    {
        return array_key_exists($key, $_POST);
    }

    protected function getPost($key)
    {
        return $_POST[$key];
    }

    protected function isGet($key)
    {
        return array_key_exists($key, $_GET);
    }

    protected function getGet($key)
    {
        return $_GET[$key];
    }
}
