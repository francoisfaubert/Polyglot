<?php

namespace Polyglot\Plugin;

class Locale {

    protected $defaultName;
    protected $code;
    protected $isDefault;
    protected $uniqueId;

    function __construct()
    {
        $this->create();
    }

    public function getDefaultName()
    {
        return $this->defaultName;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function isDefault()
    {
        return (bool)$this->isDefault;
    }

    public function isNew()
    {
        // @todo : this doesn't work if I am supposed to support
        // externally generated files.
        return !($this->hasPo() && $this->hasMo());
    }

    public function getId()
    {
        return $this->uniqueId;
    }

    public function create($config = array())
    {
        $this->createEmpty();
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function hasMo()
    {
        return file_exists($this->getMoPath());
    }

    public function getMoPath()
    {
        return WP_LANG_DIR . $this->getCode() . '.mo';
    }

    public function hasPo()
    {
        return file_exists($this->getPoPath());
    }

    public function getPoPath()
    {
        return WP_LANG_DIR . DIRECTORY_SEPARATOR . str_replace("-", "_", $this->getCode()) . '.po';
    }

    public function isValid()
    {
        return $this->getDefaultName() != "" && preg_match("/[a-z]{2}-[A-Z]{2}/", $this->getCode());
    }

    protected function createEmpty()
    {
        $this->uniqueId = uniqid("id-");
        $this->defaultName = "";
        $this->code = "";
        $this->isDefault = false;
    }
}
