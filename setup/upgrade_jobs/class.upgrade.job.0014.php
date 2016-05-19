<?php

/**
 * This file contains the upgrade job 14.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @author frederic.schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 14
 *
 * Switched AMR and url_shortener plugins from "Content" navigation to "Extras" navigation
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0014 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.4";

    public function _execute() {
        $cfg = cRegistry::getConfig();

        if ($_SESSION['setuptype'] == 'upgrade') {

            // Initializing cApiNavSub
            $navsub = new cApiNavSub();

            // mod_rewrite/main
            // Get informations for mod_rewrite/xml/;navigation/content/mod_rewrite/main
            $navsub->loadBy('location', 'mod_rewrite/xml/;navigation/content/mod_rewrite/main');

            // If entry exist, change location to mod_rewrite/xml/;navigation/extra/mod_rewrite/main
            if ($navsub !== null) {
            	$navsub->set('location', 'mod_rewrite/xml/;navigation/extra/mod_rewrite/main');
            	$navsub->store();
            }

            // mod_rewrite/settings
            // Get informations for mod_rewrite/xml/;navigation/content/mod_rewrite/settings
            $navsub->loadBy('location', 'mod_rewrite/xml/;navigation/content/mod_rewrite/settings');

            // If entry exist, change location to mod_rewrite/xml/;navigation/extra/mod_rewrite/settings
            if ($navsub !== null) {
            	$navsub->set('location', 'mod_rewrite/xml/;navigation/extra/mod_rewrite/settings');
            	$navsub->store();
            }

            // mod_rewrite/expert
            // Get informations for mod_rewrite/xml/;navigation/content/mod_rewrite/expert
            $navsub->loadBy('location', 'mod_rewrite/xml/;navigation/content/mod_rewrite/expert');

            // If entry exist, change location to mod_rewrite/xml/;navigation/extra/mod_rewrite/expert
            if ($navsub !== null) {
            	$navsub->set('location', 'mod_rewrite/xml/;navigation/extra/mod_rewrite/expert');
            	$navsub->store();
            }

            // mod_rewrite/test
            // Get informations for mod_rewrite/xml/;navigation/content/mod_rewrite/test
            $navsub->loadBy('location', 'mod_rewrite/xml/;navigation/content/mod_rewrite/test');

            // If entry exist, change location to mod_rewrite/xml/;navigation/extra/mod_rewrite/test
            if ($navsub !== null) {
            	$navsub->set('location', 'mod_rewrite/xml/;navigation/extra/mod_rewrite/test');
            	$navsub->store();
            }

            // url_shortener/main
            // Get informations for url_shortener/xml/;navigation/content/url_shortener/main
            $navsub->loadBy('location', 'url_shortener/xml/;navigation/content/url_shortener/main');

            // If entry exist, change location to url_shortener/xml/;navigation/extra/url_shortener/main
            if ($navsub !== null) {
            	$navsub->set('location', 'url_shortener/xml/;navigation/extra/url_shortener/main');
            	$navsub->store();
            }

            // mod_rewrite
            // Get informations for mod_rewrite/xml;navigation/content/mod_rewrite
            $navsub->loadBy('location', 'mod_rewrite/xml/;navigation/content/mod_rewrite');

            // If entry exist, please delete it
            if ($navsub !== null) {
                $navsubColl = new cApiNavSubCollection();
                $navsubColl->deleteByWhereClause("location = 'mod_rewrite/xml/;navigation/content/mod_rewrite'");
            }

        }
    }

}

