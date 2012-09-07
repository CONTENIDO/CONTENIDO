<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Form Element Generator
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.5
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-05-20
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *
 *   $Id: functions.forms.php 309 2008-06-26 10:06:56Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * Generates textial Input Form elements
 *
 * @param $type       Either "text", "password" or "textbox"
 * @param $name       Name of the field
 * @param $initvalue  Init value of the field
 * @param $size       Size of the field
 * @param $maxlen     Maximum length of the field
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 * @return Generated field code
 *
 */
function formGenerateField ($type, $name, $initvalue, $width, $maxlen)
{
    switch ($type)
    {
        case "text":
            return ('<input class="text_medium" type="text" name="'.$name.'" size="'.$width.'" maxlength="'.$maxlen.'" value="'.$initvalue.'">');
            break;
        case "password":
            return ('<input class="text_medium" type="password" name="'.$name.'" size="'.$width.'" maxlength="'.$maxlen.'" value="'.$initvalue.'">');
            break;
        case "textbox":
            return ('<textarea class="text_medium" name="'.$name.'" rows="'.$maxlen.'" cols="'.$width.'">'.$initvalue.'</textarea>');
            break;
        default:
            return('');
            break;
    }
        

}

/**
 * Generates check box elements
 *
 * @param $name       Name of the checkbox
 * @param $value      Value of the checkbox
 * @param $checked    Initially checked?
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 * @return Generated field code
 *
 */
function formGenerateCheckbox ($name, $value, $checked, $caption = "")
{
	if (strlen($caption) > 0)
	{
		$label = '<label for="'.$name.$value.'">'.$caption.'</label>';
	} else {
		$label = "";
	}
	
    if ($checked) {
        return('<input class="text_medium" id="'.$name.$value.'" type="checkbox" name="'.$name.'" value="'.$value.'" checked>'.$label);
    } else {
        return('<input class="text_medium" id="'.$name.$value.'" type="checkbox" name="'.$name.'" value="'.$value.'">'.$label);
    }

}
?>