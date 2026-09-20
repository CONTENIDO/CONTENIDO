<?php

declare(strict_types=1);

/**
 * This file contains the meta type service class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Murat Purç <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * @since CONTENIDO 4.10.2
 * @package    Core
 * @subpackage Backend_Service
 *
 * TODO Use Dependency Injection Container for the service
 */
class cMetaTypeService
{
    private static ?array $metaTypes = null;

    private CApiMetaTypeCollection $metaTypeCollection;

    public function __construct()
    {
        $this->metaTypeCollection = new cApiMetaTypeCollection();
    }

    /**
     * Returns all meta type items.
     * Cashed the first call, and returns the result from cache on further calls.
     *
     * @param bool $reload Flag to reload items from the database.
     * @return cApiMetaType[]
     */
    public function getAll(bool $reload = false): array
    {
        if (self::$metaTypes === null || $reload) {
            try {
                self::$metaTypes = $this->metaTypeCollection->fetchAll();
            } catch (cDbException|cException $e) {
                cError($e->getMessage());
                self::$metaTypes = [];
            }
        }

        return self::$metaTypes;
    }

    /**
     * Returns a meta type item by its meta type.
     *
     * @param string $metaTypeStr The meta type, e. g. 'robots', 'keywords', etc.
     */
    public function getByMetaType(string $metaTypeStr): ?cApiMetaType
    {
        return array_find($this->getAll(), fn(cApiMetaType $item) => $item->get('metatype') === $metaTypeStr);
    }

    public function deleteById(int $id): bool
    {
        try {
            $result = $this->metaTypeCollection->delete($id);
            if ($result) {
                self::$metaTypes = array_filter(self::$metaTypes, fn($item) => $item->getId() !== $id);
            }

            return $result;
        } catch (cDbException|cException $e) {
            cError($e->getMessage());

            return false;
        }
    }

    public function addNew(string $metaType, string $fieldType, int $maxLength, string $fieldName): ?cApiMetaType
    {
        try {
            $metaType = $this->metaTypeCollection->create($metaType, $fieldType, $maxLength, $fieldName);
            self::$metaTypes[] = $metaType;
        } catch (cDbException|cException $e) {
            cError($e->getMessage());

            $metaType = null;
        }

        return $metaType;
    }

    /**
     * Returns the meta tag item from the passed meta tags list corresponding to the meta tag type.
     *
     * @param cApiMetaTag[] $metaTags
     */
    public static function findMetaTagByMetaType(cApiMetaType $metaType, array $metaTags): ?cApiMetaTag
    {
        return array_find(
            $metaTags,
            fn(cApiMetaTag $item) => $item->get('idmetatype') === $metaType->getId()
        );
    }
}
