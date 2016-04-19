<?php
namespace Polyglot\Admin\Controller;

use Strata\Controller\Request;

/**
 * Listens for action and filters registered to Wordpress.
 * All callbacks are sent here to stay in the MVC environment.
 */
class CallbackController extends BaseController {

    /**
     * Callback to the metabox registration hook.
     * @see Polyglot\Plugin\Adaptor\WordpressAdaptor
     */
    public function addMetaBox()
    {
        if (!is_null(get_post_type())) {
            $this->registerLocalizationMetabox();
            $this->preventTranslatedMetaBoxes();
        }
    }

    /**
     * Callback to the column registration hook.
     * @see Polyglot\Plugin\Adaptor\WordpressAdaptor
     */
    public function addLocalizationColumn($columns)
    {
        $columns['polyglot_locales'] = __('i18n', 'polyglot');
        return $columns;
    }

    /**
     * Callback to the post/page edit.php column rendering.
     * @see Polyglot\Plugin\Adaptor\WordpressAdaptor
     */
    public function renderLocalizationColumn($column, $postId)
    {
        if ($column === "polyglot_locales" && get_post_status() != "trash") {

            $this->view->set("obj_id", $postId);
            $this->view->set("obj_type", get_post_type());
            $this->view->set("objKind", "WP_Post");
            $this->view->set("contextualPostType", $this->request->hasGet("post_type") ? $this->request->get("post_type") : "post");

            $this->render("buttonTranslator");
        }
    }

    /**
     * Callback to the taxonomy listing column rendering
     * @see Polyglot\Plugin\Adaptor\WordpressAdaptor
     */
    public function renderTaxonomyLocalizationColumn($out, $column, $termId)
    {
        if ($column === "polyglot_locales") {
            $this->view->set("contextualPostType", $this->request->hasGet("post_type") ? $this->request->get("post_type") : "post");
            $this->view->set("obj_id", $termId);
            $this->view->set("obj_type", $this->request->get("taxonomy"));
            $this->view->set("objKind", "Term");
            $this->render("buttonTranslator");
        }
    }

    /**
     * Callback that renders the metabox used to display a summary
     * of the post/page translations.
     */
    public function renderPostMetabox()
    {
        if (get_post_status() == "auto-draft") {
            $this->view->set("invalidStatus", true);
        } else {
            $this->view->set("obj_id", get_the_ID());
            $this->view->set("obj_type", get_post_type());
            $this->view->set("objKind", "WP_Post");
        }
        $this->render("metaboxTranslator");
    }

    /**
     * Callback that renders the metabox used to display a summary on the
     * taxonomies' edit page.
     */
    public function addTaxonomyLocaleSelect($taxonomy)
    {
        $configuration = $this->i18n->getConfiguration();

        if (!is_null($taxonomy) && $configuration->isTaxonomyEnabled($taxonomy->taxonomy)) {
            $this->view->set("obj_id", $taxonomy->term_id);
            $this->view->set("obj_type", $taxonomy->taxonomy);
            $this->view->set("objKind", "Term");
            $this->render("metaboxTranslator");
        }
    }


    /**
     * Registers the post/page sidebar metabox for switching
     * localizations.
     * @return bool
     */
    private function registerLocalizationMetabox()
    {
        return add_meta_box(
            "polyglot-localization-metabox",
            __('Localization', "polyglot"),
            array($this, 'renderPostMetabox'),
            get_post_type(),
            'side',
            'high'
        );
    }

    /**
     * Prevents unsupported features from appearing on
     * translated posts. This allows updates on the original
     * localization to carry over to the translations without fear
     * of erasing something.
     */
    private function preventTranslatedMetaBoxes()
    {
        $locale = $this->i18n->getCurrentLocale();
        if ($locale && !$locale->isDefault()) {
            remove_meta_box("formatdiv", "post", "side");
            remove_meta_box("categorydiv", "post", "side");
            remove_meta_box("tagsdiv-post_tag", "post", "side");
            remove_meta_box("formatdiv", "page", "side");
            remove_meta_box("categorydiv", "page", "side");
            remove_meta_box("pageparentdiv", "page", "side");
            remove_meta_box("tagsdiv-post_tag", "page", "side");
        }
    }
}
