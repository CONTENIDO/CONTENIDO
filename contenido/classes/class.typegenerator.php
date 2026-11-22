<?php

/**
 * This file contains content type generator class.
 * TODO: This class needs more documentation.
 *
 * @package    Core
 * @subpackage ContentType
 * @author     Alexander Scheider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class generates content types.
 *
 * @package    Core
 * @subpackage ContentType
 */
class cTypeGenerator
{
    /**
     * @since CONTENIDO 4.10.2
     */
    private static ?cArticleContentHelper $articleContentHelper = NULL;

    private static array $a_content = [];

    private int $articleId;

    private int $languageId;

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cDbException
     */
    public function __construct()
    {
        $this->articleId = cRegistry::getArticleId(true);
        $this->languageId = cRegistry::getLanguageId();

        if (!isset(self::$a_content[$this->articleId])) {
            $this->fillContent();
        }
    }

    /**
     * @deprecated [2026-09-20] Since CONTENIDO 4.10.2, use {@see cTypeGenerator::getContentTypeClassName()} instead
     */
    protected function _getContentTypeClassName(string $type): string
    {
        cDeprecated("The function _getContentTypeClassName() is deprecated since CONTENIDO 4.10.2, use cTypeGenerator::getContentTypeClassName() instead.");
        return self::getContentTypeClassName($type);
    }

    /**
     * Returns the classname for a content type.
     *
     * @param string $type Content type, e.g. CMS_HTMLHEAD
     * @return string The classname e.g. cContentTypeHtmlhead for content type CMS_HTMLHEAD
     */
    public static function getContentTypeClassName(string $type): string
    {
        // Remove the prefix 'CMS_'
        $contentType = cString::getPartOfString($type, 4);

        return sprintf('cContentType%s', cString::ucfirst(cString::toLowerCase($contentType)));
    }

    /**
     * Returns the full path to the include file name of a content type.
     *
     * @param string $type Content type, e.g. CMS_HTMLHEAD
     * @return string The full path e.g.
     *         {path_to_contenido_includes}/type/code/include.CMS_HTMLHEAD.code.php
     *         for content type CMS_HTMLHEAD
     * @since CONTENIDO 4.10.2
     */
    public static function getContentTypeCodeFilePathName(string $type): string
    {
        $cfg = cRegistry::getConfig();

        return cRegistry::getBackendPath() . $cfg['path']['includes'] . 'type/code/include.' . $type . '.code.php';
    }

    /**
     * @deprecated [2026-09-20] Since CONTENIDO 4.10.2, use {@see cTypeGenerator::getContentTypeCodeFilePathName()} instead
     */
    protected function _getContentTypeCodeFilePathName($type): string
    {
        cDeprecated("The function _getContentTypeCodeFilePathName() is deprecated since CONTENIDO 4.10.2, use cTypeGenerator::getContentTypeCodeFilePathName() instead.");
        return self::getContentTypeCodeFilePathName($type);
    }

    /**
     * Fill content from db for current article
     *
     * @throws cDbException
     */
    private function fillContent(): void
    {
        self::$a_content[$this->articleId] = [];

        if (!isset(self::$articleContentHelper)) {
            self::$articleContentHelper = new cArticleContentHelper();
        }

        self::$a_content[$this->articleId] = self::$articleContentHelper->getContentByIdArtAndIdLang(
            $this->articleId, $this->languageId
        );
    }

    /**
     * @throws cDbException|cException
     */
    private function processCmsTags(string $type, int $index): string
    {
        $oTypeColl = new cApiTypeCollection();
        $oTypeColl->select();

        $typeList = [];
        while ($oType = $oTypeColl->next()) {
            $typeList[] = $oType->toObject();
        }

        // Replace all CMS_TAGS[]
        foreach ($typeList as $typeItem) {
            if ($type === $typeItem->type) {
                $items[] = $typeItem->type;

                $typeClassName = self::getContentTypeClassName($typeItem->type);
                $typeCodeFile = self::getContentTypeCodeFilePathName($typeItem->type);

                $settings = self::$a_content[$this->articleId][$typeItem->type][$index] ?? '';
                /** @var cContentTypeAbstract $cTypeObject */
                $cTypeObject = new $typeClassName($settings, $index, $items);
                if (cRegistry::isBackendEditMode()) {
                    $tmp = $cTypeObject->generateEditCode();
                } else {
                    $tmp = $cTypeObject->generateViewCode();
                }

                return $tmp;
            }
        }

        return '';
    }

    /**
     * Helper function to call a private function
     *
     * @throws cDbException|cException
     */
    public function getGeneratedCmsTag(string $type, int $index): string
    {
        return $this->processCmsTags($type, $index);
    }

}
