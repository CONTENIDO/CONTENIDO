<?php
/**
 * This file contains the category frontend logic class.
 *
 * @package Plugin
 * @subpackage FrontendLogic
 * @author Andreas Lindner
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Category frontend logic class.
 *
 * This "plugin" contains but a single class frontendlogic_category which
 * extends the core class FrontendLogic. Neither frontendlogic_category nor
 * FrontendLogic are used in the whole project and seem to be deprecated. Author
 * of frontendlogic_category was Andreas Lindner. The author of FrontendLogic is
 * not known.
 *
 * @package Plugin
 * @subpackage FrontendLogic
 */
class frontendlogic_category extends FrontendLogic {

    /**
     * @see FrontendLogic::getFriendlyName()
     */
    public function getFriendlyName() {
        return i18n("Category", "frontendlogic_category");
    }

    /**
     * @see FrontendLogic::listActions()
     */
    public function listActions() {
        return array(
            "access" => i18n("Access category", "frontendlogic_category")
        );
    }

    /**
     * @see FrontendLogic::listItems()
     */
    public function listItems() {
        global $lang, $db, $cfg;

        if (!is_object($db)) {
            $db = cRegistry::getDb();
        }

        $sSQL = "SELECT
                   b.idcatlang,
                   b.name,
                   c.level
                 FROM
                   " . $cfg['tab']['cat'] . " AS a,
                   " . $cfg['tab']['cat_lang'] . " AS b,
                   " . $cfg['tab']['cat_tree'] . " AS c
                 WHERE
                   a.idcat = b.idcat AND
                   a.idcat = c.idcat AND
                   b.idlang = " . $lang . " AND
                   b.public = 0
                 ORDER BY c.idtree ASC";

        $db->query($sSQL);
        while ($db->nextRecord()) {
            $items[$db->f("idcatlang")] = '<span style="padding-left: ' . ($db->f("level") * 10) . 'px;">' . htmldecode($db->f("name")) . '</span>';
        }

        return $items;
    }
}

?>