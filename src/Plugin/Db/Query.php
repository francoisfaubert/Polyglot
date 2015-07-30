<?php
namespace Polyglot\Plugin\Db;

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

    private function configureLogger()
    {
        $this->logger = new Logger();
        $this->logger->color = "\e[0;35m";
    }

    public function createTable()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . 'polyglot';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $tableName (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            obj_kind varchar(10) NOT NULL,
            obj_type tinytext NOT NULL,
            obj_id mediumint(9) NOT NULL,
            translation_of mediumint(9) NOT NULL,
            translation_locale varchar(10),
            UNIQUE KEY id (id)
        ) $charset;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);

        $this->logger->log("Created or updated the Polyglot table.", "[Plugins::Polyglot]");

        add_option('polyglot_db_version', self::DB_VERSION);
    }

    public function findAllTranlationsOfOriginal($object)
    {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT *
            FROM {$wpdb->prefix}polyglot
                WHERE translation_of = %s
                AND obj_kind = %s
            ORDER BY id ASC",
            $object->ID,
            get_class($object)
        );


        $this->logQueryStart();
        $result = $wpdb->get_results($query);
        $this->logQueryCompletion($wpdb->last_query);

        return $result;
    }

    public function findDetails($object)
    {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT *
            FROM {$wpdb->prefix}polyglot
                WHERE obj_id = %s
                AND obj_kind = %s
            ORDER BY id ASC
            LIMIT 1",
            $object->ID,
            get_class($object)
        );

        $this->logQueryStart();
        $result = $wpdb->get_row($query);
        $this->logQueryCompletion($wpdb->last_query);

        if (is_null($result) && $this->isInDefaultLocale($object)) {
            return $this->createFakeRow($object);
        }

        return $result;
    }

    public function findOriginal($translatedPost)
    {
        global $wpdb;
        $app = Strata::app();

        $query = $wpdb->prepare("
            SELECT translation_of
            FROM {$wpdb->prefix}polyglot
                WHERE obj_id = %s
                AND obj_kind = %s
            ORDER BY id ASC",
            $translatedPost->ID,
            get_class($translatedPost)
        );

        $this->logQueryStart();
        $originalPost = get_post($wpdb->get_var($query));
        $this->logQueryCompletion($wpdb->last_query);

        return $originalPost;
    }

    public function isOriginal($obj)
    {
        $original = $this->findOriginal($obj);
        return is_null($original) && $this->isInDefaultLocale($obj);
    }

    public function findPostLocale($translatedPost)
    {
        global $wpdb;

        $query = $wpdb->prepare("
            SELECT translation_locale
            FROM {$wpdb->prefix}polyglot
                WHERE obj_id = %s
                AND obj_kind = %s
            ORDER BY id ASC",
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

    public function addPostTranslation($originalId, $originalType, $originalKind, $targetLocale)
    {
        global $wpdb;
        $originalPostTitle = get_the_title($originalId);
        $translationTitle = $originalPostTitle . " ($targetLocale)";

        $associationId = wp_insert_post(array("post_title" => $translationTitle));

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

    private function createFakeRow($object)
    {
        $app = Strata::app();
        $row = new \StdClass();
        $row->obj_id = $object->ID;
        $row->obj_kind = get_class($object);
        $row->obj_type = $object->post_type;
        $row->translation_locale = $app->i18n->getDefaultLocale()->getCode();
        $row->translation_of = null;
        return $row;
    }
}
