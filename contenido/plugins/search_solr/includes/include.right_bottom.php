<?php

/**
 *
 * @package Plugin
 * @subpackage SearchSolr
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright four for business AG
 * @link https://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// create and render page
try {
    $page = new SolrRightBottomPage();
    $page->render();
} catch (Exception $e) {
    Solr::logException($e);
    echo Solr::notifyException($e);
}
