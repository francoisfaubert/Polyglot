<?php
namespace Polyglot\Plugin\Db;


use Polyglot\Plugin\TranslationEntity;


use WP_Post;
use StdClass;
use Strata\Strata;
use Strata\Logger\Logger;

class Query {

    const WP_UNIQUE_KEY = "polyglot-plugin";
    const DB_VERSION = "0.1.0";

    protected $logger;
    private $executionStart = 0;

    function __construct()
    {
        $this->configureLogger();
    }

    public function createTranslationEntity($object)
    {
        $app = Strata::app();

        $entity = new TranslationEntity();
        $entity->obj_id = $object->ID;
        $entity->obj_kind = get_class($object);
        $entity->obj_type = $object->post_type;
        $entity->translation_locale = $app->i18n->getDefaultLocale()->getCode();
        $entity->translation_of = null;

        return $entity;
    }

    public function createTable()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . 'polyglot';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $tableName (
            polyglot_ID mediumint(9) NOT NULL AUTO_INCREMENT,
            obj_kind varchar(10) NOT NULL,
            obj_type tinytext NOT NULL,
            obj_id mediumint(9) NOT NULL,
            translation_of mediumint(9) NOT NULL,
            translation_locale varchar(10),
            UNIQUE KEY polyglot_ID (polyglot_ID)
        ) $charset;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);

        $this->logger->log("Created or updated the Polyglot table.", "[Plugins::Polyglot]");

        add_option('polyglot_db_version', self::DB_VERSION);
    }

    public function unlinkTranslationFor($objectId, $objectKind)
    {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . 'polyglot', array(
            "obj_id" => $objectId,
            "obj_kind" => $objectKind
        ));
    }

    public function findAllTranlationsOfOriginal($object)
    {
        return $this->findAllTranlationsOfOriginalId($object->ID, get_class($object));
    }

    public function findAllTranlationsOfOriginalId($id, $kind = "WP_Post")
    {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT {$wpdb->prefix}polyglot.*,
                {$wpdb->prefix}posts.post_name
            FROM {$wpdb->prefix}polyglot
                LEFT JOIN {$wpdb->prefix}posts on {$wpdb->prefix}posts.ID = {$wpdb->prefix}polyglot.obj_id
            WHERE translation_of = %s
                AND obj_kind = %s
            ORDER BY polyglot_ID ASC",
            $id,
            $kind
        );

        $this->logQueryStart();
        $result = array();
        foreach ($wpdb->get_results($query) as $row) {
            $result[] = new TranslationEntity($row);
        }
        $this->logQueryCompletion($wpdb->last_query);

        return $result;
    }

    // public function translationTree(WP_Post $post)
    // {
    //     global $wpdb;

    //     $query = $wpdb->prepare("
    //         SELECT *
    //         FROM {$wpdb->prefix}polyglot
    //             LEFT JOIN {$wpdb->prefix}posts on {$wpdb->prefix}posts.ID = {$wpdb->prefix}polyglot.obj_id
    //         WHERE translation_of = %s
    //             AND obj_kind = %s
    //         ORDER BY polyglot_ID ASC",
    //         $post->ID,
    //         get_class($post)
    //     );

    //     $this->logQueryStart();
    //     $result = $wpdb->get_results($query);
    //     $this->logQueryCompletion($wpdb->last_query);

    //     return $result;
    // }

    /**
     * Queries for the translation details of an object.
     * @param  mixed $object
     * @return TranslationEntity
     */
    public function findDetails($object)
    {
        global $wpdb;
        $query = $wpdb->prepare("
            SELECT *
            FROM {$wpdb->prefix}polyglot
                WHERE obj_id = %s
                AND obj_kind = %s
            ORDER BY polyglot_ID ASC
            LIMIT 1",
            $object->ID,
            get_class($object)
        );

        $this->logQueryStart();
        $result = $wpdb->get_row($query);
        $this->logQueryCompletion($wpdb->last_query);

        if (is_null($result) && $this->isInDefaultLocale($object)) {
            return $this->createTranslationEntity($object);
        }

        return new TranslationEntity($result);
    }


    /**
     * Fetches all the known translation details of an object's original parent.
     * Best for cases where you have a translation and need to build the list of
     * all locale possibilities.
     * @param  mixed $object
     * @return TranslationEntity
     */
    public function findOriginalTranslationDetails($object)
    {
        global $wpdb;
        $app = Strata::app();

        $query = $wpdb->prepare("
            SELECT *
            FROM {$wpdb->prefix}polyglot
            WHERE translation_of =
                (
                    SELECT translation_of
                    FROM {$wpdb->prefix}polyglot
                        WHERE obj_id = %s
                        AND obj_kind = %s
                )",
            $object->ID,
            get_class($object)
        );

        $this->logQueryStart();
        $result = array();
        foreach ($wpdb->get_results($query) as $row) {
            $result[] = new TranslationEntity($row);
        }
        $this->logQueryCompletion($wpdb->last_query);

        return $result;
    }


    // public function findOriginal($mixed)
    // {
    //     global $wpdb;
    //     $app = Strata::app();

    //     $query = $wpdb->prepare("
    //         SELECT translation_of
    //         FROM {$wpdb->prefix}polyglot
    //             WHERE obj_id = %s
    //             AND obj_kind = %s
    //         ORDER BY polyglot_ID ASC",
    //         $mixed->ID,
    //         get_class($mixed)
    //     );

    //     $this->logQueryStart();
    //     $originalPost = $wpdb->get_var($query);
    //     $this->logQueryCompletion($wpdb->last_query);

    //     return $originalPost;
    // }

    // public function isOriginal($obj)
    // {
    //     $original = $this->findOriginal($obj);
    //     return is_null($original) && $this->isInDefaultLocale($obj);
    // }



    // public function findAllIdsOfLocale($localeCode, $kind = "WP_Post")
    // {
    //      global $wpdb;

    //     $query = $wpdb->prepare("
    //         SELECT obj_id
    //         FROM {$wpdb->prefix}polyglot
    //             WHERE obj_kind = %s
    //             AND translation_locale = %s
    //         ORDER BY polyglot_ID ASC",
    //         $kind,
    //         $localeCode
    //     );

    //     $this->logQueryStart();
    //     $results = $wpdb->get_row($query);
    //     $this->logQueryCompletion($wpdb->last_query);

    //     return $results;
    // }

    public function addPostTranslation($originalId, $originalType, $originalKind, $targetLocale)
    {
        global $wpdb;
        $originalPostTitle = get_the_title($originalId);
        $translationTitle = $originalPostTitle . " ($targetLocale)";

        if ($originalKind == "WP_Post") {
            $associationId = wp_insert_post(array(
                "post_title" => $translationTitle,
                "post_type" => $originalType
            ));
        } else {
            throw new Exception("We don't know how to duplicate $originalKind.");
        }

        if ((int)$associationId > 0) {

            $row = array(
                'obj_kind' => $originalKind,
                'obj_type' => $originalType,
                'obj_id'    => $associationId,
                'translation_of' => $originalId,
                'translation_locale' => $targetLocale
            );

            if ($wpdb->insert("{$wpdb->prefix}polyglot", $row)) {
                return $associationId;
            }

            throw new Exception("Could not save translation data.");
        }
        throw new Exception("Could not duplicate post.");
    }

    private function logQueryStart()
    {
        $this->executionStart = microtime(true);
    }

    private function logQueryCompletion($sql)
    {
        $executionTime = microtime(true) - $this->executionStart;
        $timer = sprintf(" (Done in %s seconds)", round($executionTime, 4));
        $oneLine = preg_replace('/\s+/', ' ', trim($sql));
        $this->logger->log($oneLine . $timer, "[Plugins:Polyglot:Query]");
    }

    private function isInDefaultLocale($obj)
    {
        // The post had no parent version, however we must
        // confirm the queried post is not the default locale.
        // If it is, then it's normal that there are not parent posts found
        // because original posts aren't listed in the polyglot table.
        $app = Strata::app();
        return $this->findPostLocale($obj) === $app->i18n->getDefaultLocale()->getCode();
    }

    public function findPostLocale(WP_Post $translatedPost)
    {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT translation_locale
            FROM {$wpdb->prefix}polyglot
                WHERE obj_id = %s
                AND obj_kind = %s
            ORDER BY polyglot_ID ASC",
            $translatedPost->ID,
            get_class($translatedPost)
        );

        $this->logQueryStart();
        $locale = $wpdb->get_var($query);
        $this->logQueryCompletion($wpdb->last_query);

        if (is_null($locale)) {
            $app = Strata::app();
            return $app->i18n->getDefaultLocale()->getCode();
        }

        return $locale;
    }

    private function configureLogger()
    {
        $this->logger = new Logger();
        $this->logger->color = "\e[0;35m";
    }
}
