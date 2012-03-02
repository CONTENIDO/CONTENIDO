<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Class for action information and management
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * @deprecated [2012-03-01] Use class in contenido/classes/contenido/class.action.php
 *                          - Use cApiActionCollection
 * 
 * {@internal 
 *   created 2003
 *   modified 2008-06-30, Frederic Schneider, add security fix
 *   modified 2009-10-15, Dominik Ziegler, getAvailableActions() now also returns the areaname
 *   modified 2010-07-03, Ortwin Pinke, CON-318, only return actions marked as relevant in getAvailableActions()
 *                        also fixed doc-comment for getActionName()
 *
 *   $Id$;
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

?>