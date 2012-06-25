<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CMS_IMG editor
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.3.5
 * @author     Ing. Christian Schuller (www.maurer-it.com)
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2003-12-10
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

if(isset($area) && $area == 'con_content_list'){
    $tmp_area = $area;
    $path = $cfg['path']['contenido_fullhtml'].'main.php?area=con_content_list&action=10&changeview=edit&idart='.$idart.'&idartlang='.$idartlang.
            '&idcat='.$idcat.'&client='.$client.'&lang='.$lang.'&frame=4&contenido='.$contenido;
} else {
    $path = $cfg['path']['contenido_fullhtml']."external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client";
}

if ($doedit == '1') {
    conSaveContentEntry($idartlang, 'CMS_IMG', $typenr, $CMS_IMG);
    conSaveContentEntry($idartlang, 'CMS_IMGDESCR', $typenr, $CMS_IMGDESCR);
    conMakeArticleIndex($idartlang, $idart);
    conGenerateCodeForArtInAllCategories($idart);
    header('location:'.$sess->url($path));
}

?>
<html>
<head>
    <title>CONTENIDO</title>
    <link rel="stylesheet" type="text/css" href="<?php print $cfg['path']['contenido_fullhtml'] . $cfg['path']['styles'] ?>contenido.css">
    <script type="text/javascript" src="<?php print $cfg['path']['contenido_fullhtml'] . $cfg['path']['scripts'] ?>jquery/jquery.js"></script>
</head>
<body>
<table width="100%"  border=0 cellspacing="0" cellpadding="0" bgcolor="#ffffff">
    <tr>
        <td width="10" rowspan="4"><img src="<?php print $cfg['path']['contenido_fullhtml'] . $cfg['path']['images'] ?>spacer.gif" width="10" height="10"></td>
        <td width="100%"><img src="<?php print $cfg['path']['contenido_fullhtml'] . $cfg['path']['images'] ?>spacer.gif" width="10" height="10"></td>
        <td width="10" rowspan="4"><img src="<?php print $cfg['path']['contenido_fullhtml'] . $cfg['path']['images'] ?>spacer.gif" width="10" height="10"></td>
    </tr>
    <tr>
        <td>

<?php

cInclude('includes','functions.forms.php');

getAvailableContentTypes($idartlang);

$dirheight = getEffectiveSetting('cms_img', 'directory-height', 5);
$dirwidth = getEffectiveSetting('cms_img', 'directory-width', 300);
$fileheight = getEffectiveSetting('cms_img', 'file-height', 5);
$filewidth = getEffectiveSetting('cms_img', 'file-width', 300);
$descrheight = getEffectiveSetting('cms_img', 'description-height', 5);
$descrwidth = getEffectiveSetting('cms_img', 'description-width', 70);
$previewwidth = getEffectiveSetting('cms_img', 'preview-width', 600);
$previewheight = getEffectiveSetting('cms_img', 'preview-height', 400);
$filetypes = "'jpeg', 'jpg', 'gif', 'png'";

// selected or previous selected image directory
if (!isset($img_dir)) {
    $oUploadItem = new cApiUpload($a_content['CMS_IMG'][$typenr]);
    $img_dir = $oUploadItem->get('dirname');
}

// all directories
$aDirectories = array();
$sql = "SELECT DISTINCT(dirname) AS dirname FROM ".$cfg['tab']['upl']." WHERE "
     . "idclient='".$client."' AND filetype IN (" . $filetypes . ") ORDER BY dirname";
$db->query($sql);
while ($db->next_record()) {
    $dirname = $db->f('dirname');
    $aParts = explode('/', trim($dirname, '/'));
    $aDirectories[] = array(
        'selected' => ($dirname == $img_dir),
        'name' => $aParts[count($aParts)-1],
        'fullPath' => $dirname,
        'level' => substr_count($dirname, '/'),
    );
}

// all images in current directory
$aImages = array();
$aDescription = array();
$oUploadColl = new cApiUploadCollection();
$sWhere = "idclient='".$client."' AND dirname='" . $db->escape($img_dir) . "' AND filetype IN (" . $filetypes . ")";
$oUploadColl->select($sWhere, '', 'filename ASC');
while ($oItem = $oUploadColl->next()) {
    //get description from con_upl_meta pro id
    $sql = "SELECT DISTINCT(description) FROM ".$cfg['tab']['upl_meta']." WHERE "
         . "idlang='".$lang."' AND idupl=".$oItem->get('idupl')." ORDER BY id_uplmeta";
    $db->query($sql);
    $db->next_record();
    $aImages[] = array(
        'selected' => ($a_content['CMS_IMG'][$typenr] == $oItem->get('idupl')),
        'idupl' => $oItem->get('idupl'),
        'description' => urldecode($db->f('description')),
        'filename' => $oItem->get('filename'),
    );
}

$form = new UI_Table_Form('editcontent', $cfg['path']['contenido_fullhtml'] . $cfg['path']['includes'] . 'include.backendedit.php');
$form->setVar('lang', $lang);
$form->setVar('typenr', $typenr);
$form->setVar('idart', $idart);
$form->setVar('idcat', $idcat);
$form->setVar('idartlang', $idartlang);
$form->setVar('contenido', $sess->id);
$form->setVar('action', 10);
$form->setVar('doedit', 1);
$form->setVar('type', $type);
$form->setVar('changeview', 'edit');
$form->setVar('CMS_LINK', $a_content['CMS_LINK'][$typenr]);

$form->addHeader(sprintf(i18n("Edit image for container %s"), $typenr));


// create directory select
$dirselect = new cHTMLSelectElement('img_dir');
$dirselect->setId('select_img_dir');
$dirselect->setSize($dirheight);
$dirselect->setStyle("width: {$dirwidth}px;");

foreach ($aDirectories as $pos => $item) {
    $text = str_repeat('-', $item['level'] * 2) . '> ' . $item['name'];
    $option = new cHTMLOptionElement($text, $item['fullPath']);

    switch ($item['level']) {
        case 0:
        case 1: $style = 'background-color:#C0C0C0;'; break;
        case 2: $style = 'background-color:#D0D0D0;'; break;
        case 3: $style = 'background-color:#E0E0E0;'; break;
        default: $style = 'background-color:#F0F0F0;'; break;
    }

    if ($item['selected']) {
        $option->setSelected('selected');
    }

    $dirselect->addOptionElement($item['fullPath'], $option);
}


// create file select
$fileselect = new cHTMLSelectElement('CMS_IMG');
$fileselect->setId('select_cms_img');
$fileselect->setSize($fileheight);
$fileselect->setStyle("width: {$filewidth}px;");

$option = new cHTMLOptionElement('-- ' . i18n("None") . ' --', '0');
if ($a_content['CMS_IMG'][$typenr] == 0) {
    $option->setSelected('selected');
}
$fileselect->addOptionElement(-1, $option);

foreach ($aImages as $pos => $item) {
    if ($item['description'] != '') {
        if (strlen($item['description']) > 24) {
            $item['description'] = substr($item['description'], 0, 24) . '..';
        }
        $text = $item['description'] . ' (' . $item['description'] . ')';
    } else {
        $text = $item['filename'];
    }

    $style = ($pos % 2) ? 'background-color:#E0E0E0;' : 'background-color:#D0D0D0;';

    $option = new cHTMLOptionElement($text, $item['idupl']);
    if ($item['selected']) {
        $option->setSelected('selected');
    }
    $option->setStyle($style);
    $fileselect->addOptionElement($item['idupl'], $option);
}

$form->add(i18n("Directory / File"), $dirselect->render() . $fileselect->render());

// description
$textarea = new cHTMLTextarea('CMS_IMGDESCR', $a_content['CMS_IMGDESCR'][$typenr], $descrwidth, $descrheight);
$form->add(i18n("Description"), $textarea->render());

// preview
$preview = '<iframe src="about:blank" name="preview" style="border:0;width:'.$previewwidth.'px;height:'.$previewheight.'px;">';
$preview .= '</iframe>';
$form->add(i18n("Preview"), $preview);

$form->render(false);


// create images javascript array
$script = '    var imglnk = new Array();' . "\n";
foreach ($aImages as $pos => $item) {
    if (cApiDbfs::isDbfs($img_dir)) {
        $link = $cfgClient[$client]['path']['htmlpath'] . 'dbfs.php?file=' . urlencode($img_dir . $item['filename']);
    } else {
        $link = $cfgClient[$client]['path']['htmlpath'] . $cfgClient[$client]['upl']['frontendpath'] . $img_dir . $item['filename'];
    }
    $script .= '    imglnk["'.$item['idupl'].'"] = "' . $link . '";' . "\n";
}

?>

        </td>
    </tr>
</table>
<script type="text/javascript">
$(document).ready(function() {
<?php echo $script ?>

    var $form = $('form[name="editcontent"]'),
        $dir = $('#select_img_dir'),
        $img = $('#select_cms_img');

    // display preview function
    var _dispPreview = function(){
        if ($img.val()) {
            var img = "";
            if ($img.val() != "0") {
                img = '<img src="'+imglnk[$img.val()]+'" alt="">';
            }
            preview.document.open();
            preview.document.writeln('<html><body style="padding:0;margin:0;"><table border="0" width="100%" height="100%"><tr><td align="middle">' + img + '</td></tr></table></body></html>');
            preview.document.close();
        }
    };

    // reload page on directory select change
    $dir.change(function(){
        $form.find('[name="doedit"]').val('0');
        $form.submit();
    });

    // display preview on image select change
    $img.change(function(){
        _dispPreview();
    });

    // display preview delayed on document ready
    window.setTimeout(function(){
        _dispPreview();
    }, 500);
});
</script>
</body>
</html>