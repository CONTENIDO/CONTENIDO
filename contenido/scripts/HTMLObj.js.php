<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * HTML object
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend sripts
 * @version    1.0.3
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.7
 * 
 * {@internal 
 *   created  2004-04-24
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2008-07-02, Frederic Schneider, include security_class
 *   modified 2010-05-20, Murat Purc, standardized CONTENIDO startup and security check invocations, see [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

// CONTENIDO startup process
include_once ('../includes/startup.php');

header("Content-Type: text/javascript");

page_open(array('sess' => 'Contenido_Session',
                'auth' => 'Contenido_Challenge_Crypt_Auth',
                'perm' => 'Contenido_Perm'));

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);
page_close();
?>

/**
 * HTMLObj Class
 *
 * Object of an HTML Element.
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function HTMLObj(objId) {

    this.objId = objId;
    this.obj = document.getElementById( this.objId );
    this.type = this.getElementType();
    this.id = null;
    this.status = 0; /* status for images / other elements..
                        0 - normal
                        1 - out
                        2 - locked */

} // end function

/**
 * Defines the HTML Element Type
 * and calls the setMethods() method
 * to add the corresponding methods
 * to the HTMLObj instance.
 *
 * @return type string Type of the HTML Element ('image'/'select')
 */
HTMLObj.prototype.getElementType = function () {

    var type = 'undefined';

    switch ( this.obj.tagName ) {

        case 'IMG':
                type = 'image';
            break;

        case 'SELECT':
                type = 'select';
            break;
    }

    if ( 'undefined' != type ) {
        this.setMethods( type );
    }

    return type;

} // end function

/**
 * Set methods depending on
 * the HTML Element type
 */
HTMLObj.prototype.setMethods = function(type) {

    switch ( type ) {

        case 'image':

            /* .over() method */
            this.over = function() {
                if ( '' != this.oImgSrc ) {
                    this.obj.src = this.oImgSrc;
                    this.status = "over";
                    /* If there is a corresponding label, show it */
                    if (document.getElementById(this.objId+'_label')) {
				         document.getElementById(this.objId+'_label').style.display = 'block';
            		  }
                }
            }

            /* .out() method */
            this.out = function() {
                if ( '' != this.nImgSrc ) {
                    this.obj.src = this.nImgSrc
                    this.status = "out";
                    /* If there is a corresponding label, show it */
                    if (document.getElementById(this.objId+'_label')) {
				         document.getElementById(this.objId+'_label').style.display = 'block';
            		  }
                }
            }

            /* Set image sources */
            this.setImgSrc = function( nImgSrc, oImgSrc ) {
                this.nImgSrc = nImgSrc;
                this.oImgSrc = oImgSrc;
            }

            /* Set the intance id */
            this.setId = function(id) {
                /* JS Object */
                this.id = id;
                /* HTML Object */
                this.obj.id = id;
            }

            /* Lock the image */
            this.lock = function() {
                this.obj.src = "images/spacer.gif";
                /* If there is a corresponding label, hide it */
                if (document.getElementById(this.objId+'_label')) {
				      document.getElementById(this.objId+'_label').style.display = 'none';
            	  }
            }

            /* ATTENTION HARDCODED EVENTS =/ */
            /*
            this.obj.onmouseover    = showAction;
            this.obj.onmouseout     = hideAction;


            */

            this.obj.onclick        = doAction;

            //Also make corresponding Labels clickable
            this.obj.parentNode.nextSibling.onclick        = doAction;
            this.obj.parentNode.nextSibling.onmouseover    = showAction;
            this.obj.parentNode.nextSibling.onmouseout     = hideAction;


            break;

        case 'select':

            /**
             * Select an entry
             * @param string value of the entry
             * @return void
             */
            this.select = function( selectedValue ) {

                var options = this.obj.getElementsByTagName( 'option' );
                var index = 0;

                for (i = 0; i < options.length; i ++) {
                    if ( selectedValue == options[i].value ) {
                        index = i;
                    }
                }

                this.obj.selectedIndex = index;

            }

            /**
             * Return value of the
             * select
             */
            this.getValue = function() {
                return this.obj.value;
            }

            /**
             * Return 'object HTMLCollection' for 'options'
             * @return object HTMLCollection All 'option' objects
             */
            this.getCollection = function() {
                return this.obj.getElementsByTagName( 'option' );
            }

            break;

    } // end switch

} // end function

/**
 * Controls the actions of
 * the infoBox class
 *
 * @return void
 * @author Jan Lengowski <jan.lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function showAction() {
		//alert("this is the JS function showAction() located in the file HTMLObj.js.php. This should not have been called as it is old style CONTENIDO")
		/*
    var str = this.src;
    //if Label is clicked, get src from previous image
    if (!str) {
        var str = this.previousSibling.firstChild.src;
    }

    if ( str.indexOf('setoffline') != -1 ) {
        box.show( '<?php echo i18n("Make offline"); ?>' );

    } else if ( str.indexOf('online.gif') != -1 ) {
        box.show('<?php echo i18n("Make online"); ?>');

    } else if ( str.indexOf('offline.gif') != -1 ) {
        box.show('<?php echo i18n("Make online"); ?>');

    } else if ( str.indexOf('folder_delock.gif') != -1 ) {
        box.show('<?php echo i18n("Protect"); ?>');

    } else if ( str.indexOf('folder_lock.gif') != -1 ) {
        box.show('<?php echo i18n("Remove protection"); ?>');

    } else {
        box.show('<?php echo i18n("Choose template"); ?>');
    }
		*/
}

/**
 * Controls the execution of
 * the actions depending on
 * the cfg properties
 *
 * @return void
 * @author Jan Lengowski <jan.lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function doAction() {
    var str = this.src;

    //if Label is clicked, get src from previous image
    if (!str) {
        var str = this.previousSibling.firstChild.src;
    }

		// Set Category Offline
    if ( str.indexOf('online.gif') != -1 )
    {
        str  = "";
        str += "main.php?area=con";
        str += "&action=con_makecatonline";
        str += "&frame=2";
        str += "&idcat=" + cfg.catId;
        str += "&online=" + 0;
        str += "&contenido=" + sid;

        if (cfg.catId != 0 && cfg.hasRight['online'] )
        {
            cfg.setAction(str);

            /* change image source */
            if ( this.status == "out" )
            {
                cfg.objRef[1].over()
                cfg.isOnline = ( cfg.isOnline == 0 ) ? 1 : 0;
            }
            else
            {
                cfg.objRef[1].out()
                cfg.isOnline = ( cfg.isOnline == 0 ) ? 1 : 0;
            }
        }
    }
    // Set Category Online
    else if ( str.indexOf('offline.gif') != -1 )
    {
        str  = "";
        str += "main.php?area=con";
        str += "&action=con_makecatonline";
        str += "&frame=2";
        str += "&idcat=" + cfg.catId;
        str += "&online=" + 1;
        str += "&contenido=" + sid;

        if (cfg.catId != 0  && cfg.hasRight['online'] )
        {
            cfg.setAction(str);

            /* change image source */
            if ( this.status == "out" )
            {
                cfg.objRef[1].over()
                cfg.isOnline = ( cfg.isOnline == 0 ) ? 1 : 0;
            }
            else
            {
                cfg.objRef[1].out()
                cfg.isOnline = ( cfg.isOnline == 0 ) ? 1 : 0;
            }
        }
    }
    //
    else if ( str.indexOf('folder_delock.gif') != -1 )
    {
        str  = "";
        str += "main.php?area=con";
        str += "&action=con_makepublic";
        str += "&frame=2";
        str += "&idcat=" + cfg.catId;
        str += "&public=" + 0;
        str += "&contenido=" + sid;

        if (cfg.catId != 0 && cfg.hasRight['public'] )
        {
            cfg.setAction(str);

            /* change image source */
            if ( this.status == "out" )
            {
                cfg.objRef[2].over()
                cfg.isPublic = ( cfg.isPublic == 0 ) ? 1 : 0;
            }
            else
            {
                cfg.objRef[2].out()
                cfg.isPublic = ( cfg.isPublic == 0 ) ? 1 : 0;
            }
        }
    }
    // Set C
    else if ( str.indexOf('folder_lock.gif') != -1 )
    {
        str  = "";
        str += "main.php?area=con";
        str += "&action=con_makepublic";
        str += "&frame=2";
        str += "&idcat=" + cfg.catId;
        str += "&public=" + 1;
        str += "&contenido=" + sid;

        if (cfg.catId != 0 && cfg.hasRight['public'] ) {

            cfg.setAction(str);

            /* change image source */
            if ( this.status == "out" )
            {
                cfg.objRef[2].over();
                cfg.isPublic = ( cfg.isPublic == 0 ) ? 1 : 0;
            }
            else
            {
                cfg.objRef[2].out();
                cfg.isPublic = ( cfg.isPublic == 0 ) ? 1 : 0;
            }
        }
    }
}

/**
 * Show the default text
 * in the infoBox
 *
 * @return void
 * @author Jan Lengowski <jan.lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function hideAction() {
    //box.show();
    //alert("this is the JS function hideAction() located in the file HTMLObj.js.php. This should not have been called as it is old style CONTENIDO")
}
