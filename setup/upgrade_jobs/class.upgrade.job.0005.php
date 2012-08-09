<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Runs the upgrade job for ...
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Setup upgrade
 * @version    0.1
 * @author     Murat Purc <murat@purc>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9
 */


if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}


class cUpgradeJob_0005 extends cUpgradeJobAbstract {

    public function execute() {
        global $cfg;

        if ($this->_setupType == 'upgrade') {
            // add the column "class" to the con_type table if it does not already exist
            $classColumnExists = false;
            $sql = 'SHOW COLUMNS FROM `' . $cfg['tab']['type'] . '`';
            $this->_oDb->query($sql);
            while ($this->_oDb->next_record()) {
                if ($this->_oDb->f('Field') == 'class') {
                    $classColumnExists = true;
                }
            }
            if ($classColumnExists) {
                // if class column already exists, replace CMS_IMAGE with CMS_IMGEDITOR
                $sql = 'UPDATE `' . $cfg['tab']['type'] . '` SET `type`=\'CMS_IMGEDITOR\' WHERE `type`=\'CMS_IMAGE\'';
                $this->_oDb->query($sql);
            } else {
                $this->_cTypeAddClassColumm();
            }
        }
    }

    /**
     * Adds the "class" column to the con_type table and inserts the according data.
     */
    protected function _cTypeAddClassColumm() {
        global $cfg;

        $sql = 'ALTER TABLE `' . $cfg['tab']['type'] . '` ADD COLUMN `class` varchar(255)';
        $this->_oDb->query($sql);
        $classNames = array(
            'CMS_HTMLHEAD' => 'cContentTypeHtmlhead',
            'CMS_HTML' => 'cContentTypeHtml',
            'CMS_TEXT' => 'cContentTypeText',
            'CMS_IMG' => 'cContentTypeImg',
            'CMS_IMGDESCR' => 'cContentTypeImgdescr',
            'CMS_LINK' => 'cContentTypeLink',
            'CMS_LINKTARGET' => 'cContentTypeLinktarget',
            'CMS_LINKDESCR' => 'cContentTypeLinkdescr',
            'CMS_HEAD' => 'cContentTypeHead',
            'CMS_SWF' => 'cContentTypeSwf',
            'CMS_LINKTITLE' => 'cContentTypeLinkTitle',
            'CMS_LINKEDIT' => 'cContentTypeLinkEdit',
            'CMS_RAWLINK' => 'cContentTypeRawLink',
            'CMS_IMGEDIT' => 'cContentTypeImgEdit',
            'CMS_IMGTITLE' => 'cContentTypeImgTitle',
            'CMS_SIMPLELINKEDIT' => 'cContentTypeSimpleLinkEdit',
            'CMS_HTMLTEXT' => 'cContentTypeHtmlText',
            'CMS_EASYIMGEDIT' => 'cContentTypeEasyImgEdit',
            'CMS_DATE' => 'cContentTypeDate',
            'CMS_TEASER' => 'cContentTypeTeaser',
            'CMS_FILELIST' => 'cContentTypeFilelist',
            'CMS_IMGEDITOR' => 'cContentTypeImgeditor',
            'CMS_LINKEDITOR' => 'cContentTypeLinkeditor'
        );
        foreach ($classNames as $type => $class) {
            $sql = 'UPDATE `' . $cfg['tab']['type'] . '` SET `class`=\'' . $class . '\' WHERE `type`=\'' . $type . '\'';
            $this->_oDb->query($sql);
        }
    }

}