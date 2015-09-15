<?php
namespace Polyglot\Plugin\Db;

use Polyglot\Plugin\TranslationEntity\TranslationEntity;

/**
 * This cache is meant to store entities on each rendering pass.
 * It is not intended to dump the results in wp_cache or as a transient.
 * However, using transients will likely be a long term goal on some of the entities
 * stored here
 */
class Cache  {
    /**
     * The entity map cache sorts records by obj_kind and
     * translation_of, the two most frequently used sort columns.
     * This makes cache lookups quick and we can mimic simple SQL
     * behaviour transparently.
     * @var array
     */
    private $entitiesMap = array();
    private $entitiesIds = array();

    public function addEntity(TranslationEntity $entity)
    {
        if (!$this->idWasCached($entity->polyglot_ID)) {

            if (!array_key_exists($entity->obj_kind, $this->entitiesMap)) {
                $this->entitiesMap[$entity->obj_kind] = array();
            }

            if (!array_key_exists((int)$entity->translation_of, $this->entitiesMap[$entity->obj_kind])) {
                $this->entitiesMap[$entity->obj_kind][(int)$entity->translation_of] = array();
            }

            $this->entitiesMap[$entity->obj_kind][(int)$entity->translation_of][] = $entity;
            $this->entitiesIds[(int)$entity->polyglot_ID] = $entity;
        }
    }

    public function getNumberOfCachedRecords()
    {
        return count($this->entitiesMap);
    }

    public function getByKind($kind)
    {
        if (array_key_exists($kind, $this->entitiesMap)) {
            return $this->entitiesMap[$kind];
        }

        return array();
    }

    public function findTranlationsOf($id, $kind)
    {
        $byKind = $this->getByKind($kind);

        if (array_key_exists((int)$id, $byKind)) {
            return $byKind[(int)$id];
        }

        return array();
    }

    public function findDetailsById($id)
    {
        if ($this->idWasCached($id)) {
            return $this->entitiesIds[(int)$id];
        }
    }

    public function findByOriginalObject($objId, $objKind)
    {
        $byKind = $this->getByKind($objKind);

        // We'll have to keep on eye on how this
        // behaves in real life
        foreach ($byKind as $translationsOfId) {
            foreach ($translationsOfId as $entity) {
                if ((int)$entity->obj_id === (int)$objId) {
                    return $entity;
                }
            }
        }
    }

    public function idWasCached($id)
    {
        return array_key_exists((int)$id, $this->entitiesMap);
    }
}
