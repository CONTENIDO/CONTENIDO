<?php
/**
 * This file contains the class for db queries.
 *
 * @package Plugin
 * @subpackage UserForum
 * @author Claus Schunk
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains feature for db queries.
 *
 *
 * @package Plugin
 * @subpackage UserForum
 */
defined('CON_FRAMEWORK') or die('Illegal call');

/**
 * Class ArticleForum
 */
class ArticleForum extends Item {
    /**
     * ArticleForum constructor.
     *
     * @param bool $id
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($id = false) {
        parent::__construct(cRegistry::getDbTableName('user_forum'), 'id_user_forum');
        $this->setFilters([], []);
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

}

?>