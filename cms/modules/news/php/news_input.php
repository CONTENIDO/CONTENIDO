?><?php
/**
* $RCSfile$
*
* Description: Newslist / ArticleList. Module "Input".
*
* @version 1.0.0
* @author Andreas Lindner
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2005-08-12
* }}
*
* $Id$
*/

#Select category
$cms_idcat = "CMS_VALUE[1]";

echo '<table cellpadding="0" cellspacing="0" border="0">';

echo '
        <tr><td class="text_medium" style="padding:5px">Kategorie w&auml;hlen: </td></tr>
        <tr><td class="text_medium" style="padding:5px">';

echo buildCategorySelect("CMS_VAR[1]", "CMS_VALUE[1]");
echo '&nbsp;<input type="image" src="images/submit.gif">';

echo '</td></tr>';

#Select sort field and sort order
$sortdate = 'checked';
$cms_imgsize = "CMS_VALUE[14]";
$cms_limit_articles = "CMS_VALUE[15]";
$cms_sort_direction = "CMS_VALUE[16]";
if ("CMS_VALUE[17]" != '') {
    $cms_include_start_article = ' checked';
} else {
    $cms_include_start_article = '';
}

if ("CMS_VALUE[3]" == 'sortdate') {
    $sortdate = 'checked';
    $sortnum = '';
}
elseif ("CMS_VALUE[3]" == 'sortnum') {
    $sortdate = '';
    $sortnum = 'checked';
}

echo '
        <tr>
          <td class="text_medium" style="padding:5px;">'.mi18n("Sortierung nach Datum").':</td>
          <td style="padding:5px;"><input type="radio" name="CMS_VAR[3]" value="sortdate" '.$sortdate.'></td>
        </tr>
        <tr>
          <td class="text_medium" style="padding:5px;">'.mi18n("Sortierung nach Nummer").':</td>
          <td style="padding:5px;"><input type="radio" name="CMS_VAR[3]" value="sortnum" '.$sortnum.'></td>
        </tr>
        <tr>
          <td class="text_medium" style="padding:5px;">'.mi18n("Sortierung aufsteigend").':</td>
          <td style="padding:5px;">';
if (strtolower($cms_sort_direction) == 'desc') {
    echo '<input type="radio" name="CMS_VAR[16]" value="asc"/>';
} else {
    echo '<input type="radio" name="CMS_VAR[16]" value="asc" checked/>';
}
echo '</td>
        </tr>
        <tr>
          <td class="text_medium" style="padding:5px;">'.mi18n("Sortierung absteigend").':</td>
          <td style="padding:5px;">';
if (strtolower($cms_sort_direction) == 'desc') {
    echo '<input type="radio" name="CMS_VAR[16]" value="desc" checked/>';
} else {
    echo '<input type="radio" name="CMS_VAR[16]" value="desc"/>';
}
echo '</td>
        </tr>';

$noimg = '';
if ("CMS_VALUE[13]" == 'true') {
    $noimg = 'checked';
}

#Headline
echo '
        <tr><td class="text_medium" style="padding:5px;">'.mi18n("&Uuml;berschrift").': </td></tr>
        <tr><td style="padding:5px;"><input type="text" name="CMS_VAR[4]" value="CMS_VALUE[4]"></td></tr>
        <tr>
          <td colspan="2" class="text_medium" style="padding:5px;"><b><u>'.mi18n("Bild f&uuml;r Teaser").':</u></b></td>
        </tr>
        <tr>
          <td colspan="2" class="text_medium" style="padding:5px;">'.mi18n("Es wird das erste Bild des Artikels angezeigt.").'</td>
        </tr>';
#Image width
echo '
        <tr><td class="text_medium" style="padding:5px;">'.mi18n("Bildbreite").': </td></tr>
        <tr><td style="padding:5px;"><input type="text" name="CMS_VAR[14]" value="'.$cms_imgsize.'" maxlength="3"></td></tr>';
#Disable images
echo '
        <tr>
          <td class="text_medium" style="padding:5px;">'.mi18n("Kein Bild anzeigen").':</td>
          <td style="padding:5px;"><input type="checkbox" name="CMS_VAR[13]" value="true" '.$noimg.'></td>
        </tr>';
#Number of articles
echo '
        <tr>
          <td class="text_medium" style="padding:5px;">'.mi18n("Anzahl Artikel begrenzen").':</td>
          <td style="padding:5px;"><input type="text" name="CMS_VAR[15]" value="'.$cms_limit_articles.'"></td>
        </tr>';
#Include start article
echo '
        <tr>
          <td class="text_medium" style="padding:5px;">'.mi18n("Startartikel in Liste einbeziehen").':</td>
          <td style="padding:5px;"><input type="checkbox" name="CMS_VAR[17]" value="yes"'.$cms_include_start_article.'></td>
        </tr>';

echo '</table>';
?><?php