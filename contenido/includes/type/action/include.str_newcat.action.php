<?php
/**
 * Backend action file str_newcat
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Dominik Ziegler
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.str.php');

$tmp_newid  = strNewCategory($idcat, $categoryname, true, $categoryalias, $visible, $public, $idtplcfg);
cApiCecHook::execute("Contenido.Action.str_newcat.AfterCall", array(
    'newcategoryid' => $tmp_newid,
    'idcat'         => $idcat,
    'categoryname'  => $categoryname,
    'categoryalias' => $categoryalias,
    'visible'       => $visible,
    'public'        => $public,
    'idtplcfg'      => $idtplcfg,
));
?>