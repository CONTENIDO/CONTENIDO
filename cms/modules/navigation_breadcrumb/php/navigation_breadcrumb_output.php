<?php
/**
 * Description: Build a breadcrumb navigation
 * starting from top idcat (of given level) down to current idcat
 *
 * @version    1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2008-04-08
 *   $Id$
 * }}
 */

try {
    $oBreadcrumb = new Contenido_FrontendNavigation_Breadcrumb($db, $cfg, $client, $lang, $cfgClient);
    $oBreadCategories = $oBreadcrumb->get($idcat, 1); // starting level in this case is 1, not 0
    if ($oBreadCategories->count() > 0) {
        foreach ($oBreadCategories as $oBreadCategory) {
            // please remember, this is a sample - or a live application it's better to use a template!
            echo '&gt; <a href="front_content.php?idcat='.$oBreadCategory->getIdCat().'">'.
                     $oBreadCategory->getCategoryLanguage()->getName().
                 '</a> ';
        }
    }
} catch (Exception $e) {
    echo 'Shit happens: ' . $e->getMessage() . ': ' . $e->getFile() . ' at line '.$e->getLine() . ' ('.$e->getTraceAsString().')';
}

?>