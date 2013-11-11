<?php
/**
 * Repair common mistakes in links
 *
 * @package Plugin
 * @subpackage Linkchecker
 * @version SVN Revision $Rev:$
 *
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Repair common mistakes in links
 *
 * @author frederic.schneider
 *
 */
class LinkcheckerRepair {

    /**
     * Typical link misstakes
     *
     * @access private
     * @var string
     */
    private $errorTypes = array(
        'htp://',
        'htttp://',
        'htps://',
        'htttps://',
        'ww',
        'www',
        'wwww'
    );

    /**
     * Fixed link misstakes
     * Keys are equivalent to $errorTypes
     *
     * @access private
     * @var string
     */
    private $correctTypes = array(
        'http://',
        'http://',
        'https://',
        'https://',
        'http://www',
        'http://www',
        'http://www'
    );

    /**
     * Checks link and generate a repaired version
     *
     * @access public
     */
    public function checkLink($link) {

        foreach ($this->errorTypes as $errorTypeKey => $errorType) {
            if (substr($link, 0, strlen($errorType)) == $errorType) {
                $repairedlink = str_replace($errorType, $this->correctTypes[$errorTypeKey], $link);
                if ($this->pingRepairedLink($repairedlink) == true) {
                    return $repairedlink;
                } else {
                    return false;
                }
            }
        }

    }

    /**
     * Test repaired link
     *
     * @access private
     * @param string $repairedlink
     */
    private function pingRepairedLink($repairedlink) {
        return @fopen($repairedlink, 'r');
    }

}
?>