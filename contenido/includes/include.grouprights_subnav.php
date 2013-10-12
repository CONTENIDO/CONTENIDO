<?php
/**
 * This file contains the sub navigation frame backend page for group management.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (isset($_GET['groupid'])) {
    $area = $_GET['area'];

    $nav = new cGuiNavigation();

    $sql = "SELECT idarea
            FROM " . $cfg["tab"]["area"] . " AS a
            WHERE a.name = '" . $db->escape($area) . "' OR a.parent_id = '" . $db->escape($area) . "'
            ORDER BY idarea";

    $db->query($sql);

    $in_str = "";

    while ($db->nextRecord()) {
        $in_str .= $db->f('idarea') . ',';
    }

    $len = strlen($in_str)-1;
    $in_str = substr($in_str, 0, $len);
    $in_str = '('.$in_str.')';

    $sql = "SELECT
                b.location AS location,
                a.name AS name
            FROM
                ".$cfg["tab"]["area"]." AS a,
                ".$cfg["tab"]["nav_sub"]." AS b
            WHERE
                b.idarea IN ".$db->escape($in_str)." AND
                b.idarea = a.idarea AND
                b.level = 1 AND
                b.online = 1
            ORDER BY
                b.idnavs";

    $db->query($sql);

    while ($db->nextRecord()) {
        // Extract names from the XML document.
        $caption = $nav->getName($db->f("location"));
        $tmp_area = $db->f("name");

        if ($perm->have_perm_area_action($tmp_area)) {
            // Set template data
            $tpl->set("d", "ID",      'c_'.$tpl->dyn_cnt);
            $tpl->set("d", "CLASS",   '');
            $tpl->set("d", "OPTIONS", '');
            $tpl->set("d", "CAPTION", '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$tmp_area&frame=4&groupid=$groupid").'">'.$caption.'</a>');
            $tpl->next();
        }
    }

    $_cecIterator = $_cecRegistry->getIterator("Contenido.Permissions.Group.Areas");

    if ($_cecIterator->count() > 0) {
        while (($chainEntry = $_cecIterator->next()) !== false) {
            $aInfo = $chainEntry->execute();

            foreach ($aInfo as $key => $sAreaID) {
                $sAreaName = false;
                $_cecIterator2 = $_cecRegistry->getIterator("Contenido.Permissions.Group.GetAreaName");
                while (($chainEntry2 = $_cecIterator2->next()) !== false) {
                    $aInfo2 = $chainEntry2->execute($sAreaID);
                    if ($aInfo2 !== false) {
                        $sAreaName = $aInfo2;
                        break;
                    }
                }

                if ($sAreaName !== false) {
                    // Set template data
                    $tpl->set("d", "ID",      'c_'.$tpl->dyn_cnt);
                    $tpl->set("d", "CLASS",   '');
                    $tpl->set("d", "OPTIONS", '');
                    $tpl->set("d", "CAPTION", '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=group_external&frame=4&external_area=$sAreaID&groupid=$groupid").'">'.$sAreaName.'</a>');
                    $tpl->next();
                }
            }
        }
    }
    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

    // Generate the third navigation layer
    $tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["subnav"]);

} else {
    include(cRegistry::getBackendPath() . $cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);
}
