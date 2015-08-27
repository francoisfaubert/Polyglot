<?php
namespace Polyglot\Plugin\Db;

use Polyglot\Plugin\TranslationEntity\PostTranslationEntity;
use Polyglot\Plugin\TranslationEntity\TermTranslationEntity;
use Polyglot\Plugin\TranslationEntity\TranslationEntity;
use Polyglot\Plugin\TranslationTree;
use Polyglot\Plugin\Db\Cache;
use Polyglot\Plugin\Db\Logger;

use WP_Post;
use Strata\Strata;
use Exception;

class Query {

    const WP_UNIQUE_KEY = "polyglot-plugin";
    const DB_VERSION = "0.1.0";

    protected $logger;
    private $cache;

    function __construct()
    {
        $this->logger = new Logger();
        $this->cache = new Cache();
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

    public function addTranslation($originalId, $originalType, $originalKind, $targetLocale)
    {
        global $wpdb;

        if ($originalKind == "WP_Post") {
            $originalTitle = get_the_title($originalId);
            $translationTitle = $originalTitle . " ($targetLocale)";
            $associationId = wp_insert_post(array(
                "post_title" => $translationTitle,
                "post_type" => $originalType
            ));
            $cahePrefix = "get_post";

        } elseif ($originalKind == "Term") {
            $term = get_term_by("id", $originalId, $originalType);
            $translationTitle = $term->name . " ($targetLocale)";
            $result = wp_insert_term( $translationTitle, $originalType);

            if (is_a($result, 'WP_Error')) {
                $error = array_values($result->errors);
                throw new Exception($error[0][0]);
            }

            $cahePrefix = "get_term";
            $associationId = $result['term_id'];
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

                $this->cache->remove("$cahePrefix\_$associationId\_$originalType");

                return $associationId;
            }
            throw new Exception("Could not save translation data.");
        }
        throw new Exception("Could not duplicate post.");
    }

    public function runCachableQuery($query, $queryType = "get_results")
    {
        global $wpdb;

        $this->logger->logQueryStart();

        if ($this->cache->has($query . $queryType)) {
            $queryResults = $this->cache->get($query . $queryType);
        } else {
            $queryResults = $wpdb->{$queryType}($query);
            $lastQuery = $wpdb->last_query;

            $this->cache->set($query . $queryType, $queryResults);
            $this->logger->logQueryCompletion($lastQuery);
        }

        return $queryResults;
    }

    public function findTranlationsOf(TranslationEntity $object)
    {
        return $this->findTranlationsOfId($object->getObjectId(), $object->getObjectKind());
    }

    public function findTranlationsOfId($id, $kind = "WP_Post")
    {
        global $wpdb;

        $results = $this->runCachableQuery($wpdb->prepare("
            SELECT *
            FROM {$wpdb->prefix}polyglot
            WHERE translation_of = %s
            AND obj_kind = %s",
            $id,
            $kind
        ));

        if (!is_null($results) && count($results)) {
            return new TranslationTree($this->rowsToEntities($results));
        }
    }

    /**
     * Returns a cached reference of a post.
     * @param  int $id
     * @return array       An array of PostTranslationEntities
     */
    public function findPostById($id)
    {
        $this->logger->logQueryStart();

        $query = "get_post_$id";
        $queryResults = null;

        if (!$this->cache->has($query)) {
            $post = get_post($id);
            if ($post) {
                $queryResults = new PostTranslationEntity($post);
                $this->cache->set($query, $queryResults);
            }
        }

        return $this->cache->get($query);
    }

    /**
     * Returns a cached reference of a loaded term.
     * @param  int $id
     * @param  string $type The type of the term (ex: category)
     * @return array       An array of TermTranslationEntities
     */
    public function findTermById($id, $type)
    {
        $this->logger->logQueryStart();

        $query = "get_term_$id\_$type";

        if ($this->cache->has($query)) {
            $queryResults = $this->cache->get($query);
        } else {
            $queryResults = new TermTranslationEntity(get_term($id, $type));
            $this->cache->set($query, $queryResults);
        }

        return $queryResults;
    }

    /**
     * Queries for the translation details of an object.
     * @param  mixed $object
     * @return TranslationEntity
     */
    public function findDetails(TranslationEntity $object)
    {
        return $this->findDetailsById($object->getObjectId(), $object->getObjectKind());
    }

    /**
     * Queries for the translation details of an object.
     * @param  int $id
     * @param string $kind The type of the object (default "WP_Post")
     * @return TranslationEntity
     */
    public function findDetailsById($id, $kind = "WP_Post")
    {
        global $wpdb;

        $result = $this->runCachableQuery($wpdb->prepare("
            SELECT *
            FROM {$wpdb->prefix}polyglot
                WHERE obj_id = %s
                AND obj_kind = %s
            LIMIT 1",
            $id,
            $kind
        ), "get_row");

        if (!is_null($result) && $result != '') {
            return TranslationEntity::factory($result);
        }
    }

    public function findDetailsByIds(array $ids, $kind = "WP_Post")
    {
        global $wpdb;

        $results = $this->runCachableQuery($wpdb->prepare("
            SELECT *
            FROM {$wpdb->prefix}polyglot
                WHERE obj_id IN (%s)
                AND obj_kind = %s",
            $ids,
            $kind
        ));

        if (!is_null($results)) {
            return $this->rowsToEntities($results);
        }
    }


    /**
     * Fetches all the known translation details of an object's original parent.
     * Best for cases where you have a translation and need to build the list of
     * all locale possibilities.
     * @param  mixed $object
     * @return TranslationEntity
     */
    public function findOriginalTranslationsOf($object)
    {
        return $this->findTranslationsOfId($object->getObjectId(), $object->getObjectKind());
    }

    /**
     * Fetches all the known translation details of an object's original parent.
     * Best for cases where you have a translation and need to build the list of
     * all locale possibilities.
     * @param  int $id
     * @param string $kind The type of the object (default "WP_Post")
     * @return TranslationEntity
     */
    public function findOriginalTranslationsOfId($id, $kind = "WP_Post")
    {
        global $wpdb;

        $results = $this->runCachableQuery($wpdb->prepare("
            SELECT *
            FROM {$wpdb->prefix}polyglot
            WHERE translation_of =
                (
                    SELECT translation_of
                    FROM {$wpdb->prefix}polyglot
                        WHERE obj_id = %s
                        AND obj_kind = %s
                )",
            $id,
            $kind
        ));

        if (!is_null($results) && count($results)) {
            return new TranslationTree($this->rowsToEntities($results));
        }
    }



    /**
     * @todo This is ugly, plz improve
     */
    public function findTranslationIdsOf($locale, $kind = "WP_Post")
    {
        global $wpdb;

        $results = $this->runCachableQuery($wpdb->prepare("
            SELECT obj_id
            FROM {$wpdb->prefix}polyglot
            WHERE translation_locale = %s
            AND  obj_kind = %s",
            $locale->getCode(),
            $kind
        ));

        if (!is_null($results) && count($results)) {
            return $results;
        }
    }


    public function findTranslationIdsNotInLocale($locale, $kind = "WP_Post")
    {
        global $wpdb;

        $results = $this->runCachableQuery($wpdb->prepare("
            SELECT * FROM
                ( SELECT obj_id
                    FROM {$wpdb->prefix}polyglot
                    WHERE translation_locale != %s
                    AND  obj_kind = %s) as tbl1,
                (SELECT translation_of as obj_id
                    FROM {$wpdb->prefix}polyglot
                    WHERE translation_locale = %s
                    AND  obj_kind = %s

                    ) as tbl2",
            $locale->getCode(),
            $kind,
            $locale->getCode(),
            $kind
        ));

        if (!is_null($results) && count($results)) {
            return $results;
        }
    }


    /**
     * @todo This is horrible, plz improve
     */
    public function listTranslatedIds($kind = "WP_Post")
    {
        global $wpdb;

        $results = $this->runCachableQuery($wpdb->prepare("
            SELECT DISTINCT translation_of, obj_id
            FROM {$wpdb->prefix}polyglot
            WHERE obj_kind = %s",
            $kind
        ));

        if (!is_null($results) && count($results)) {
            return $results;
        }
    }


    /**
     * Returns the original locale of a translated object.
     * @param  mixed $translatedObj
     * @return Locale
     */
    public function findObjectLocale($translatedObj)
    {
        global $polyglot;

        $details = $this->findDetails($translatedObj);

        if (is_null($details)) {
            return $polyglot->getDefaultLocale();
        }

        return $polyglot->getLocaleByCode($details->translation_locale);
    }

    /**
     * Returns the default homepage ID
     * @return Integer The page ID or -1 if there are none
     */
    public function getDefaultHomepageId()
    {
        if ($this->hasHomePage()) {
            return $this->pageOnFront();
        }

        return -1;
    }

    /**
     * Returns a cached reference to Wordpress' show_on_front option
     * @see https://codex.wordpress.org/Option_Reference
     * @return Boolean True if the current app has a page for home page
     */
    private function hasHomePage()
    {
        $query = "get_option_show_on_front";

        if ($this->cache->has($query)) {
            $showOnFront = $this->cache->get($query);
        } else {
            $showOnFront = get_option('show_on_front');
            $this->cache->set($query, $showOnFront);
        }

        return $showOnFront == "page";
    }

    /**
     * Returns a cached reference to Wordpress' page_on_front option
     * @see https://codex.wordpress.org/Option_Reference
     * @return Integer The page ID
     */
    private function pageOnFront()
    {
        $query = "get_option_page_on_front";

        if ($this->cache->has($query)) {
            $pageOnFront = $this->cache->get($query);
        } else {
            $pageOnFront = (int)get_option('page_on_front');
            $this->cache->set($query, $pageOnFront);
        }

        return $pageOnFront;
    }

    /**
     * Converts DB rows into translation entities
     * @param  array $rows
     * @return array
     */
    protected function rowsToEntities($rows)
    {
        $results = array();
        foreach ($rows as $row) {
            $results[] = TranslationEntity::factory($row);
        }
        return $results;
    }
}
