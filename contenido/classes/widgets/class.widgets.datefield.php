<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/** @deprecated 2012-03-10 This class is not longer supported. */
class cDatefield extends cHTMLTextbox
{
    var $_oDate;

    function cDatefield($name, $initvalue, $width = 10)
    {
        cDeprecated("This class is not longer supported.");
        $this->_oDate = new cDatatypeDateTime;
        $this->_oDate->set($initvalue);
        parent::cHTMLTextbox($name, $initvalue, $width);

    }

    function render()
    {
        if ($this->_oDate->get(cDateTime_ISO) != "1970-01-01") {
            if ($this->_oDate->_cTargetFormat == cDateTime_Custom) {
                parent::setValue($this->_oDate->render());
            } else {
                parent::setValue($this->_oDate->render(cDateTime_Locale_DateOnly));
            }
        }
        return parent::render();
    }
}

?>