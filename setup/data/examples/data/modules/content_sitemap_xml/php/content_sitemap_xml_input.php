?><?php

/**
 *
 * @package    Module
 * @subpackage ContentSitemapXml
 * @author     simon.sprankel@4fb.de
 * @author     marcus.gnass@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!class_exists('ContentSitemapXmlModule')) {
    cInclude('module', 'class.content_sitemap_xml_module.php');
}

$db = cRegistry::getDb();
$cfg = cRegistry::getConfig();
$client = cRegistry::getClientId();

$selected = "CMS_VALUE[1]";
$filename = "CMS_VALUE[2]";
$selectName = "CMS_VAR[1]";

// if nothing is selected, select the root category
if ($selected == '') {
    $query = 'SELECT * FROM ' . $cfg['tab']['cat_tree'] . ' AS a, ' . $cfg['tab']['cat'] . ' AS b WHERE (a.idcat) AND (b.idcat) AND (b.idclient = ' . $client . ') ORDER BY a.idtree';
    $db->query($query);
    $db->nextRecord();
    $selected = $db->f('idcat');
}

$categories = ContentSitemapXmlModule::buildCategoryArray();
// construct the HTML
$table = new cHTMLTable();
$trs = [];

// construct the category select HTML
$tr = new cHTMLTableRow();
$tds = [];
$td = new cHTMLTableData();
$td->setContent(conHtmlSpecialChars(mi18n("Choose tree:")));
$tds[] = $td;
$td = new cHTMLTableData();
//$select = new cHTMLInputSelectElement($selectName);
$select = new cHTMLSelectElement($selectName);

foreach ($categories as $key => $value) {
    $option = new cHTMLOptionElement($value['name_indented'], $value['idcat']);
    if ($selected == $value['idcat']) {
        $option->setSelected(true);
    }
    $select->appendOptionElement($option);
}
$td->setContent($select);
$tds[] = $td;
$tr->setContent($tds);
$trs[] = $tr;

// construct the filename input HTML
$tr = new cHTMLTableRow();
$tds = [];
$td = new cHTMLTableData();
$td->setContent(conHtmlSpecialChars(mi18n("Enter filename (optional):")));
$tds[] = $td;
$td = new cHTMLTableData();
$input = new cHTMLTextbox("CMS_VAR[2]", $filename, 20);
// show error message if filename contains slashes or backslashes
$td->setContent($input);
$tds[] = $td;
$tr->setContent($tds);
$trs[] = $tr;

$table->setContent($trs);

// echo the whole HTML
echo $table->render();

