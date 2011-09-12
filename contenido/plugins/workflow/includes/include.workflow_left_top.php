<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 *  Workflow allocation class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend classes
 * @version    1.5
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2003-07-18
 *   
 *   $Id: class.workflowallocation.php,v 1.5 2006/01/13 15:54:41 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$create = new Link;
$create->setMultiLink("workflow","","workflow_common","workflow_create");
//$create->setCLink("workflow_common",4,"workflow_create");
$create->setContent(i18n("Create workflow", "workflow"));
$create->setCustom("idworkflow","-1");

$aAttributes = array();
$aAttributes['class'] = "addfunction";
$create->updateAttributes($aAttributes);

$ui = new UI_Left_Top;
$ui->setLink($create);
$ui->render();

?>