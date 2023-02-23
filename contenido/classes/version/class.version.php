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
class cVersion {

    /**
     * Id of Type
     *
     * @var string
     */
    protected $sType;

    /**
     * md5 coded name of author
     *
     * @var string
     */
    protected $sAuthor;

    /**
     * Time of created
     *
     * @var string (Date)
     */
    protected $dCreated;

    /**
     * Time of last modified
     *
     * @var string (Date)
     */
    protected $dLastModified;

    /**
     * Body data of xml file
     *
     * @var string
     */
    protected $aBodyData;

    /**
     * For init global variable
     *
     * @var array
     */
    protected $aCfg;

    /**
     * For init global variable $cfgClient
     *
     * @var array
     */
    protected $aCfgClient;

    /**
     * CONTENIDO database object
     *
     * @var cDb
     */
    protected $oDB;

    /**
     * For init global variable $client
     *
     * @var int
     */
    protected $iClient;

    /**
     * Revision files of current file
     *
     * @var array
     */
    public $aRevisionFiles;

    /**
     * Number of Revision
     *
     * @var int
     */
    protected $iRevisionNumber;

    /**
     * Timestamp
     *
     * @var array
     */
    protected $dTimestamp;

    /**
     * For init global variable $area
     *
     * @var string
     */
    protected $sArea;

    /**
     * For init global variable $frame
     *
     * @var int
     */
    protected $iFrame;

    /**
     * For init variables
     *
     * @var array
     */
    protected $aVarForm;

    /**
     * Identity the id of Content Type
     *
     * @var int
     */
    protected $iIdentity;

    /**
     * @var string
     */
    protected $sDescription;

    /**
     * @var string
     */
    protected $iVersion;

    /**
     * To take control versioning is switched off
     *
     * @var bool
     */
    private $bVersioningActive;

    /**
     * Timestamp
     *
     * @var int
     */
    protected $dActualTimestamp;

    /**
     * Alternative Path for save version files
     *
     * @var string
     */
    protected $sAlternativePath;

    /**
     * Displays Notification only onetime per object
     *
     * @var int
     */
    public static $iDisplayNotification;

    /**
     * Constructor to create an instance of this class.
     *
     * Initializes class variables.
     *
     * @param array $aCfg
     * @param array $aCfgClient
     * @param cDb $oDB
     *         CONTENIDO database object
     * @param int $iClient
     * @param string $sArea
     * @param int $iFrame

     * @throws cDbException|cException
     */
    public function __construct($aCfg, $aCfgClient, $oDB, $iClient, $sArea, $iFrame) {
        $this->aBodyData = [];
        $this->aRevisionFiles = [];
        $this->aCfg = $aCfg;

        $this->aCfgClient = $aCfgClient;

        $this->oDB = $oDB;
        $this->iClient = $iClient;
        $this->iRevisionNumber = 0;
        $this->sArea = $sArea;
        $this->iFrame = $iFrame;

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
    protected function prune() {
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
     * This function checks if needed version paths exists and were created if
     * necessary
     */
    protected function checkPaths() {
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
            $sFrontEndPath = $this->aCfgClient[$this->iClient]['version']['path'];
        } else {
            $sFrontEndPath = $this->sAlternativePath . '/' . $this->iClient . '/';
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
     * @param string $sKey
     * @param string $sValue
     */
    public function setData($sKey, $sValue) {
        $this->aBodyData[$sKey] = $sValue;
    }

    /**
     * This function creates a xml file.
     * XML Writer helps for create this file.
     *
     * @param string $sDirectory
     * @param string $sFileName
     *         name of xml file to create
     *
     * @return bool
     *         true if saving file was successful, otherwise false
     * @throws cException
     */
    public function createNewXml($sDirectory, $sFileName) {
        $oWriter = new cXmlWriter();
        $oRootElement = $oWriter->addElement('version', '', NULL, [
            'xml:lang' => 'de'
        ]);
        $oHeadElement = $oWriter->addElement('head', '', $oRootElement);

        $oWriter->addElement('version_id', $this->iIdentity . '_' . $this->iVersion, $oHeadElement);
        $oWriter->addElement('type', $this->sType, $oHeadElement);
        $oWriter->addElement('date', date('Y-m-d H:i:s'), $oHeadElement);
        $oWriter->addElement('author', $this->sAuthor, $oHeadElement);
        $oWriter->addElement('client', $this->iClient, $oHeadElement);
        $oWriter->addElement('created', $this->dCreated, $oHeadElement);
        $oWriter->addElement('lastmodified', $this->dLastModified, $oHeadElement);

        $oBodyElement = $oWriter->addElement('body', '', $oRootElement);
        foreach ($this->aBodyData as $sKey => $sValue) {
            $oWriter->addElement($sKey, $sValue, $oBodyElement, [], true);
        }

        return $oWriter->saveToFile($sDirectory, $sFileName);
    }

    /**
     * This function creates new version in right folder.
     *
     * @throws cException
     *         if new version could not be created
     * @return bool
     */
    public function createNewVersion() {
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
    protected function initRevisions() {
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
                            $this->iRevisionNumber = $aValues[0];
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
     * @return bool
     *                           return true if successful
     * @throws cInvalidArgumentException
     */
    public function deleteFile($sFirstFile = '') {
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
        if ($bDelete) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the frontendpath to revision
     *
     * @return string
     *         returns path to revision file
     */
    public function getFilePath() {
        if ($this->sAlternativePath == '') {
            $sFrontEndPath = $this->aCfgClient[$this->iClient]['version']['path'];
        } else {
            $sFrontEndPath = $this->sAlternativePath . '/' . $this->iClient . '/';
        }
        return $sFrontEndPath . $this->sType . '/' . $this->iIdentity . '/';
    }

    /**
     * Get the last revision file
     *
     * @return array
     *         returns Last Revision
     */
    public function getLastRevision() {
        return reset($this->aRevisionFiles);
    }

    /**
     * Makes new and init Revision Name
     *
     * @return int
     *         returns number of Revision File
     */
    private function getRevision() {
        $this->iVersion = ($this->iRevisionNumber + 1) . '_' . $this->dActualTimestamp;
        return $this->iVersion;
    }

    /**
     * Inits the first element of revision files
     *
     * @return string
     *         the name of xml files
     */
    protected function getFirstRevision() {
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
     * Revision Files
     *
     * @return array
     *         returns all Revision File
     */
    public function getRevisionFiles() {
        return $this->aRevisionFiles;
    }

    /**
     * This function generate version names for select-box
     *
     * @return array
     *         returns an array of revision file names
     */
    public function getFormatTimestamp() {
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
     * @param string $sKey
     * @param string $sValue
     */
    public function setVarForm($sKey, $sValue) {
        $this->aVarForm[$sKey] = $sValue;
    }

    /**
     * The general SelectBox function for get Revision.
     *
     * @param string $sTableForm
     *                         The name of Table_Form class
     * @param string $sAddHeader
     *                         The Header Label of SelectBox Widget
     * @param string $sLabelOfSelectBox
     *                         The Label of SelectBox Widget
     * @param string $sIdOfSelectBox
     *                         Id of Select Box
     * @param bool   $disabled [optional]
     *                         If true, show disabled buttons for deleting
     *
     * @return string
     *         if is exists Revision, then returns HTML Code of full SelectBox
     *         else returns empty string
     * @throws cInvalidArgumentException|cException
     */
    public function buildSelectBox($sTableForm, $sAddHeader, $sLabelOfSelectBox, $sIdOfSelectBox, $disabled = false) {
        $oForm = new cGuiTableForm($sTableForm);

        // if exists xml files
        if (count($this->dTimestamp) > 0) {
            foreach ($this->aVarForm as $sKey => $sValue) {
                $oForm->setVar($sKey, $sValue);
            }
            $aMessage = $this->getMessages();
            $oForm->addHeader(i18n($sAddHeader));
            $oForm->add(i18n($sLabelOfSelectBox), $this->getSelectBox($this->getFormatTimestamp(), $sIdOfSelectBox));
            $oForm->setActionButton('clearhistory', 'images/delete' . (($disabled) ? '_inact' : '') . '.gif', $aMessage['alt'], 'c', 'history_truncate');
            if (!$disabled) {
                $oForm->setConfirm('clearhistory', $aMessage['alt'], $aMessage['popup']);
            }
            $oForm->setActionButton('submit', 'images/but_refresh.gif', i18n('Refresh'), 's');
            $oForm->setTableID('version_selector');

            return $oForm->render();
        } else {
            return '';
        }
    }

    /**
     * Messagebox for build selectBox.
     * Dynamic allocation for type.
     *
     * @return array
     *         the attributes alt and popup returns
     */
    private function getMessages() {
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
     * @param array $aTempVesions
     *         List of versions
     * @param string $sIdOfSelectBox
     *         Select box id
     * @return string
     *         Returns rendered select-box with filled files
     */
    private function getSelectBox($aTempVesions, $sIdOfSelectBox) {
        $sSelected = $_POST[$sIdOfSelectBox] ?? '';
        $oSelectMenu = new cHTMLSelectElement($sIdOfSelectBox);
        $oSelectMenu->autoFill($aTempVesions);

        if ($sSelected != '') {
            $oSelectMenu->setDefault($sSelected);
        }

        return $oSelectMenu->render();
    }

    /**
     * Build new Textarea with below parameters
     *
     * @param string $sName
     *         The name of Textarea.
     * @param string $sInitValue
     *         The value of Input Textarea
     * @param int $iWidth
     *         width of Textarea
     * @param int $iHeight
     *         height of Textarea
     * @param string $sId [optional]
     * @param bool $disabled [optional]
     *         Disabled Textarea
     * @return string
     *         HTML Code of Textarea
     */
    public function getTextarea($sName, $sInitValue, $iWidth, $iHeight, $sId = '', $disabled = false) {
        if ($sId != '') {
            $oHTMLTextarea = new cHTMLTextarea($sName, $sInitValue, $iWidth, $iHeight, $sId);
        } else {
            $oHTMLTextarea = new cHTMLTextarea($sName, $sInitValue, $iWidth, $iHeight);
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
     * @param string $sName
     *         The name of Input text field.
     * @param string $sInitValue
     *         The value of Input text field
     * @param int $iWidth
     *         width of Input text field
     * @param bool $bDisabled [optional]
     *         Disabled TextBox
     * @return string
     *         HTML Code of Input text field
     */
    public function getTextBox($sName, $sInitValue, $iWidth, $bDisabled = false) {
        $oHTMLTextbox = new cHTMLTextbox($sName, conHtmlEntityDecode($sInitValue), $iWidth, '', '', $bDisabled);
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
    public function displayNotification($sOutPut) {
        if ($sOutPut != '') {
            print $sOutPut;
        }
    }

    /**
     * Set new node for xml file of description
     *
     * @param string $sDesc
     *         Content of node
     */
    public function setBodyNodeDescription($sDesc) {
        if ($sDesc != '') {
            $this->sDescription = conHtmlentities($sDesc);
            $this->setData('description', $this->sDescription);
        }
    }

}
