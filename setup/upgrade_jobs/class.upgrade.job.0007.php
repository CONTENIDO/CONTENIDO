<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Runs the upgrade job to remove unused mail logging include files from the DB.
 * Additionally, the used include file is renamed.
 * Besides, the column idmail_resend is removed from the con_mail_log_success
 * table.
 *
 * @package CONTENIDO Setup upgrade
 * @version 0.1
 * @author Simon Sprankel <simon.sprankel@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release 4.9
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
class cUpgradeJob_0007 extends cUpgradeJobAbstract {

    public function execute() {
        global $cfg, $db;

        if ($this->_setupType == 'upgrade') {
            // get the IDs of the mail log areas
            $areaItem = new cApiArea();
            $areaItem->loadByMany(array(
                'parent_id' => '0',
                'name' => 'mail_log'
            ));
            $mainIdarea = $areaItem->get('idarea');
            $areaItem->loadByMany(array(
                'parent_id' => 'mail_log',
                'name' => 'mail_log_overview'
            ));
            $overviewIdarea = $areaItem->get('idarea');
            $areaItem->loadByMany(array(
                'parent_id' => 'mail_log',
                'name' => 'mail_log_detail'
            ));
            $detailIdarea = $areaItem->get('idarea');

            // delete the unused mail log include files and rename the used ones
            $fileCollection = new cApiFileCollection();
            $file = new cApiFile();

            // delete the include.mail_log.left_bottom.php entry
            $file->loadByMany(array(
                'idarea' => $mainIdarea,
                'filename' => 'include.mail_log.left_bottom.php'
            ));
            if ($file->isLoaded()) {
                $fileCollection->delete($file->get('idfile'));
            }

            // delete the include.mail_log.subnav.php entry
            $file->loadByMany(array(
                'idarea' => $mainIdarea,
                'filename' => 'include.mail_log_subnav.php'
            ));
            if ($file->isLoaded()) {
                $fileCollection->delete($file->get('idfile'));
            }

            // rename the include.mail_log.right_bottom.php entries to
            // include.mail_log.php
            $file->loadByMany(array(
                'idarea' => $mainIdarea,
                'filename' => 'include.mail_log.right_bottom.php'
            ));
            if ($file->isLoaded()) {
                $file->set('filename', 'include.mail_log.php');
                $file->store();
            }
            $file->loadByMany(array(
                'idarea' => $overviewIdarea,
                'filename' => 'include.mail_log.right_bottom.php'
            ));
            if ($file->isLoaded()) {
                $file->set('filename', 'include.mail_log.php');
                $file->store();
            }
            $file->loadByMany(array(
                'idarea' => $detailIdarea,
                'filename' => 'include.mail_log.right_bottom.php'
            ));
            if ($file->isLoaded()) {
                $file->set('filename', 'include.mail_log.php');
                $file->store();
            }

            // remove the column idmail_resend from the table
            // con_mail_log_success if it exists
            $columns = array();
            $sql = 'SHOW COLUMNS FROM ' . $cfg['tab']['mail_log_success'];
            $db->query($sql);
            while ($db->next_record()) {
                $columns[] = $db->f('Field');
            }
            if (in_array('idmail_resend', $columns)) {
                $sql = 'ALTER TABLE `' . $cfg['tab']['mail_log_success'] . '` DROP `idmail_resend`';
                $db->query($sql);
            }
        }
    }

}
