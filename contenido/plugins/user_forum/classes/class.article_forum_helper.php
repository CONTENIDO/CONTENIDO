<?php

/**
 * This file contains the helper class for the UserForum plugin.
 *
 * @package    Plugin
 * @subpackage UserForum
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains helper functions for the UserForum plugin
 *
 * @package    Plugin
 * @subpackage UserForum
 */
class ArticleForumHelper
{
    /**
     * @param array $forumStruct
     * @param array $result
     * @param int $level
     */
    public static function normalizeArray(array $forumStruct, array &$result, int $level = 0): void
    {
        foreach ($forumStruct as $key => $value) {
            $value['level'] = $level;
            if (isset($value['children'])) {
                unset($value['children']);
            }
            $result[$key] = $value;
            self::normalizeArray($value['children'] ?? [], $result, $level + 1);
        }
    }
}
