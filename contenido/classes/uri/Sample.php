<?php

/**
 * This file contains some sample scripts how to use the Uri
 * and uri builder classes.
 *
 * @package    Core
 * @subpackage Frontend_URI
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

// build a front_content.php URL
try {
    $aParams = array(
        'idcat' => 1,
        'idart' => 5
    );
    $oUriBuilder = cUriBuilderFactory::getUriBuilder('front_content');
    // needed if you need an absolute url
    $oUriBuilder->setHttpBasePath(cRegistry::getFrontendUrl());
    $oUriBuilder->buildUrl($aParams, true);
    echo $oUriBuilder->getUrl();
} catch (cInvalidArgumentException $e) {
    throw $e;
}

// build a URL with languagedependent category path
// like /path/path/path/index-b-1-2-3.html
try {
    $aParams = array(
        'idcat' => 1,
        'lang' => 1,
        'level' => 1,
        'b' => array(1, 2, 3)
    );
    $oUriBuilder = cUriBuilderFactory::getUriBuilder('custom_path');
    $oUriBuilder->buildUrl($aParams);
    echo $oUriBuilder->getUrl();
} catch (cInvalidArgumentException $e) {
    throw $e;
}

// build a URL with languagedependent category path
// like /path/path/path/rocknroll,goodies,1,2,3.4fb
try {
    $aParams = array(
        'idcat' => 1,
        'lang' => 1,
        'level' => 1,
        'goodies' => array(1, 2, 3)
    );
    $aConfig = array(
        'prefix' => 'rocknroll',
        'suffix' => '.4fb',
        'separator' => ','
    );
    $oUriBuilder = cUriBuilderFactory::getUriBuilder('custom_path');
    $oUriBuilder->buildUrl($aParams, false, $aConfig);
    echo $oUriBuilder->getUrl();
} catch (cInvalidArgumentException $e) {
    throw $e;
}
