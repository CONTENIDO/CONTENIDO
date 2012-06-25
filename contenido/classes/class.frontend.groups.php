<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Frontend groups class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.7
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2011-09-20] Use new classes in contenido/classes/contenido/class.frontend.group.php
 *                          - Use cApiFrontendGroupCollection instead of FrontendGroupCollection
 *                          - Use cApiFrontendGroup instead of FrontendGroup
 *                          and new classes in contenido/classes/contenido/class.frontend.group.member.php
 *                          - Use cApiFrontendGroupMemberCollection instead of FrontendGroupMemberCollection
 *                          - Use cApiFrontendGroupMember instead of FrontendGroupMember
 *
 * {@internal
 *   created  unknown
 *   modified 2008-06-30, Frederic Schneider, add security fix
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-09-20, Murat Purc, removed in favor of normalizing the API
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

?>