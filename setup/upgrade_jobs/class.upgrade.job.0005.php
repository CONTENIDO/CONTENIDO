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


class cUpgradeJob_0006 extends cUpgradeJobAbstract {

    public function execute() {
        global $cfg;

        $db = $this->_oDb;

        if ($this->_setupType == 'upgrade') {
            // map all content types to their IDs
            $types = array();
            $typeCollection = new cApiTypeCollection();
            $typeCollection->addResultField('idtype');
            $typeCollection->addResultField('type');
            $typeCollection->query();
            while (($typeItem = $typeCollection->next()) !== false) {
                $types[$typeItem->get('type')] = $typeItem->get('idtype');
            }

            /* Convert the value of each CMS_DATE entry.
             * Old:
             * 16.07.2012
             * New:
             * <?xml version="1.0" encoding="utf-8"?>
             * <date><timestamp>1342404000</timestamp><format>d.m.Y</format></date>
             */
            $contentCollection = new cApiContentCollection();
            $contentCollection->setWhere('idtype', $types['CMS_DATE']);
            $contentCollection->query();
            while (($item = $contentCollection->next()) !== false) {
                $idcontent = $item->get('idcontent');
                $oldValue = $item->get('value');
                // if the value has not the format dd.mm.yyyy, it is possibly the new format, so ignore it
                $oldValueSplitted = explode('.', $oldValue);
                if (count($oldValueSplitted) !== 3 || !checkdate($oldValueSplitted[1], $oldValueSplitted[0], $oldValueSplitted[2])) {
                    continue;
                }
                // value has the format dd.mm.yyyy, so convert it to the new XML structure
                $timestamp = strtotime($oldValue);
                $xml = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<date><timestamp>$timestamp</timestamp><format>d.m.Y</format></date>
EOT;
                $item->set('value', $xml);
                $item->store();
            }

            // Convert the value of each CMS_FILELIST entry.
            $contentCollection = new cApiContentCollection();
            $contentCollection->setWhere('idtype', $types['CMS_FILELIST']);
            $contentCollection->query();
            while (($item = $contentCollection->next()) !== false) {
                $oldFilelistVal = $item->get('value');
                $oldFilelistArray = cXmlBase::xmlStringToArray($oldFilelistVal);
                // convert the whole entries
                if (isset($oldFilelistArray['directories']['dir'])) {
                    $oldFilelistArray['directories'] = $oldFilelistArray['directories']['dir'];
                }
                if (isset($oldFilelistArray['incl_subdirectories'])) {
                    if ($oldFilelistArray['incl_subdirectories'] == 'checked') {
                        $oldFilelistArray['incl_subdirectories'] = 'true';
                    } else {
                        $oldFilelistArray['incl_subdirectories'] = 'false';
                    }
                }
                if (isset($oldFilelistArray['manual'])) {
                    if ($oldFilelistArray['manual'] == 'checked') {
                        $oldFilelistArray['manual'] = 'true';
                    } else {
                        $oldFilelistArray['manual'] = 'false';
                    }
                }
                if (isset($oldFilelistArray['incl_metadata'])) {
                    if ($oldFilelistArray['incl_metadata'] == 'checked') {
                        $oldFilelistArray['incl_metadata'] = 'true';
                    } else {
                        $oldFilelistArray['incl_metadata'] = 'false';
                    }
                }
                if (isset($oldFilelistArray['extensions']['ext'])) {
                    $oldFilelistArray['extensions'] = $oldFilelistArray['extensions']['ext'];
                }
                if (isset($oldFilelistArray['ignore_extensions'])) {
                    if ($oldFilelistArray['ignore_extensions'] == 'off') {
                        $oldFilelistArray['ignore_extensions'] = 'false';
                    } else {
                        $oldFilelistArray['ignore_extensions'] = 'true';
                    }
                }
                if (isset($oldFilelistArray['manual_files']['file'])) {
                    $oldFilelistArray['manual_files'] = $oldFilelistArray['manual_files']['file'];
                }
                $newFilelistVal = cXmlBase::arrayToXml($oldFilelistArray, null, 'filelist');
                $item->set('value', $newFilelistVal->asXML());
                $item->store();
            }

            /* Convert all DB entries CMS_IMG and CMS_IMGDESCR to CMS_IMGEDITOR.
             * Old:
             * In the past, CMS_IMG saved the idupl and CMS_IMGDESCR the corresponding description.
             *
             * New:
             * Since CONTENIDO 4.9, CMS_IMGEDITOR saves the idupl and the description is saved
             * in the con_upl_meta table.
             */
            $sql = 'SELECT `idcontent`, `idartlang`, `idtype`, `typeid`, `value` FROM `' . $cfg['tab']['content'] . '` WHERE `idtype`=' . $types['CMS_IMG'] . ' OR `idtype`=' . $types['CMS_IMGDESCR'] . ' ORDER BY `typeid` ASC';
            $db->query($sql);
            $result = array();
            while ($db->next_record()) {
                // create an array in which each entry contains the data needed for converting one entry
                $idartlang = $db->f('idartlang');
                $typeid = $db->f('typeid');
                $key = $idartlang . '_' . $typeid;
                if (isset($result[$key])) {
                    $subResult = $result[$key];
                } else {
                    $subResult = array();
                    $subResult['idartlang'] = $idartlang;
                }
                if ($db->f('idtype') == $types['CMS_IMG']) {
                    $subResult['idupl'] = $db->f('value');
                    $subResult['imgidcontent'] = $db->f('idcontent');
                } else if ($db->f('idtype') == $types['CMS_IMGDESCR']) {
                    $subResult['description'] = $db->f('value');
                    $subResult['imgdescridcontent'] = $db->f('idcontent');
                }
                $result[$key] = $subResult;
            }

            // iterate over all entries and convert each of them
            foreach ($result as $imageInfo) {
                // calculate the next unused typeid
                $sql = 'SELECT MAX(typeid) AS maxtypeid FROM `' . $cfg['tab']['content'] . '` WHERE `idartlang`=' . $imageInfo['idartlang'] . ' AND `idtype`=' . $types['CMS_IMGEDITOR'];
                $db->query($sql);
                $db->next_record();
                if ($db->f('maxtypeid') === false) {
                    $nextTypeId = 1;
                } else {
                    $nextTypeId = $db->f('maxtypeid') + 1;
                }
                // insert new CMS_IMGEDITOR content entry
                $contentCollection = new cApiContentCollection();
                $contentCollection->create($imageInfo['idartlang'], $types['CMS_IMGEDITOR'], $nextTypeId, $imageInfo['idupl'], '');
                // save description in con_upl_meta if it does not already exist
                $sql = 'SELECT `idlang` FROM `' . $cfg['tab']['art_lang'] . '` WHERE `idartlang`=' . $imageInfo['idartlang'];
                $db->query($sql);
                if ($db->next_record()) {
                    $idlang = $db->f('idlang');
                    $metaItem = new cApiUploadMeta();
                    $metaItemExists = $metaItem->loadByMany(array('idupl' => $imageInfo['idupl'], 'idlang' => $idlang));
                    if ($metaItemExists) {
                        // if meta item exists but there is no description, add the description
                        if ($metaItem->get('description') == '') {
                            $metaItem->set('description', $imageInfo['description']);
                            $metaItem->store();
                        }
                    } else {
                        // if no meta item exists, create a new one with the description
                        $metaItemCollection = new cApiUploadMetaCollection();
                        $metaItemCollection->create($imageInfo['idupl'], $idlang, '', $imageInfo['description']);
                    }
                }
                // delete old CMS_IMG and CMS_IMGDESCR content entries
                $contentCollection->delete($imageInfo['imgidcontent']);
                $contentCollection->delete($imageInfo['imgdescridcontent']);
            }

            /*
             * Convert all DB entries CMS_LINK, CMS_LINKTARGET and CMS_LINKDESCR to CMS_LINKEDITOR.
            * Old:
            * In the past, CMS_LINK saved the actual link, CMS_LINKTARGET the corresponding target and
            * CMS_LINKDESCR the corresponding link text.
            *
            * New:
            * Since CONTENIDO 4.9, CMS_LINKEDITOR contains an XML structure with all information.
            */
            $sql = 'SELECT `idcontent`, `idartlang`, `idtype`, `typeid`, `value` FROM `' . $cfg['tab']['content'] . '` WHERE `idtype`=' . $types['CMS_LINK'] . ' OR `idtype`=' . $types['CMS_LINKTARGET'] . ' OR `idtype`=' . $types['CMS_LINKDESCR'] . ' ORDER BY `typeid` ASC';
            $db->query($sql);
            $result = array();
            while ($db->next_record()) {
                // create an array in which each entry contains the data needed for converting one entry
                $idartlang = $db->f('idartlang');
                $typeid = $db->f('typeid');
                $key = $idartlang . '_' . $typeid;
                if (isset($result[$key])) {
                    $subResult = $result[$key];
                } else {
                    $subResult = array();
                    $subResult['idartlang'] = $idartlang;
                }
                if ($db->f('idtype') == $types['CMS_LINK']) {
                    $subResult['link'] = $db->f('value');
                    $subResult['linkidcontent'] = $db->f('idcontent');
                } else if ($db->f('idtype') == $types['CMS_LINKTARGET']) {
                    $subResult['linktarget'] = $db->f('value');
                    $subResult['linktargetidcontent'] = $db->f('idcontent');
                } else if ($db->f('idtype') == $types['CMS_LINKDESCR']) {
                    $subResult['linkdescr'] = $db->f('value');
                    $subResult['linkdescridcontent'] = $db->f('idcontent');
                }
                $result[$key] = $subResult;
            }

            // iterate over all entries and convert each of them
            foreach ($result as $linkInfo) {
                // calculate the next unused typeid
                $sql = 'SELECT MAX(typeid) AS maxtypeid FROM `' . $cfg['tab']['content'] . '` WHERE `idartlang`=' . $linkInfo['idartlang'] . ' AND `idtype`=' . $types['CMS_LINKEDITOR'];
                $db->query($sql);
                $db->next_record();
                if ($db->f('maxtypeid') === false) {
                    $nextTypeId = 1;
                } else {
                    $nextTypeId = $db->f('maxtypeid') + 1;
                }
                // construct the XML structure
                $newWindow = ($linkInfo['linktarget'] == '_blank')? 'true' : 'false';
                // if link is a relative path, prepend the upload path
                if (strpos($linkInfo['link'], 'http://') == 0 || strpos($linkInfo['link'], 'www.') == 0) {
                    $link = $linkInfo['link'];
                } else {
                    $link = $cfgClient[$this->_client]['upl']['path'] . $linkInfo['link'];
                }
                $xml = <<<EOT
<?xml version="1.0" encoding="utf-8"?>
<linkeditor><type>external</type><externallink>{$link}</externallink><title>{$linkInfo['linkdescr']}</title><newwindow>{$newWindow}</newwindow><idart></idart><filename></filename></linkeditor>
EOT;
                // insert new CMS_LINKEDITOR content entry
                $contentCollection = new cApiContentCollection();
                $contentCollection->create($linkInfo['idartlang'], $types['CMS_LINKEDITOR'], $nextTypeId, $xml, '');

                // delete old CMS_LINK, CMS_LINKTARGET and CMS_LINKDESCR content entries
                $contentCollection->delete($linkInfo['linkidcontent']);
                $contentCollection->delete($linkInfo['linktargetidcontent']);
                $contentCollection->delete($linkInfo['linkdescridcontent']);
            }

            /* Convert the value of each CMS_TEASER entry.
             * Only the format of the manual teaser settings has been changed as follows:
             * Old:
             * <manual_art>
             *   <art>6</art>
             *   <art>7</art>
             * </manual_art>
             *
             * New:
             * <manual_art><array_value>6</array_value><array_value>7</array_value></manual_art>
             */
            $contentCollection = new cApiContentCollection();
            $contentCollection->setWhere('idtype', $types['CMS_TEASER']);
            $contentCollection->query();
            while (($item = $contentCollection->next()) !== false) {
                $oldTeaserVal = $item->get('value');
                $oldTeaserArray = cXmlBase::xmlStringToArray($oldTeaserVal);
                if (!isset($oldTeaserArray['manual_art']['art'])) {
                    continue;
                }
                $oldTeaserArray['manual_art'] = $oldTeaserArray['manual_art']['art'];
                $newTeaserVal = cXmlBase::arrayToXml($oldTeaserArray, null, 'teaser');
                $item->set('value', $newTeaserVal->asXML());
                $item->store();
            }

        }
    }

}