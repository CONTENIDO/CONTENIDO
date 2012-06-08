<?php
/**
 * Description: Meta Navigation on bottom of page
 *
 * @version    1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2008-04-07
 *   $Id$
 * }}
 */

// Get start idcat
$iIdcatStart = getEffectiveSetting('navigation', 'idcat-meta', 2);

// Check if there is a template instance
if (!isset($tpl) || !is_object($tpl)) {
    $tpl = new Template();
}

// Reset template object
$tpl->reset();

// Build navigation
try {
    $oFeNav = new Contenido_FrontendNavigation($db, $cfg, $client, $lang, $cfgClient);
    $oFeNav->setAuth($auth);
    $oContenidoCategories = $oFeNav->getSubCategories($iIdcatStart, true);
    if ($oContenidoCategories->count() > 0) {
        foreach ($oContenidoCategories as $oContenidoCategory) {
            // This is just for sample client - modify to your needs!
            if ($cfg['url_builder']['name'] == 'front_content' || $cfg['url_builder']['name'] == 'MR') {
                $aParams = array('lang' => $lang, 'idcat' => $oContenidoCategory->getIdCat());
            } else {
                $aParams = array(
                    'a' => $oContenidoCategory->getIdCat(),
                    'idcat' => $oContenidoCategory->getIdCat(), // needed to build category path
                    'lang' => $lang, // needed to build category path
                    'level' => 0
                ); // needed to build category path
            }
            try {
                $tpl->set('d', 'url', Contenido_Url::getInstance()->build($aParams));
            } catch (InvalidArgumentException $e) {
                $tpl->set('d', 'url', 'front_content.php?idcat='.$oContenidoCategory->getIdCat());
            }
            $tpl->set('d', 'title', $oContenidoCategory->getCategoryLanguage()->getName());
            $tpl->set('d', 'label', $oContenidoCategory->getCategoryLanguage()->getName());
            $tpl->next();
        }
        $sItems = $tpl->generate('templates/navigation_meta_item.html', true, false);
        $tpl->reset();
        $tpl->set('s', 'items', $sItems);
        $tpl->generate('templates/navigation_meta_container.html');
    }
} catch (Exception $e) {
    echo 'Shit happens: ' . $e->getMessage() . ': ' . $e->getFile() . ' at line '.$e->getLine() . ' ('.$e->getTraceAsString().')';
}

?>