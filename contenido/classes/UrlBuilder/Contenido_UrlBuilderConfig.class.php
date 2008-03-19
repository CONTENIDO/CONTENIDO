<?php
/**
* $RCSfile$
*
* Description: Configure UrlBuilder URL style. Per default, configures for style index-a-1.html.
* If you need another style, extend this class to your needs and pass it to desired UrlBuilder.
*
* @version 1.0.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-02-28
* }}
*
* $Id$
*/

class Contenido_UrlBuilderConfig {
    public static function getConfig() {
        return array(
                    'prefix' => 'index', 
                    'suffix' => '.html', 
                    'separator' => '-'
                    );
    }
}
?>