<?php

/**
 * @package    Plugin
 * @subpackage Linkchecker
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Repair common mistakes in links
 *
 * @author frederic.schneider
 */
class cLinkcheckerRepair
{
    /**
     * @var string[] Typical link mistakes
     */
    private array $errorTypes = [
        'htp://',
        'htttp://',
        'htps://',
        'htttps://',
        'ww',
        'www',
        'wwww',
    ];

    /**
     * @var string[] Fixed link mistakes, keys are equivalent to $errorTypes
     */
    private array $correctTypes = [
        'http://',
        'http://',
        'https://',
        'https://',
        'http://www',
        'http://www',
        'http://www',
    ];

    /**
     * Checks link and generate a repaired version
     *
     * @return string|bool
     */
    public function checkLink(string $link)
    {
        foreach ($this->errorTypes as $errorTypeKey => $errorType) {
            if (cString::getPartOfString($link, 0, cString::getStringLength($errorType)) == $errorType) {
                $repairedRink = str_replace($errorType, $this->correctTypes[$errorTypeKey], $link);
                return $this->pingRepairedLink($repairedRink) ? $repairedRink : false;
            }
        }
        return false;
    }

    /**
     * Test repaired link
     */
    private function pingRepairedLink(string $repairedRink): bool
    {
        $repairedRink = cSecurity::escapeString($repairedRink);

        return (bool) @fopen($repairedRink, 'r');
    }
}
