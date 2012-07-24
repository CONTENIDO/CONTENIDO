<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * str_newtree action
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Backend Includes
 * @version    0.0.1
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$tmp_newid = strNewTree($categoryname, $categoryalias, $visible, $public, $idtplcfg);
strRemakeTreeTable();
cApiCecHook::execute("Contenido.Action.str_newtree.AfterCall", array(
    'newcategoryid' => $tmp_newid,
    'categoryname'  => $categoryname,
    'categoryalias' => $categoryalias,
    'visible'       => $visible,
    'public'        => $public,
    'idtplcfg'      => $idtplcfg,
));
?>