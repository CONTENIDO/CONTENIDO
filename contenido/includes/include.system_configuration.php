<?php
/**
 * This file contains the system configuration backend page.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Trautmann, Simon Sprankel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Renders a select element with a label.
 * If there are only two possible values which are called true/false,
 * enabled/disabled or 0/1, a checkbox is rendered.
 * Returns an associative array with the label and the input field.
 *
 * @param string $name the name of the form element
 * @param array $possibleValues the possible values
 * @param string $value the value which should be selected
 * @param string $label the label text which should be rendered
 * @return array associative array with the label and the input field
 */
function renderSelectProperty($name, $possibleValues, $value, $label) {
    global $auth;

    if (count($possibleValues) === 2 && (in_array('true', $possibleValues) && in_array('false', $possibleValues) || in_array('enabled', $possibleValues) && in_array('disabled', $possibleValues) || in_array('0', $possibleValues) && in_array('1', $possibleValues))) {
        // render a checkbox if there are only the values true and false
        $checked = $value == 'true' || $value == '1' || $value == 'enabled';
        $html = new cHTMLCheckbox($name, $possibleValues[0], $name, $checked);
        $html->setLabelText($label);
    } else {
        // otherwise render a select box with the possible values
        $html = new cHTMLSelectElement('');
        foreach ($possibleValues as $possibleValue) {
            $element = new cHTMLOptionElement($possibleValue, $possibleValue);
            if ($possibleValue == $value) {
                $element->setSelected(true);
            }
            $html->appendOptionElement($element);
        }
    }

    // disable the HTML element if user is not a sysadmin
    if (strpos($auth->auth['perm'], 'sysadmin') === false) {
        $html->updateAttribute('disabled', 'true');
    }

    $return = array();
    $return['input'] = $html->render();
    $return['label'] = '';

    return $return;
}

/**
 * Renders a cHTMLLabel.
 *
 * @param string $text the label text
 * @param string $name the name of the corresponding input element
 * @param string $width the width in pixel
 * @param string $seperator the seperator which is written at the end of the
 *            label
 * @return string the rendered cHTMLLabel element
 */
function renderLabel($text, $name, $width = 250, $seperator = ':') {
    $label = new cHTMLLabel($text . $seperator, $name);
    $label->setStyle('padding:3px;display:block;float:left;width:' . $width . 'px;');

    return $label->render();
}

/**
 * Renders a cHTMLTextbox.
 * Returns an associative array with the label and the input field.
 *
 * @param string $name the name of the form element
 * @param string $value the value of the text field
 * @param string $label the label text
 * @return array associative array with the label and the input field
 */
function renderTextProperty($name, $value, $label) {
    global $auth;

    $textbox = new cHTMLTextbox($name, conHtmlSpecialChars($value), '50', '96');
    // disable the textbox if user is not a sysadmin
    if (strpos($auth->auth['perm'], 'sysadmin') === false) {
        $textbox->updateAttribute('disabled', 'true');
    }
    $return = array();
    $return['input'] = $textbox->render();
    $return['label'] = renderLabel($label, $name);

    return $return;
}

$page = new cGuiPage('system_configuration', '', '1');

// read the properties from the XML file
$propertyTypes = cXmlReader::xmlStringToArray(cFileHandler::read($cfg['path']['xml'] . 'system.xml'));
$propertyTypes = $propertyTypes['properties'];

// get the stored settings
$settings = getSystemProperties();

$reloadHeader = false;
// store the system properties
if (isset($_POST['action']) && $_POST['action'] == 'edit_sysconf' && $perm->have_perm_area_action($area, 'edit_sysconf')) {
    if (strpos($auth->auth['perm'], 'sysadmin') === false) {
        $page->displayError(i18n('You don\'t have the permission to make changes here.'));
    } else {
        // @TODO Find a general solution for this!
        if (defined('CON_STRIPSLASHES')) {
            $post = cString::stripSlashes($_POST);
        } else {
            $post = $_POST;
        }
        $stored = false;
        foreach ($propertyTypes as $type => $properties) {
            foreach ($properties as $name => $infos) {
                // get the posted value
                $fieldName = $type . '{_}' . $name;
                if (isset($post[$fieldName])) {
                    $value = $post[$fieldName];
                } else {
                    $value = (isset($infos['values'][1])) ? $infos['values'][1] : 'false';
                }

                $storedValue = $settings[$type][$name];
                if ($storedValue != $value && (is_array($infos['values']) && $value != '' || !is_array($infos['values']))) {
                    if ($type == 'update' && $name == 'check_period' && $value < 60) {
                        $page->displayError(i18n('Update check period must be at least 60 minutes.'));
                        $stored = false;
                        // break out of both loops
                        break 2;
                    } else {
                        setSystemProperty($type, $name, $value);
                        // also update the settings array because it is used below
                        $settings[$type][$name] = $value;
                        $stored = true;

                        if (($type == 'debug' && $name == 'debug_to_screen') || ($type == 'system' && $name == 'clickmenu')) {
                            $reloadHeader = true;
                        }
                    }
                }
            }
        }
        if ($stored) {
            $page->displayInfo(i18n('Changes saved'));
        }
    }
}

// generate the table for changing the system properties
$form = new cGuiTableForm('system_configuration');
$form->addHeader(i18n('System configuration'));
$form->setVar('area', $area);
$form->setVar('frame', $frame);
$form->setVar('action', 'edit_sysconf');

// show a disabled OK button if user is not a sysadmin
if (strpos($auth->auth['perm'], 'sysadmin') === false) {
    $form->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok_off.gif', i18n("You are not sysadmin. You can't change these settings."), 's');
}

$groups = array();
$currentGroup = '';
$leftContent = '';
// iterate over all property types
foreach ($propertyTypes as $type => $properties) {
    foreach ($properties as $name => $infos) {
        // $infos is an array with the keys 'values', 'label' and 'group'
        // extend the groups array if it is a new group
        if (!isset($groups[$infos['group']])) {
            $groups[$infos['group']] = '';
        }

        // get the currently stored value
        if (isset($settings[$type][$name])) {
            $value = $settings[$type][$name];
        } else {
            $value = '';
        }

        // render the HTML and add it to the groups array
        $fieldName = $type . '{_}' . $name;
        if (is_array($infos['values'])) {
            $htmlElement = renderSelectProperty($fieldName, $infos['values'], $value, i18n($infos['label']));
        } else {
            $htmlElement = renderTextProperty($fieldName, $value, i18n($infos['label']));
        }

        $groups[$infos['group']] .= new cHTMLDiv($htmlElement['label'] . $htmlElement['input'], 'systemSetting');
    }
}

// render the group names and the corresponding settings
foreach ($groups as $groupName => $groupSettings) {
    $groupName = i18n($groupName);
    $form->add(renderLabel($groupName, '', 150, ''), $groupSettings);
}

// show error if user is not allowed to see the page
if ($perm->have_perm_area_action($area, 'edit_sysconf')) {
    $page->set('s', 'RELOAD_HEADER', ($reloadHeader) ? 'true' : 'false');
    $page->set('s', 'FORM', $form->render());
} else {
    $page->displayCriticalError(i18n('Access denied'));
}

$page->render();

