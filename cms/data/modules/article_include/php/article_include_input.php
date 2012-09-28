?><?php
/**
 * Article Include input
 *
 * @author Willi Man
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 */

$cfg = cRegistry::getConfig();
$db = cRegistry::getDb();
$lang = cRegistry::getLanguageId();

// Get current settings
$name = "CMS_VAR[1]";
$cmsIdcat = "CMS_VALUE[1]";
$cmsIdcatart = "CMS_VALUE[2]";

// Cat selector
echo buildCategorySelect($name, $cmsIdcat);

$table = new cHTMLTable();
$td = new cHTMLTableData(mi18n('Choose article') . ':');
$td->setClass('text_medium');
$tr = new cHTMLTableRow($td);
$table->appendContent($tr);

// build article select
$select = new cHTMLSelectElement("CMS_VAR[2]");
$option = new cHTMLOptionElement(mi18n('Please choose'), '');
// if no article has been selected yet, select "please choose" option
if (empty($cmsIdcatart)) {
    $option->setSelected(true);
}
$select->appendOptionElement($option);

if (!empty($cmsIdcat)) {
    $sql = 'SELECT
                a.title AS title, b.idcatart AS idcatart
           FROM
                ' . $cfg['tab']['art_lang'] . ' AS a, ' . $cfg['tab']['cat_art'] . " AS b
            WHERE
                b.idcat = '" . $cmsIdcat . "' AND a.idart = b.idart AND a.idlang = '" . $lang . "'";
    $db->query($sql);
    while ($db->next_record()) {
        $idcatart = $db->f('idcatart');
        $title = $db->f('title');
        $option = new cHTMLOptionElement($title, $idcatart);

        if ($cmsIdcatart == $idcatart) {
            $option->setSelected(true);
        }
        $select->appendOptionElement($option);
    }
}

$td = new cHTMLTableData($select);
$input = new cHTMLFormElement();
$input->setAttribute('type', 'image');
$input->setAttribute('src', 'images/submit.gif');
$td->appendContent($input);
$td->setClass('text_medium');
$tr = new cHTMLTableRow($td);
$table->appendContent($tr);

echo $table->render();

?><?php