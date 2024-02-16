<?php

/**
 * This file contains the Newsletter Plugin class.
 *
 * @since      CONTENIDO 4.10.2
 * @package    Plugin
 * @subpackage Newsletter
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

final class PiNewsletter
{

    /**
     * Name of this plugin
     * @var string
     */
    private $name;

    /**
     * Foldername of this plugin
     * @var string
     */
    private $folderName;

    /**
     * @var array
     */
    private $data = [];

    public function __construct()
    {
        $this->name = 'Newsletter';
        $this->folderName = basename(dirname(__DIR__, 1));
    }

    /**
     * Returns plugin name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns plugin folder name.
     *
     * @return string
     */
    public function getFolderName(): string
    {
        return $this->folderName;
    }

    /**
     * Returns date format for current language.
     * Returns the default configured date format in plugin, if no setting found.
     *
     * @param int $idLang
     * @return string
     */
    public function getDateFormat(int $idLang): string
    {
        $key = 'dateFormat:' . $idLang; 
        if (!isset($this->data[$key])) {
            $this->initializeDateTimeFormatData($idLang);
        }

        return $this->data[$key];
    }

    /**
     * Returns time format for current language.
     * Returns the default configured time format in plugin, if no setting found.
     *
     * @param int $idLang
     * @return string
     */
    public function getTimeFormat(int $idLang): string
    {
        $key = 'timeFormat:' . $idLang;
        if (!isset($this->data[$key])) {
            $this->initializeDateTimeFormatData($idLang);
        }

        return $this->data[$key];
    }

    private function initializeDateTimeFormatData(int $idLang)
    {
        $cfg = cRegistry::getConfig();

        $dateKey = 'dateFormat:' . $idLang;
        $timeKey = 'timeFormat:' . $idLang;

        try {
            $oLanguage = new cApiLanguage($idLang);
            $dateFormat = $oLanguage->getProperty('dateformat', 'date');
            $timeFormat = $oLanguage->getProperty('dateformat', 'time');
        } catch (Throwable $e) {
            cLogError('Could not initialize date/time format. Error: ' . $e->getMessage());
        }

        if (empty($dateFormat)) {
            $dateFormat = $cfg['pi_newsletter']['defaultDateFormat'];
        }
        if (empty($timeFormat)) {
            $timeFormat = $cfg['pi_newsletter']['defaultTimeFormat'];
        }

        $this->data[$dateKey] = $dateFormat;
        $this->data[$timeKey] = $timeFormat;
    }

}