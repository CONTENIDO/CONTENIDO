<?php
/**
 * description: check for rights for the file
 *
 * @package Module
 * @subpackage content_file_security
 * @author alexander.scheider@4fb.de
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');
// cInclude('classes', 'class.cziparchive.php');
// cInclude('frontend', 'classes/class.sort_dl_manager.php');

// frontend url
$path = $cfgClient[cRegistry::getClientId()]['htmlpath']['frontend'];

if (isset($_REQUEST['file'])) {
    performDownload();
}

if (isset($_REQUEST['files'])) {

    $idClient = cRegistry::getClientId();
    $uplPath = $cfgClient[cRegistry::getClientId()]['path']['frontend'] . 'upload/';

    $time = getdate(time());
    $fileNameZip = $time['hours'] . $time['minutes'] . $time['seconds'] . '_tmp' . '.zip';

    // build complete path from requested file
    foreach ($_REQUEST['files'] as $key => $file) {
        $filesToZip[] = $uplPath . $file;
    }

    $zip = new ZipArchive();
    $zip->open($uplPath . $fileNameZip, ZipArchive::OVERWRITE);
    $names = array();
    foreach ($filesToZip as $file) {
        // get basename of file
        $file_basename = pathinfo($file, PATHINFO_BASENAME);
        // handle name collision
        $counter = 1;
        while (in_array($file_basename, $names)) {
            $file_pathinfo = pathinfo($file);
            $file_filename = $file_pathinfo['filename'];
            $file_extension = $file_pathinfo['extension'];
            $file_basename = $file_filename . '_' . $counter . '.' . $file_extension;
        }
        $names[] = $file_basename;
        // get content and add to zip
        $file_content = file_get_contents($file);
        // TODO better use $this->_readfileChunked()
        $zip->addFromString($file_basename, $file_content);
    }
    $zip->close();

    // download link
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $path . "?idart=83&file=" . $fileNameZip);
}

function performDownload() {
    $file = $cfgClient[$client]['path']['frontend'] . 'upload/' . $_REQUEST['file'];

    $mimeType = mime_content_type($file);

    header('Content-type: ' . $mimeType . '');
    header('Content-disposition: attachment; filename="' . basename($file) . '"');
    header("Content-length: " . filesize($file));

    // output
    readfile_chunked($file);

    // delete temporary zip files : file name contains the suffix _tmp
    if (!strpos($file, '_tmp') === false) {
        unlink($file);
    }
}

/**
 * Reads file bytes
 *
 * @param string $filename
 * @return boolean
 */
function readfile_chunked($filename) {
    $chunksize = 1 * (1024 * 1024); // how many bytes per chunk
    $buffer = '';
    $handle = fopen($filename, 'rb');
    if ($handle === false) {
        return false;
    }
    while (!feof($handle)) {
        $buffer = fread($handle, $chunksize);
        print $buffer;
        ob_flush();
        flush();
    }
    return fclose($handle);
}

?>