<?php

if (isset($_REQUEST['contenido_path']) || isset($_REQUEST['cfg']) || isset($_REQUEST['cfgClient'])) {
    die ('Illegal call!');
}

cInclude("includes", "functions.pathresolver.php");

function str_replace_recursive ($array) {
	if (!is_array($array)) return false;
	
	$result = array();
	
	foreach ($array as $value) {
		$result[] = str_replace("e", "", $value);
	}
	
	return $result;
}

// fetch idartlang for idart
$sql = "SELECT idartlang FROM ".$cfg['tab']['art_lang']." WHERE idart=".intval($idart)." AND idlang=".intval($lang);
$db->query($sql);
$db->next_record();
$this_idartlang = $db->f('idartlang');

if ($_POST) {
	//$_POST['allocation'] = str_replace_recursive($_POST['allocation']);
	
	#echo "<pre>";
	#print_r($_POST);
	#echo "</pre>";
} else {
	#echo "<pre>";
	#print_r($_REQUEST);
	#echo "</pre>";
}

$oPage = new cPage;
$oPage->setMargin(10);

$oTree = new pApiContentAllocationComplexList('06bd456d-fe76-40cb-b041-b9ba90dc400a');
$oAlloc = new pApiContentAllocation;

if ($_POST['action'] == 'storeallocation') {
	$oAlloc->storeAllocations($this_idartlang, $_POST['allocation']);	
}
if ($_GET['step'] == 'collapse') {
	$oTree->setTreeStatus($_GET['idpica_alloc']);
}

#build category path
$catString = '';
prCreateURLNameLocationString($idcat, '/', $catString);
$oArticle = new Article ($idart, $client, $lang);
$sArticleTitle = $oArticle->getField('title');

$sLocationString = "<div class=\"categorypath\">".$catString.'/'.htmlspecialchars($sArticleTitle)."</div>";

// load allocations
$loadedAllocations = $oAlloc->loadAllocations($this_idartlang);

$oTree->setChecked($loadedAllocations);
$result = $oTree->renderTree(true);

if ($result == false) {
    $result = $notification->returnNotification("warning", i18n('There is no Content Allocation tree.'));
} else {
	if (!is_object($tpl)) { $tpl = new Template; }
	$hiddenfields = '<input type="hidden" name="action" value="storeallocation">
		<input type="hidden" name="idart" value="'.$idart.'">
		<input type="hidden" name="contenido" value="'.$sess->id.'">
		<input type="hidden" name="area" value="'.$area.'">
		<input type="hidden" name="frame" value="'.$frame.'">
		<input type="hidden" name="idcat" value="'.$idcat.'">';
	$tpl->set('s', 'HIDDENFIELDS', $hiddenfields);
	
	
	if (sizeof($loadedAllocations) > 0) {
		$tpl->set('s', 'ARRAY_CHECKED_BOXES', 'var checkedBoxes = [' . implode(',', $loadedAllocations) . '];');
	} else {
		$tpl->set('s', 'ARRAY_CHECKED_BOXES', 'var checkedBoxes = [];');
	}
	
	$oDiv = new cHTMLDiv;
	$oDiv->updateAttributes(array('style' => 'text-align: right; padding: 5px; width: 730px; border: 1px #B3B3B3 solid; background-color: #FFFFFF;'));
	$oDiv->setContent('<input type="image" src="images/but_ok.gif" />');
	$tpl->set('s', 'DIV', '<br>' . $oDiv->render());
	
	$tpl->set('s', 'TREE', $result);

	$tpl->set('s', 'REMOVE_ALL', i18n("Remove all"));
	$tpl->set('s', 'REMOVE', i18n("Remove"));
	
	$result = $tpl->generate($cfg['pica']['treetemplate_complexlist'], true);
	
	$script = '<link rel="stylesheet" type="text/css" href="'.$cfg['pica']['style_complexlist'].'"/>
	<script language="javascript" src="'.$cfg['pica']['script_complexlist'].'"></script>';
	$oPage->addScript('style', $script);	
}


$oPage->setContent($sLocationString.$result . markSubMenuItem(5, true));
$oPage->render();

?>
