<?php

/**
 * This file contains the extended base XML class.
 *
 * @since      CONTENIDO 4.10.2 - Class code extracted from `contenido/includes/include.con_content_list.php`.
 * @package    Core
 * @subpackage XML
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Extended SimpleXMLElement class to add CDATA to content
 */
class cSimpleXMLExtended extends SimpleXMLElement
{

    /**
     * Appends DOMCdataSection to the DOMNode.
     *
     * @param string $data
     */
    public function addCData(string $data)
    {
        $element = dom_import_simplexml($this);
        $node = $element->ownerDocument;
        $element->appendChild($node->createCDATASection($data));
    }

}
