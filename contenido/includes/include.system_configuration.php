<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * File provides a user friendly way for setting general system properties instead of using
 * Systemproperties
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @version    1.0.0
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.8
 * 
 * {@internal 
 *   created 2008-08-19
 *
 * }}
 * 
 */
if(!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}


function renderBooleanProperty($sName, $aPossValues, $sValue, $sLabel) {
    $aReturn = array();
    
    if ($aPossValues[0] == $sValue) {
        $bChecked = false;
    } else {
        $bChecked = true;
    }

    $oCheckbox = new cHTMLCheckbox($sName, $aPossValues[1], $sName, $bChecked);
    $oCheckbox->setLabelText('&nbsp;&nbsp;'.$sLabel);
    $oCheckbox->setStyle('margin:0; padding:0px;margin-left:3px;');
    
    $aReturn['input'] = $oCheckbox->render();
    $aReturn['label'] = '';
    
    return $aReturn;
}

function renderLabel($sLabel, $sName, $iWidth = 250, $sSeperator = ':') {
    $oLabel = new cHTMLLabel($sLabel.$sSeperator, $sName);
    $oLabel->setStyle('padding:3px;display:block;float:left;width:'.$iWidth.'px;');
    return $oLabel->render();
}

function renderTextProperty($sName, $sValue, $sLabel) {
    $oTextbox = new cHTMLTextbox($sName, $sValue, "50", "96");
    $oTextbox->setStyle('width:320px;');
    $aReturn['input'] = $oTextbox->render();
    $aReturn['label'] = renderLabel($sLabel, $sName);
    return $aReturn;
}

function getPostValue($aProperty) {
    $sName = $aProperty['type'].'{_}'.$aProperty['name'];
    if (isset($_POST[$sName])) {
        if (is_array($aProperty['value'])) {
            if (in_array($_POST[$sName], $aProperty['value'])) {
                return $_POST[$sName];
            } else {
                return $aProperty['value'][0];
            }
        } else {
            if ($aProperty['value'] == 'integer') {
                return (int) $_POST[$sName];
            } else {
                return $_POST[$sName];
            }
        }
    }

    if (is_array($aProperty['value'])) {
        return $aProperty['value'][0];
    } else {
        return '';
    }
} 

$aManagedProperties = array(
                          array('type' => 'versioning', 'name' => 'activated', 'value' => array('false', 'true'), 'label' => i18n('Versioning activated'), 'group' => i18n('Versioning')),
                          array('type' => 'versioning', 'name' => 'path', 'value' => '', 'label' => i18n('Serverpath to version files'), 'group' => i18n('Versioning')),
                          array('type' => 'versioning', 'name' => 'prune_limit', 'value' => 'integer', 'label' => i18n('Maximum number of stored versions'), 'group' => i18n('Versioning')),
                          array('type' => 'update', 'name' => 'check', 'value' => array('false', 'true'), 'label' => i18n('Check for updates'), 'group' => i18n('Update notifier')),
                          array('type' => 'update', 'name' => 'news_feed', 'value' => array('false', 'true'), 'label' => i18n('Get news from contenido.org'), 'group' => i18n('Update notifier')),
                          array('type' => 'update', 'name' => 'check_period', 'value' => 'integer', 'label' => i18n('Update check period (minutes)'), 'group' => i18n('Update notifier')),
                          array('type' => 'system', 'name' => 'clickmenu', 'value' => array('false', 'true'), 'label' => i18n('Clickable menu in backend'), 'group' => i18n('Backend')),
                          array('type' => 'pw_request', 'name' => 'enable', 'value' => array('false', 'true'), 'label' => i18n('Use passwordrequest in Backend'), 'group' => i18n('Backend')),
                          array('type' => 'maintenance', 'name' => 'mode', 'value' => array('disabled', 'enabled'), 'label' => i18n('Activate maintenance mode'), 'group' => i18n('Backend')),
                          array('type' => 'edit_area', 'name' => 'activated', 'value' => array('false', 'true'), 'label' => i18n('Use editarea for code highlighting'), 'group' => i18n('Backend')),
                          array('type' => 'system', 'name' => 'insight_editing_activated', 'value' => array('false', 'true'), 'label' => i18n('Use TinyMce as insight editor'), 'group' => i18n('Backend')), 
						  array('type' => 'backend', 'name' => 'preferred_idclient', 'value' => 'integer', 'label' => i18n('Default client (ID)'), 'group' => i18n('Backend')),
                          array('type' => 'system', 'name' => 'mail_host', 'value' => '', 'label' => i18n('Mailserver host'), 'group' => i18n('Mailserver')),
                          array('type' => 'system', 'name' => 'mail_sender', 'value' => '', 'label' => i18n('Mailserver sender name'), 'group' => i18n('Mailserver')),
                          array('type' => 'system', 'name' => 'mail_sender_name', 'value' => '', 'label' => i18n('Mailserver sender mail'), 'group' => i18n('Mailserver')),
                          array('type' => 'generator', 'name' => 'xhtml', 'value' => array('false', 'true'), 'label' => i18n('Generate XHTML'), 'group' => i18n('Development')),
                          array('type' => 'generator', 'name' => 'basehref', 'value' => array('false', 'true'), 'label' => i18n('Generate basehref'), 'group' => i18n('Development')),
                          array('type' => 'imagemagick', 'name' => 'available', 'value' => array('0', '1'), 'label' => i18n('Use image magic (if available)'), 'group' => i18n('Development'))				  
					  );

$aSettings = getSystemProperties(1);

if (isset($_POST['action']) && $_POST['action'] == 'edit_sysconf' && $perm->have_perm_area_action($area, 'edit_sysconf')) {
   $bStored = false;
   foreach ($aManagedProperties as $aProperty) {
        $sValue = getPostValue($aProperty);
        $sStoredValue = $aSettings[$aProperty['type']][$aProperty['name']]['value'];

        if ($sStoredValue != $sValue &&  (is_array($aProperty['value']) && $sValue != '' || !is_array($aProperty['value']))) {
            setSystemProperty($aProperty['type'], $aProperty['name'], $sValue); 
            $bStored = true;
        }        
   }  
   if ($bStored) {
        $sNotification = $notification->displayNotification("info", i18n("Changes saved"));
   }   
}


                      
$aSettings = getSystemProperties(1);

$oForm = new UI_Table_Form("system_configuration");
$oForm->addHeader(i18n("System Configuration"));
$oForm ->setWidth("770");
$oForm->setVar("area", $area);
$oForm->setVar("frame", $frame);
$oForm->setVar("action", 'edit_sysconf');

$sCurGroup = '';
$sLeftContent = '';

$sRowTemplate = '<p style="margin:0; padding:3px;">%s</p>';

foreach ($aManagedProperties as $aProperty) {
    $sName = $aProperty['type'].'{_}'.$aProperty['name'];
    if (isset($aSettings[$aProperty['type']][$aProperty['name']]['value'])) {
        $sValue = $aSettings[$aProperty['type']][$aProperty['name']]['value'];
    } else {
        $sValue = '';
    }
    
    if (is_array($aProperty['value'])) {
        $aHtmlElement = renderBooleanProperty($sName, $aProperty['value'], $sValue, $aProperty['label']);
    } else {
        $aHtmlElement = renderTextProperty($sName, $sValue, $aProperty['label']);
    }
    
    if ($sCurGroup == '' || $sCurGroup == $aProperty['group']) {
        if ($sCurGroup == '') {
            $sCurGroup = $aProperty['group'];
        }
        $sLeftContent .= sprintf($sRowTemplate, $aHtmlElement['label']. $aHtmlElement['input']);
    } else {
        $oForm->add(renderLabel($sCurGroup, '', 150, ''), $sLeftContent);
        $sCurGroup = $aProperty['group'];
        $sLeftContent = sprintf($sRowTemplate, $aHtmlElement['label']. $aHtmlElement['input']);
    }
}

$oForm->add(renderLabel($sCurGroup, '', 150, ''), $sLeftContent);

$sJs = '<script type="text/javascript">
          if (top.content.right_top.document.getElementById(\'c_1\') ) {
              menuItem = top.content.right_top.document.getElementById(\'c_1\');
              top.content.right_top.sub.clicked(menuItem.firstChild);
          }
       </script>';

$oPage = new cPage;
if ($perm->have_perm_area_action($area, 'edit_sysconf')) {
    $oPage->setContent($sNotification.$oForm->render());
} else {
    $oPage->setContent($notification->returnNotification("error", i18n('Access denied'), 1));
}
$oPage->addScript('setMenu', $sJs);
$oPage->render();
?>