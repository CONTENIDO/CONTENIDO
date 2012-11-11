<?php 
	$tpl = Contenido_SmartyWrapper::getInstance();
	
	$upload = new cApiUploadCollection();
	$upload->select("dirname = 'picture_gallery/'");
	
	$pictures = array();
	
	while (($item = $upload->next()) !== false) {
		
		$path = 'upload/' . $item->getField('dirname') . $item->getField('filename');

		$id = $item->getField('idupl');

		$meta = new cApiUploadMeta();
		$meta->loadByUploadIdAndLanguageId($id, $lang);

		$record = array();
		$record['thumb'] = cApiImgScale($path, 319, 199);
		$record['lightbox'] = $path;
		$record['description'] = $meta->getField('description');
		$record['copyright'] = $meta->getField('copyright');
		
		array_push($pictures, $record);
	}
	
	$tpl->assign('pictures', $pictures);
	
	$tpl->display('content_picture_gallery/template/picture_gallery.tpl');
	
	if (cRegistry::isBackendEditMode()) {
		//TODO USE FILELIST SELECTOR AS INPUT FOR THIS MODULE
		echo "CMS_FILELIST[1]";
	}
	
?>