<?php

/**
 * description: google map
 *
 * @package Module
 * @author alexander.scheider@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (cRegistry::isBackendEditMode()) {
	"CMS_HTMLHEAD[600]";
    "CMS_HTML[601]";
}

// use smarty template to output header text
$tpl = Contenido_SmartyWrapper::getInstance();


$map = '<iframe width="425" height="350" frameborder="0"
    	scrolling="no" marginheight="0" marginwidth="0"
    	src="http://maps.google.de/maps?f=q&amp;source=s_q&amp;hl=de&amp;geocode=&amp;q=Nordring+82B,+Offenbach+am+Main&amp;aq=2&amp;oq=nordring+82B&amp;sll=50.112213,8.747968&amp;sspn=0.010252,0.01929&amp;ie=UTF8&amp;hq=&amp;hnear=Nordring+82B,+63067+Offenbach+am+Main,+Darmstadt,+Hessen&amp;t=m&amp;z=14&amp;ll=50.112213,8.747968&amp;output=embed&amp;iwloc=near"></iframe>';

$tpl->assign('map',$map);
$tpl->assign('header', "CMS_TEXT[600]");
$tpl->assign('address', strip_tags("CMS_TEXT[601]"));

echo $tpl->fetch('content_map_google/template/get.tpl');


?>