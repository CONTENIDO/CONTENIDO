?><?php
/**
 * Description: Simple sitemap input
 *
 * @version    0.1.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @todo       move function to load all categories somewhere else...
 * @todo       use some template instead of html in code...
 *
 * {@internal
 *   created 2009-01-05
 *   $Id$
 * }}
 */

if (!isset($db)) {
    $db = cRegistry::getDb();
}

$iSelectedCat = intval("CMS_VALUE[1]");
$iSelectedDepth = intval("CMS_VALUE[2]");
$iSelectedHtmlOutputStyle = intval("CMS_VALUE[3]");
$aAllCategories = sitemap_getAllCategories($db, $cfg, $lang, $client);
$sOptionsCategories = '';
$sOptionsDepth = '';
$sSelectedHtmlStyle0 = $iSelectedHtmlOutputStyle == 0 ? ' selected="selected"' : '';
$sSelectedHtmlStyle1 = $iSelectedHtmlOutputStyle == 1 ? ' selected="selected"' : '';

$sHtmlTable = '<table border="0" cellpadding="10" cellspacing="0">
    <tr>
        <td>'.mi18n("Kategorie wählen:").'</td>
        <td>
            <select name="' . "CMS_VAR[1]" . '">
                <option value="0">---Alle---</option>
                [SNIP_CATEGORIES]
            </select>
        </td>
</tr>
<tr>
    <td>'.mi18n("Maximale Anzahl Ebenen:").'</td>
    <td>
        <select name="' . "CMS_VAR[2]" . '">
            <option value="0">---Select---</option>
            [SNIP_DEPTH]
        </select>
    </td>
</tr>
<tr>
    <td>'.mi18n("HTML Ausgabe:").'</td>
    <td>
        <select name="' . "CMS_VAR[3]" . '">
            <option value="0"'.$sSelectedHtmlStyle0.'>&lt;ul&gt;&lt;li&gt;Alle Kategorien in einer Liste&lt;/li&gt;&lt;/ul&gt;</option>
            <option value="1"'.$sSelectedHtmlStyle1.'>&lt;ul&gt;&lt;li&gt;Alle Hauptkategorien in einer eigenen ul&lt;/li&gt;&lt;/ul&gt;</option>
        </select>
    </td>
</tr>
</table>
';

$iSelectedCat = intval("CMS_VALUE[1]");
$iSelectedDepth = intval("CMS_VALUE[2]");
$aAllCategories = sitemap_getAllCategories($db, $cfg, $lang, $client);
$sOptionsCategories = '';
$sOptionsDepth = '';

for ($i = 1; $i <= 30; $i++) {
    $sSelected = $iSelectedDepth == $i ? ' selected="selected"' : '';
    $sOptionsDepth .= '<option value="'.strval($i).'"'.$sSelected.'>'.strval($i).'</option>';
}

if (sizeof($aAllCategories) > 0) {
    foreach ($aAllCategories as $aCatDetails) {
        $sSelected = $iSelectedCat == intval($aCatDetails['idcat']) ? ' selected="selected"' : '';
        $sSpace = str_repeat('-', intval($aCatDetails['level']));
        $sCssLevelZero = intval($aCatDetails['level']) == 0 ? ' style="background-color:#F8FDDC;"' : '';
        $sOptionsCategories .= '<option value="'.strval($aCatDetails['idcat']).'"'.$sSelected.$sCssLevelZero.'>'.$sSpace.' '.strval($aCatDetails['name']).'</option>';
    }
}

echo str_replace(array('[SNIP_CATEGORIES]', '[SNIP_DEPTH]'), array($sOptionsCategories, $sOptionsDepth), $sHtmlTable);


/**
 * Return array with all info on ALL categories of current client/lang
 *
 * @param unknown_type $oDb
 * @param array $aCfg
 * @param unknown_type $iLang
 * @param unknown_type $iClient
 * @return unknown
 */
function sitemap_getAllCategories($oDb, array $aCfg, $iLang, $iClient) {
    $aResult = array();
    $sSql = "SELECT
            A.idcat,
            A.level,
            C.name
          FROM
            ".$aCfg['tab']['cat_tree']." AS A,
            ".$aCfg['tab']['cat']." AS B,
            ".$aCfg['tab']['cat_lang']." AS C
          WHERE
            A.idcat=B.idcat
            AND B.idcat=C.idcat
            AND C.idlang='".intval($iLang)."'
            AND B.idclient='".intval($iClient)."'
            AND C.visible=1
          ORDER BY A.idtree";
    $oDb->query($sSql);
    if ($oDb->Errno == 0 && $oDb->num_rows() > 0) {
        while($oDb->next_record()) {
            $aResult[] = array(
                'idcat' => intval($oDb->f('idcat')),
                'level' => intval($oDb->f('level')),
                'name' => strval($oDb->f('name'))
            );
        }
    }
    return $aResult;
}