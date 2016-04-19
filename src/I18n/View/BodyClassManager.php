<?php

namespace Polyglot\I18n\View;

use Strata\Strata;

class BodyClassManager {

    private $classes;
    private $currentLocale;

    public function __construct($classes)
    {
        $this->classes = $classes;
        $this->currentLocale = Strata::i18n()->getCurrentLocale();
    }

    // On a secondary locale, if the current page is a translation
    // of the page on front, then replace the classes of the body correctly
    public function localize()
    {
        if (!$this->currentLocale->isDefault()) {
            if ($this->isLocalizedBlogPage()) {
                $this->localizeBlogClasses();
            }
        }

        return $this->classes;
    }

    protected function isLocalizedBlogPage()
    {
        $pageOnFront = $this->currentLocale->getTranslatedPost(get_option('page_on_front'));

        return $pageOnFront && $pageOnFront->ID == get_the_ID();
    }

    protected function localizeBlogClasses()
    {
        $this->removePageKeys();
        array_splice($this->classes, 0, 0, "blog");
    }

    protected function removePageKeys()
    {
        foreach ($this->classes as $idx => $class) {
            if (preg_match("/^page(\-.*)?/", $class)) {
                unset($this->classes[$idx]);
            }
        }
    }
}
