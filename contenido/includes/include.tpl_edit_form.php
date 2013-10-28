<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Template edit form
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.2.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *   modified 2009-12-04, Dominik Ziegler, added notice for default module and removed auto select of module if there is no module set
 *
 *   $Id: include.tpl_edit_form.php 1097 2009-12-04 16:17:42Z dominik.ziegler $:
 * }}
 * 
 */


if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$tpl2 = new Template();

if (($action == "tpl_new") && (!$perm->have_perm_area_action_anyitem($area, $action))) {
    $notification->displayNotification("error", i18n("Permission denied"));
} else {

	if ($action == "tpl_new") {
		$tplname = "-- " . i18n("New template") . " --";
	}

	$sql = "SELECT
			a.idtpl, a.name as name, a.description, a.idlay, b.description as laydescription, a.defaulttemplate 
			FROM
			" . $cfg['tab']['tpl'] . " AS a
			LEFT JOIN
			" . $cfg['tab']['lay'] . " AS b
			ON a.idlay=b.idlay
			WHERE a.idtpl='" . Contenido_Security::toInteger($idtpl) . "'
			ORDER BY name";

	$db->query($sql);

	if ($db->next_record()) {
		$idtpl          = $db->f("idtpl");
		$tplname        = $db->f("name");
		$description    = $db->f("description");
		$idlay          = $db->f("idlay");
		$laydescription = nl2br($db->f("laydescription"));
		$vdefault 		= $db->f("defaulttemplate");
	}

	$sql = "SELECT
			number, idmod
			FROM
			" . $cfg['tab']['container'] . "
			WHERE
			idtpl='" . Contenido_Security::toInteger($idtpl) . "'";

	$db->query($sql);
	while( $db->next_record() ) {
		$a_c[$db->f("number")] = $db->f("idmod");
	}

	//*************** List layouts ****************	
	$tpl2->set('s', 'NAME', 'idlay');
	$tpl2->set('s', 'CLASS', 'text_medium');
	$tpl2->set('s', 'OPTIONS', 'onchange="tplform.changelayout.value=1;tplform.submit();"');

	if ($idlay != 0) {
			$tpl2->set('d', 'VALUE', 0);
			$tpl2->set('d', 'CAPTION', '--- '.i18n("none").' ---');
			$tpl2->set('d', 'SELECTED', '');
			$tpl2->next();
		} else {
			$tpl2->set('d', 'VALUE', 0);
			$tpl2->set('d', 'CAPTION', '--- '.i18n("none") .' ---' );
			$tpl2->set('d', 'SELECTED', 'selected');
			$tpl2->next();
	}
	$sql = "SELECT
			idlay, name
			FROM
			" . $cfg['tab']['lay'] . "
			WHERE
			idclient='" . Contenido_Security::toInteger($client) . "'
			ORDER BY name";
			
	$db->query($sql);

	while ($db->next_record()) {
		if ($db->f("idlay") != $idlay) {
			$tpl2->set('d', 'VALUE', $db->f("idlay"));
			$tpl2->set('d', 'CAPTION', $db->f("name"));
			$tpl2->set('d', 'SELECTED', '');
			$tpl2->next();
		} else {
			$tpl2->set('d', 'VALUE', $db->f("idlay"));
			$tpl2->set('d', 'CAPTION', $db->f("name"));
			$tpl2->set('d', 'SELECTED', 'selected');
			$tpl2->next();
		}
	}

	$select = $tpl2->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true);

	$sql = "SELECT
			idmod, name, type
			FROM
			" . $cfg['tab']['mod'] . "
			WHERE
			idclient='" . Contenido_Security::toInteger($client) . "'
			ORDER BY name";
			
	$db->query($sql);

	$modules = array();

	while ($db->next_record()) {
		$modules[$db->f("idmod")]["name"] = $db->f("name");
		$modules[$db->f("idmod")]["type"] = $db->f("type");	
	}


	$form = new UI_Table_Form("tplform");
	$form->setVar("area", $area);
	$form->setVar("changelayout", 0);
	$form->setVar("frame", $frame);
	$form->setVar("action", "tpl_edit");
	$form->setVar("idtpl", $idtpl);
	if (!$idlay) {
		$form->setVar("createmode", 1);
	}
	$form->addHeader(i18n("Edit template"));

	$name = new cHTMLTextbox("tplname",$tplname,35);
	$form->add(i18n("Name"), $name->render());

	$descr = new cHTMLTextarea("description", $description);
	$form->add(i18n("Description"), $descr->render());

	 
	$standardcb = new cHTMLCheckbox("vdefault", 1, "", $vdefault);
	$form->add(i18n("Default"), $standardcb->toHTML(false));

	$form->add(i18n("Layout"), $select);
	$form->add(i18n("Layout description"), $laydescription);

	if ($idlay) {
		tplPreparseLayout($idlay);
		
		$tmp_returnstring = tplBrowseLayoutForContainers($idlay);

		$a_container = explode("&",$tmp_returnstring);
		
		foreach ($a_container as $key=>$value) {
			if ($value != 0) {
				// Loop through containers ****************
				$name = tplGetContainerName($idlay, $value);
				
				$modselect = new cHTMLSelectElement("c[".$value."]");

				if ($name != "") {
					$caption = $name." (Container $value)";
				} else {
					$caption = 'Container '.$value;				
				}

				$mode = tplGetContainerMode($idlay, $value);
				$defaultModuleNotice = '';
				
				if ($mode == "fixed") {
					$default = tplGetContainerDefault($idlay, $value);
					
					foreach ($modules as $key => $val) {
						
						if ($val["name"] == $default) {
							$option = new cHTMLOptionElement($val["name"], $key);
							
							if ($a_c[$value] == $key) {
								$option->setSelected(true);
							}
							
							$modselect->addOptionElement($key, $option);
						}
					}				
				} else {
					$default = tplGetContainerDefault($idlay, $value);

					if ($mode == "optional" || $mode == "") {
						$option = new cHTMLOptionElement("-- ".i18n("none")." --",0);
						
						if (isset($a_c[$value]) && $a_c[$value] != 0) {
							$option->setSelected(false);
						} else {
							$option->setSelected(true);
						}
					
						$modselect->addOptionElement(0, $option);
					}
					
					$allowedtypes = tplGetContainerTypes($idlay, $value);

					foreach ($modules as $key => $val) {
						$option = new cHTMLOptionElement($val["name"], $key);
						
						//if ($a_c[$value] == $key || ($a_c[$value] == 0 && $val["name"] == $default))
						if ($a_c[$value] == $key || (($a_c[$value] == 0 && $val["name"] == $default) && $createmode == 1)) {
							$option->setSelected(true);
						}
						
						if (count($allowedtypes) > 0) {
							if (in_array($val["type"], $allowedtypes) || $val["type"] == "") {
								$modselect->addOptionElement($key, $option);
							}
						} else {
							$modselect->addOptionElement($key, $option);
						}
					}
					
					if ( $default != "" && $modules[$a_c[$value]]["name"] != $default && $createmode != 1 ) {
						$defaultModuleNotice = "&nbsp;(" . i18n("Default") . ": " . $default . ")";
					}
				}

				$form->add($caption, $modselect->render() . $defaultModuleNotice);
			}
		}
	}

	$page = new cPage;
	$page->setReload();

	$page->setSubnav("idtpl=$idtpl", "tpl");

	if ($action != "tpl_duplicate") {
		$page->setContent($form->render());
	}

	$page->render();
} 
?>