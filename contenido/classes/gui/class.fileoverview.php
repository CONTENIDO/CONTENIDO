<?php

/**
 * This file contains the generic file overview class.
 *
 * @package Core
 * @subpackage GUI
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * The class cGuiFileOverview is a cGuiPage displaying files.
 * It is meant to be used but in the left bottom frame.
 * As for now it is used to display HTML, CSS & JS files.
 *
 * <strong>Usage</strong>
 * <code>
 * // path to directory where files to display are located
 * $path = $cfgClient[$client]['tpl']['path'];
 * // basename of file to mark as selected
 * $mark = stripslashes($_REQUEST['file']);
 * // build page to display files
 * $page = new cGuiFileOverview($path, $mark, 'html');
 * // optionally set extension(s) to filter files
 * $page->setFileExtension(array('html', 'tpl'));
 * // render page
 * $page->render();
 * </code>
 *
 * <strong>Directory</strong>
 * Only files in the directory defined by the given path are displayed.
 *
 * <strong>Extension</strong>
 * If set via setFileExtension(array) only files of the given extensions
 * are displayed. By default all extensions are considered.
 *
 * <strong>Order of files</strong>
 * The files to be displayed are sorted alphabetically.
 *
 * <strong>Marking files</strong>
 * When initializing the class the name of a file to mark can be given.
 * This feature is totally optional.
 *
 * <strong>Additional file information</strong>
 * When initializing the class the name of a file information type to
 * display can be given.
 * This feature is totally optional.
 * @todo This feature does not work at the moment.
 *
 * <strong>Template</strong>
 * This class is bound to the template generic_file_overview in a
 * hardcoded manner (template,generic_file_overview.html).
 * @todo This prevents this class to be used with other views.
 *
 * <strong>Editing a file</strong>
 * When editing a file
 * <ul>
 * <li>the right top frame is opened with the URL:
 *      main.php
 *          ?area={AREA}
 *          &frame=3
 *          &file={FILENAME}
 *          &contenido=1
 * <li>whereas the right bottom frame is opened with the URL:
 *      main.php
 *          ?area={AREA}
 *          &frame=4
 *          &action={ACTION}
 *          &file={FILENAME}
 *          &tmp_file={FILENAME}
 *          &contenido=1
 * </ul>
 * AREA & ACTION are filled with the current global values.
 * FILENAME is the name of the current file.
 * @todo Why the parameter contenido is set to 1 is to be clarified.
 * IMHO it should be the current value of the global contenido variable.
 * @todo Why the parameter file and tmp_file are both set is to be clarified.
 *
 * <strong>Deleting a file</strong>
 * A delete icon is provided for each file.
 * When deleting a file
 * <ul>
 * <li>the right bottom frame is opened with the URL:
 *      main.php
 *          ?area={AREA}
 *          &action={ACTION}
 *          &frame=4
 *          &delfile={FILENAME}
 * </ul>
 * @todo Why the parameter contenido is not set is to be clarified.
 * @todo Why the URL is generated via JS is to be clarified.
 *
 * If the effective setting
 * client/readonly is "true" or the current user has no privileges for
 * the action $area . "_delete" of the current area this icon will be
 * inactive though.
 * @todo This prevents this class to be used in other areas.
 *
 * @package Core
 * @subpackage GUI
 */
class cGuiFileOverview extends cGuiPage {

    /**
     * Path to the directory directory where files to display are located.
     *
     * @var string
     */
    protected $_directory;

    /**
     * Basename of file that will be marked as selected.
     *
     * @var string
     */
    protected $_markedFile;

    /**
     * Type of additional file information that should be displayed as
     * description.
     *
     * @var string
     */
    protected $_fileInfoType;

    /**
     * Selected file extension.
     *
     * @var string
     */
    protected $_fileExtension;

    /**
     * Constructor to create an instance of this class.
     *
     * Initializes the class for the directory.
     *
     * @param string $dir
     *        path to the directory directory where files to display are
     *        located
     * @param string $markedFile [optional]
     *        basename of file that will be marked as selected.
     * @param string $fileInfoType [optional]
     *        type of additional file information that should be
     *        displayed as description
     */
    public function __construct($dir, $markedFile = '', $fileInfoType = '') {
        parent::__construct('generic_file_overview');

        // assign properties
        $this->_directory = $dir;
        $this->_markedFile = $markedFile;
        $this->_fileInfoType = $fileInfoType;
    }

    /**
     * Sets extension(s) to filter files that should be displayed.
     *
     * @param array|string $extension
     *         Name of extensions
     */
    public function setFileExtension($extension) {
        if (cSecurity::isString($extension)) {
            $extension = array($extension);
        }
        $this->_fileExtension = $extension;
    }

    /**
     * Renders the page
     *
     * @param cTemplate|null $template
     * @param bool $return
     */
    public function render($template = NULL, $return = false) {

        $cfg = cRegistry::getConfig();
        $area = cRegistry::getArea();
        $perm = cRegistry::getPerm();

        // create an array of all files in the directory
        $files = array();
        foreach (new DirectoryIterator($this->_directory) as $file) {
            if ($file->isDir()) {
                continue;
            }
            if (!empty($this->_fileExtension) && !in_array($file->getExtension(), $this->_fileExtension)) {
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
            if($this->_fileInfoType != '') {
                $fileInfo = $fileInfos->getFileInformation($file, $this->_fileInfoType);
                $this->set('d', 'DESCRIPTION', conHtmlSpecialChars($fileInfo['description']));
            } else {
                $this->set('d', 'DESCRIPTION', '');
            }
            $this->set('d', 'AREA', $area);
            $this->set('d', 'ACTION', $area . '_edit');
            $this->set('d', 'FILENAME', $file);
            if($file == $this->_markedFile) {
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
