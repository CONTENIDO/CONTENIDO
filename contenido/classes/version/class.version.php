<?php

/**
 * This file contains the base version class.
 *
 * @package    Core
 * @subpackage Versioning
 * @author     Bilal Arslan, Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Base version class
 *
 * @package    Core
 * @subpackage Versioning
 */
class cVersion
{

    /**
     * @var string Id of Type
     */
    protected $sType;

    /**
     * @var string md5 coded name of author
     */
    protected $sAuthor;

    /**
     * @var string (Date) Time of created
     */
    protected $dCreated;

    /**
     * @var string (Date) Time of last modified
     */
    protected $dLastModified;

    /**
     * @var string Body data of xml file
     */
    protected $aBodyData;

    /**
     * @var array For init global variable
     */
    protected $aCfg;

    /**
     * @var array For init global variable $cfgClient
     */
    protected $aCfgClient;

    /**
     * @var cDb CONTENIDO database object
     */
    protected $oDB;

    /**
     * @var int For init global variable $client
     */
    protected $clientId;

    /**
     * @var array Revision files of current file
     */
    public $aRevisionFiles;

    /**
     * @var int Number of Revision
     */
    protected $iRevisionNumber;

    /**
     * @var array Timestamp
     */
    protected $dTimestamp;

    /**
     * @var string For init global variable $area
     */
    protected $area;

    /**
     * @var int For init global variable $frame
     */
    protected $frame;

    /**
     * @var array For init variables
     */
    protected $aVarForm;

    /**
     * @var int|string Identity the id of Content Type
     */
    protected $entityId;

    /**
     * @var string
     */
    protected $sDescription;

    /**
     * @var string
     */
    protected $revision;

    /**
     * @var bool To take control versioning is switched off
     */
    private $bVersioningActive;

    /**
     * @var int Timestamp
     */
    protected $dActualTimestamp;

    /**
     * @var string Alternative Path for save version files
     */
    protected $sAlternativePath;

    /**
     * @var int Displays Notification only onetime per object
     */
    public static $iDisplayNotification;

    /**
     * Constructor to create an instance of this class.
     *
     * Initializes class variables.
     *
     * @param array $cfg
     * @param array $cfgClient
     * @param cDb $db CONTENIDO database object
     * @param int $clientId
     * @param string $area
     * @param int $frame
     * @throws cDbException|cException
     */
    public function __construct(array $cfg, array $cfgClient, cDb $db, $clientId, $area, $frame)
    {
        $this->aBodyData = [];
        $this->aRevisionFiles = [];
        $this->aCfg = $cfg;

        $this->aCfgClient = $cfgClient;

        $this->oDB = $db;
        $this->clientId = $clientId;
        $this->iRevisionNumber = 0;
        $this->area = $area;
        $this->frame = $frame;

        $this->dActualTimestamp = time();

        $this->aVarForm = [];

        self::$iDisplayNotification++;

        // Look if versioning is allowed, default is false
        if (function_exists('getEffectiveSetting')) {
            $this->bVersioningActive = getEffectiveSetting('versioning', 'activated', 'true');
            $this->sAlternativePath = getEffectiveSetting('versioning', 'path');

            if ($this->bVersioningActive == 'true') {
                $this->bVersioningActive = true;
            } else {
                $this->bVersioningActive = false;
            }
        } else {
            $this->bVersioningActive = true;
            $this->sAlternativePath = '';
        }

        if (!$this->bVersioningActive) {
            return;
        }

        if (!is_dir($this->sAlternativePath)) {
            // Alternative Path is not true or is not exist, we use the
            // frontendpath
            if ($this->sAlternativePath != '' and self::$iDisplayNotification < 2) {
                $oNotification = new cGuiNotification();
                $sNotification = i18n('Alternative path %s does not exist. Version was saved in frontendpath.');
                $oNotification->displayNotification('warning', sprintf($sNotification, $this->sAlternativePath));
            }

            $this->sAlternativePath = '';
        }

        // Look if versioning is set alternative path to save
        $this->checkPaths();
    }

    /**
     * This function looks if maximum number of stored versions is achieved.
     * If true, it will be deleted the first version.
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    protected function prune()
    {
        $this->initRevisions();
        $iLimit = cSecurity::toInteger(cEffectiveSetting::get('versioning', 'prune_limit', '0'));

        $bDelete = true;

        while (count($this->aRevisionFiles) >= $iLimit && $bDelete && $iLimit > 0) {
            $keys = array_keys($this->aRevisionFiles);
            $iIndex = end($keys);
            $bDelete = $this->deleteFile($this->getFirstRevision());
            unset($this->aRevisionFiles[$iIndex]);
        }
    }

    /**
     * This function checks if needed version paths exists and were created if necessary
     */
    protected function checkPaths()
    {
        $aPath = [
            '/',
            'css/',
            'js/',
            'layout/',
            'module/',
            'templates/'
        ];
        $sFrontEndPath = '';
        if ($this->sAlternativePath == '') {
            $sFrontEndPath = $this->aCfgClient[$this->clientId]['version']['path'];
        } else {
            $sFrontEndPath = $this->sAlternativePath . '/' . $this->clientId . '/';
        }

        foreach ($aPath as $sSubPath) {
            if (!is_dir($sFrontEndPath . $sSubPath)) {
                mkdir($sFrontEndPath . $sSubPath, cDirHandler::getDefaultPermissions());
                @chmod($sFrontEndPath . $sSubPath, cDirHandler::getDefaultPermissions());
            }
        }
    }

    /**
     * This function initialize the body node of xml file
     *
     * @param string $key
     * @param string $value
     */
    public function setData($key, $value)
    {
        $this->aBodyData[$key] = $value;
    }

    /**
     * This function creates a xml file.
     * XML Writer helps for create this file.
     *
     * @param string $sDirectory
     * @param string $sFileName name of xml file to create
     * @return bool true if saving file was successful, otherwise false
     * @throws cException|DOMException
     */
    public function createNewXml(string $sDirectory, string $sFileName): bool
    {
        $oWriter = new cXmlWriter();
        $oWriter->getDomDocument()->formatOutput = true;
        $oRootElement = $oWriter->addElement('version', '', NULL, [
            'xml:lang' => 'de'
        ]);
        $oHeadElement = $oWriter->addElement('head', '', $oRootElement);

        $oWriter->addElement('version_id', $this->entityId . '_' . $this->revision, $oHeadElement);
        $oWriter->addElement('type', $this->sType, $oHeadElement);
        $oWriter->addElement('date', date('Y-m-d H:i:s'), $oHeadElement);
        $oWriter->addElement('author', $this->sAuthor, $oHeadElement);
        $oWriter->addElement('client', $this->clientId, $oHeadElement);
        $oWriter->addElement('created', $this->dCreated, $oHeadElement);
        $oWriter->addElement('lastmodified', $this->dLastModified, $oHeadElement);

        $oBodyElement = $oWriter->addElement('body', '', $oRootElement);
        foreach ($this->aBodyData as $key => $value) {
            $oWriter->addElement($key, $value, $oBodyElement, [], true);
        }

        return $oWriter->saveToFile($sDirectory, $sFileName);
    }

    /**
     * This function creates new version in right folder.
     *
     * @throws cException|DOMException if new version could not be created
     */
    public function createNewVersion(): bool
    {
        if (!$this->bVersioningActive) {
            return false;
        }

        // get version name
        $sRevisionName = $this->getRevision();

        if (!is_dir($this->getFilePath())) {
            mkdir($this->getFilePath(), cDirHandler::getDefaultPermissions());
            @chmod($this->getFilePath(), cDirHandler::getDefaultPermissions());
        }

        // Create xml version file
        $bCreate = $this->createNewXml($this->getFilePath(), $sRevisionName . '.xml');
        if (!$bCreate) {
            throw new cException('Could not create new version.');
        }

        return $bCreate;
    }

    /**
     * This function inits version files.
     * Its filter also timestamp and version files
     */
    protected function initRevisions()
    {
        $this->aRevisionFiles = [];
        $this->dTimestamp = [];

        // Open this Filepath and read then the content.
        $sDir = $this->getFilePath();
        if (is_dir($sDir)) {
            if (false !== ($handle = cDirHandler::read($sDir))) {
                foreach ($handle as $file) {
                    if (false === cFileHandler::fileNameIsDot($file)) {
                        $aData = explode('.', $file);
                        $aValues = explode('_', $aData[0]);
                        if ($aValues[0] > $this->iRevisionNumber) {
                            $this->iRevisionNumber = cSecurity::toInteger($aValues[0]);
                        }

                        $this->dTimestamp[$aValues[0]] = $aValues[1];
                        $this->aRevisionFiles[$aValues[0]] = $file;
                    }
                }
            }
        }

        krsort($this->aRevisionFiles);
    }

    /**
     * This function deletes files and the folder, for given path.
     *
     * @param string $sFirstFile [optional]
     * @return bool Return true if successful
     * @throws cInvalidArgumentException
     */
    public function deleteFile(string $sFirstFile = ''): bool
    {
        // Open this Filepath and read then the content.
        $sDir = $this->getFilePath();

        $bDelete = true;
        if (is_dir($sDir) and $sFirstFile == '') {
            if (false !== ($handle = cDirHandler::read($sDir))) {
                foreach ($handle as $sFile) {
                    if (false === cFileHandler::fileNameIsDot($sFile)) {
                        // Delete the files
                        if (false === cFileHandler::remove($sDir . $sFile)) {
                            $bDelete = false;
                        }
                    }
                }
                // if the files be cleared, the delete the folder
                if (true === $bDelete) {
                    $bDelete = cDirHandler::remove($sDir);
                }
            }
        } elseif ($sFirstFile != '') {
            $bDelete = cFileHandler::remove($sDir . $sFirstFile);
        }

        return $bDelete;
    }

    /**
     * Get the frontendpath to revision
     */
    public function getFilePath(): string
    {
        if ($this->sAlternativePath == '') {
            $sFrontEndPath = $this->aCfgClient[$this->clientId]['version']['path'];
        } else {
            $sFrontEndPath = $this->sAlternativePath . '/' . $this->clientId . '/';
        }
        return $sFrontEndPath . $this->sType . '/' . $this->entityId . '/';
    }

    /**
     * Get the last revision file
     */
    public function getLastRevision(): array
    {
        return reset($this->aRevisionFiles);
    }

    /**
     * Makes new and init revision name and returns it back
     */
    private function getRevision(): string
    {
        $this->revision = ($this->iRevisionNumber + 1) . '_' . $this->dActualTimestamp;
        return $this->revision;
    }

    /**
     * Inits the first element of revision files and returns it back
     */
    protected function getFirstRevision(): string
    {
        $this->initRevisions();
        $aKey = $this->aRevisionFiles;
        $sFirstRevision = '';

        // to take first element, we use right sort
        ksort($aKey);
        foreach ($aKey as $value) {
            return $sFirstRevision = $value;
        }
        return $sFirstRevision;
    }

    /**
     * Revision files
     */
    public function getRevisionFiles(): array
    {
        return $this->aRevisionFiles;
    }

    /**
     * This function generate version names for select-box
     *
     * @return array Returns an array of revision file names
     */
    public function getFormatTimestamp(): array
    {
        $aTimes = [];
        if (count($this->dTimestamp) > 0) {
            krsort($this->dTimestamp);
            foreach ($this->dTimestamp as $iKey => $sTimeValue) {
                $aTimes[$this->aRevisionFiles[$iKey]] = date('d.m.Y H:i:s', $sTimeValue) . ' - Revision: ' . $iKey;
            }
        }

        return $aTimes;
    }

    /**
     * This function generate version names for select-box
     *
     * @param string $key
     * @param string $value
     */
    public function setVarForm($key, $value)
    {
        $this->aVarForm[$key] = $value;
    }

    /**
     * The general SelectBox function for get revision.
     *
     * @param string $formName The name of Table_Form class
     * @param string $formHeader The Header Label of SelectBox Widget
     * @param string $label The Label of SelectBox Widget
     * @param string $id Id of Select Box
     * @param bool $disabled [optional] If true, show disabled buttons for deleting
     * @return string If it exists Revision, then returns HTML Code of full SelectBox
     *      else returns empty string
     * @throws cInvalidArgumentException|cException
     */
    public function buildSelectBox(
        string $formName,
        string $formHeader,
        string $label,
        string $id,
        bool $disabled = false
    ): string {
        $oForm = new cGuiTableForm($formName);

        // if exists xml files
        if (count($this->dTimestamp) > 0) {
            foreach ($this->aVarForm as $key => $value) {
                $oForm->setVar($key, $value);
            }
            $aMessage = $this->getMessages();
            $oForm->setHeader(i18n($formHeader));
            $oForm->add(i18n($label), $this->getSelectBox($this->getFormatTimestamp(), $id));
            $oForm->setActionButton('clearhistory', 'images/delete' . ($disabled ? '_inact' : '') . '.gif', $aMessage['alt'], 'c', 'history_truncate');
            if (!$disabled) {
                $oForm->setConfirm('clearhistory', $aMessage['alt'], $aMessage['popup']);
            }
            $oForm->setActionButton('submit', 'images/but_refresh.gif', i18n('Refresh'), 's');
            $oForm->setTableClass('generic mgb10');

            return $oForm->render();
        } else {
            return '';
        }
    }

    /**
     * Messagebox for build selectBox.
     * Dynamic allocation for type.
     *
     * @return array the attributes alt and popup returns
     * @throws cException
     */
    private function getMessages(): array
    {
        $aMessage = [];
        switch ($this->sType) {
            case 'layout':
                $aMessage['alt'] = i18n('Clear layout history');
                $aMessage['popup'] = i18n('Do you really want to clear layout history?') . '<br><br>' . i18n('Note: This only affects the current layout.');
                break;
            case 'module':
                $aMessage['alt'] = i18n('Clear module history');
                $aMessage['popup'] = i18n('Do you really want to clear module history?') . '<br><br>' . i18n('Note: This only affects the current module.');
                break;
            case 'css':
                $aMessage['alt'] = i18n('Clear style history');
                $aMessage['popup'] = i18n('Do you really want to clear style history?') . '<br><br>' . i18n('Note: This only affects the current style.');
                break;
            case 'js':
                $aMessage['alt'] = i18n('Clear Java-Script history');
                $aMessage['popup'] = i18n('Do you really want to clear Java-Script history?') . '<br><br>' . i18n('Note: This only affects the current JavaScript.');
                break;
            case 'templates':
                $aMessage['alt'] = i18n('Clear HTML template history');
                $aMessage['popup'] = i18n('Do you really want to clear HTML template history?') . '<br><br>' . i18n('Note: This only the affects current HTML template.');
                break;
            default:
                $aMessage['alt'] = i18n('Clear history');
                $aMessage['popup'] = i18n('Do you really want to clear history?') . '<br><br>' . i18n('Note: This only affects the current history.');
                break;
        }
        return $aMessage;
    }

    /**
     * Renders select box for selecting file revision.
     *
     * @param array $aTempVesions List of versions
     * @param string $idOfSelectBox Select box id
     * @return string Returns rendered select-box with filled files
     */
    private function getSelectBox($aTempVesions, $idOfSelectBox): string
    {
        $sSelected = $_POST[$idOfSelectBox] ?? '';
        $oSelectMenu = new cHTMLSelectElement($idOfSelectBox);
        $oSelectMenu->autoFill($aTempVesions);

        if ($sSelected != '') {
            $oSelectMenu->setDefault($sSelected);
        }

        return $oSelectMenu->render();
    }

    /**
     * Build new Textarea with below parameters
     *
     * @param string $name The name of Textarea.
     * @param string $value The value of Input Textarea
     * @param int $width width of Textarea
     * @param int $height height of Textarea
     * @param string $id [optional]
     * @param bool $disabled [optional] Disabled Textarea
     * @return string HTML Code of Textarea
     */
    public function getTextarea($name, $value, $width, $height, $id = '', bool $disabled = false): string
    {
        if ($id != '') {
            $oHTMLTextarea = new cHTMLTextarea($name, $value, $width, $height, $id);
        } else {
            $oHTMLTextarea = new cHTMLTextarea($name, $value, $width, $height);
        }

        if ($disabled) {
            $oHTMLTextarea->setDisabled(true);
        }

        $oHTMLTextarea->setStyle('font-family: monospace; width: 100%;');
        $oHTMLTextarea->updateAttributes([
            'wrap' => 'off'
        ]);

        return $oHTMLTextarea->render();
    }

    /**
     * Build new text field with below parameters
     *
     * @param string $name The name of Input text field.
     * @param string $value The value of Input text field
     * @param int $width width of Input text field
     * @param bool $bDisabled [optional] Disabled TextBox
     * @return string HTML Code of Input text field
     */
    public function getTextBox($name, $value, $width, $bDisabled = false): string
    {
        $oHTMLTextbox = new cHTMLTextbox($name, conHtmlEntityDecode($value), $width, 0, 0, $bDisabled);
        $oHTMLTextbox->setStyle('font-family:monospace; width:100%;');
        $oHTMLTextbox->updateAttributes([
            'wrap' => 'off'
        ]);

        return $oHTMLTextbox->render();
    }

    /**
     * Displays your notification
     *
     * @param string $sOutPut
     */
    public function displayNotification($sOutPut)
    {
        if ($sOutPut != '') {
            print $sOutPut;
        }
    }

    /**
     * Set new node for xml file of description
     *
     * @param string $sDesc Content of node
     */
    public function setBodyNodeDescription($sDesc)
    {
        if ($sDesc != '') {
            $this->sDescription = conHtmlentities($sDesc);
            $this->setData('description', $this->sDescription);
        }
    }

}
