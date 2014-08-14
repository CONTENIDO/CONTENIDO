<?php
/**
 * This file contains the upgrade job 16.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @version SVN Revision $Rev:$
 *
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 16.
 * Updates some tables which uses old slashes-handling
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0016 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.5";

    public function _execute() {
        global $db, $cfg;

        if ($_SESSION['setuptype'] == 'upgrade') {

			// GROUPS
			$groupColl = new cApiGroupCollection();

            // Get all created groups
            $groupColl->select();

            while ($group = $groupColl->next()) {

				$groupname = stripcslashes(preg_replace("/\"/", "", ($group->get('groupname'))));
				$description = stripcslashes(preg_replace("/\"/", "", ($group->get('description'))));
				
				$group->set('groupname', $groupname);
				$group->set('description', $description);

				// Update group
                $group->store();

			}
	
			// METATAGS
			$metaColl = new cApiMetaTagCollection();
			
			// Get all created metatags
            $metaColl->select();

            while ($meta = $metaColl->next()) {

				$metavalue = stripcslashes(preg_replace("/\"/", "", ($meta->get('metavalue'))));
				$meta->set('metavalue', $metavalue);

				// Update metatags
                $meta->store();

			}
			
			// UPLOAD METATAGS
			$uplColl = new cApiUploadMetaCollection();
			
			// Get all created upload metatags
            $uplColl->select();

            while ($upl = $uplColl->next()) {

				$medianame = stripcslashes(preg_replace("/\"/", "", ($upl->get('medianame'))));
				$description = stripcslashes(preg_replace("/\"/", "", ($upl->get('description'))));				
				$keywords = stripcslashes(preg_replace("/\"/", "", ($upl->get('keywords'))));
				$internal_notice = stripcslashes(preg_replace("/\"/", "", ($upl->get('internal_notice'))));
				$copyright = stripcslashes(preg_replace("/\"/", "", ($upl->get('copyright'))));
				
				$upl->set('medianame', $medianame);
				$upl->set('description', $description);
				$upl->set('keywords', $keywords);
				$upl->set('internal_notice', $internal_notice);
				$upl->set('copyright', $copyright);

				// Update upload meta tags
                $upl->store();

			}
	
        }
    }

}
