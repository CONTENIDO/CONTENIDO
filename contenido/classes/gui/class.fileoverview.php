<?php

/**
 * This file contains the generic file overviewer class.
 *
 * @package Core
 * @subpackage GUI
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Displays files in the left bottom frame
 * @package Core
 * @subpackage GUI
 */
class cGuiFileOverview extends cGuiPage {

    /**
     * Path to the directory that is being displayed
     * @var string
     */
    protected $directory;

    /**
     * Name of the marked up file
     * @var string
     */
    protected $markedFile;

    /**
     * Type of the file information in the database
     * @var string
     */
    protected $fileInfoType;

    /**
     * Selected file extension
     * @var string
     */
    protected $fileExtension;

    /**
     * Default constructor. Initializes the class for the directory
     * @param string $dir
     * @param string $markedFile [optional]
     * @param string $fileInfoType [optional]
     */
    public function __construct($dir, $markedFile = '', $fileInfoType = '') {
        parent::__construct('generic_file_overview');

        // assign properties
        $this->directory = $dir;
        $this->markedFile = $markedFile;
        $this->fileInfoType = $fileInfoType;
    }

    /**
     * Display only special file extensions
     *
     * @param array|string $extension
     *         Name of extensions
     */
    public function setFileExtension($extension) {
        if (cSecurity::isString($extension)) {
            $extension = array($extension);
        }
        $this->fileExtension = $extension;
    }

    /**
     * Renders the page
     * @see cGuiPage::render()
     */
    public function render() {
        global $area, $cfg, $perm;

        // create an array of all files in the directory
        $files = array();
        foreach (new DirectoryIterator($this->directory) as $file) {
            if ($file->isDir()) {
                continue;
            }
            if (!empty($this->fileExtension) && !in_array($file->getExtension(), $this->fileExtension)) {
                continue;
            }
            $files[] = $file->getBasename();
        }

        // sort the files
        sort($files);

        // assign variables for the JavaScript
        $this->set('s', 'JS_AREA', $area);
        $this->set('s', 'JS_ACTION_DELETE', $area . '_delete');

        // assign variables for every file
        $fileInfos = new cApiFileInformationCollection();
        foreach($files as $file) {
            if($this->fileInfoType != '') {
                $fileInfo = $fileInfos->getFileInformation($file, $this->fileInfoTyp);
                $this->set('d', 'DESCRIPTION', $fileInfo['description']);
            } else {
                $this->set('d', 'DESCRIPTION', '');
            }
            $this->set('d', 'AREA', $area);
            $this->set('d', 'ACTION', $area . '_edit');
            $this->set('d', 'FILENAME', $file);
            if($file == $this->markedFile) {
                $this->set('d', 'MARKED', 'marked');
            } else {
                $this->set('d', 'MARKED', '');
            }
            if(getEffectiveSetting("client", "readonly", "false") == "true" || (!$perm->have_perm_area_action($area, $area . "_delete"))) {
                $this->set('d', 'DELETE_IMAGE', $cfg['path']['images'] . 'delete_inact.gif');
            } else {
                $this->set('d', 'DELETE_IMAGE', $cfg['path']['images'] . 'delete.gif');
            }

            $this->next();
        }

        // call the render method of cGuiPage to display the webpage
        parent::render();
    }

}
