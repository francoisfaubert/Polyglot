<?php

namespace Polyglot\Plugin;

class Cache {
    protected $locales;
    protected $enabledLocales;
    protected $translatables;

    public function pull()
    {
        $configuration = (array)get_option('polyglot_settings', $this->getDefaultConfiguration());
        foreach ($configuration as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function push()
    {
        $configuration = array(
            'locales' => $this->locales,
            'enabledLocales' => $this->enabledLocales,
            'translatables' => $this->translatables,
        );

        update_option('polyglot_settings', $configuration);
        return true; // it would sure be nice if WP only returned false on failure.
    }

    public function getEnabledLocales()
    {
        $list = $this->getLocaleList();
        $return = array();

        foreach ($list as $id => $locale) {
            if ($this->isEnabled($locale)) {
                $return[] = $locale;
            }
        }

        return $return;
    }

    public function getDisabledLocales()
    {
        $list = $this->getLocaleList();
        $return = array();

        foreach ($list as $id => $locale) {
            if (!$this->isEnabled($locale)) {
                $return[] = $locale;
            }
        }

        return $return;
    }

    public function isEnabled(Locale $locale)
    {
        $enabled = (array)$this->enabledLocales;
        return in_array($locale->getId(), $enabled);
    }

    public function getLocaleList()
    {
        return (array)$this->locales;
    }

    public function localeIdExists($id)
    {
        return array_key_exists($id, $this->getLocaleList());
    }

    public function localeExists($locale)
    {
        foreach ($this->getLocaleList() as $id => $test) {
            if ($test->getCode() === $locale->getCode() && $test->getId() === $locale->getId()) {
                return true;
            }
        }
        return false;
    }

    public function findByCode($code)
    {
        foreach ($this->getLocaleList() as $id => $locale) {
            if ($locale->getCode() === $code) {
                return $locale;
            }
        }
    }

    public function isDuplicateCode(Locale $locale)
    {
        return !is_null($this->findByCode($locale->getCode())) && !$this->localeExists($locale);
    }

    public function getLocaleById($id)
    {
        if ($this->localeIdExists($id)) {
            return $this->locales[$id];
        }
    }

    public function saveLocale(Locale $locale)
    {
        $backup = $this->locales;

        // Set newly added languages to disabled to give some
        // time for the admins to enter the strings.
        if (!$this->localeIdExists($locale->getId())) {
            $this->disabledLocales[] = $locale->getId();
        }

        $this->locales[$locale->getId()] = $locale;

        if (!$this->push()) {
            // revert
            $this->locales = $backup;
            return false;
        }

        return true;
    }

    protected function getDefaultConfiguration()
    {
        return array(
            "locales" => array(),
            "enabledLocales" => array(),
            "translatables" => array(),
        );
    }

}
