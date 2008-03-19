/*****************************************
*
* $Id: contenidoConfig.js,v 1.6 2003/10/15 09:44:24 jan Exp $
*
* File      :   $RCSfile: contenidoConfig.js,v $
* Project   :
* Descr     :
*
* Author    :   $Author: jan $
* Modified  :   $Date: 2003/10/15 09:44:24 $
*
* � four for business AG, www.4fb.de
******************************************/

/**
 * ContenidoConfig Class
 *
 * Contenido configuration object
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function contenidoConfig( instanceName ,actionFrameName ) {

    /* Name of the Instance for external calls
       or CallBacks. Defaults to 'cfg' */
    this.instanceName = instanceName || 'cfg';

    /* Name of the Actionframe. Defaults to 'left_bottom' */
    this.actionFrameName = actionFrameName || 'left_bottom';

    /* Reference to the Actionframe */
    this.actionFrame = parent.frames[this.actionFrameName];

    /* Element references Array */
    this.objRef = [];

    /* Userright properties Array */
    this.hasRight = [];
    this.hasRight['template']   = 0;
    this.hasRight['template_cfg']   = 0;
    this.hasRight['online']     = 0;
    this.hasRight['public']     = 0;
    this.hasRight['syncable']     = 0;

    /* Actionstatus */
    this.action = '';

    /* Status */
    this.status = false;

    /* Template ID */
    this.tplId = 0;
    
    /* New template id */
    this.nTplId = null;

    /* Online flag */
    this.isOnline = 0;
    
    /* Public flag */
    this.isPublic = 0;
    
    /* Category ID */
    this.catId = 0;
    
    /* idString */
    this.idString = 0;

} // end function

/**
 * Initializes the class.
 * This method should be overwritten if the
 * class is used in an other area beside 'con'.
 * Stuff is HARDCODED for 'con' ATM
 *
 * @param string Id of the On-/offline image
 * @param string Id of the Lock/Unlock image
 * @param string Id of the Template select
 */
contenidoConfig.prototype.init = function( imgOnlineId, imgPublicId, imgSelectId, imgTemplateCfgId) {

    this.objRef = this.createRefs( imgOnlineId, imgPublicId, imgSelectId, imgTemplateCfgId);

    if ( this.objRef.length == 4 ) {

        this.status = true;
        /* Set the object ID's */
        this.objRef[1].setId('online');
        this.objRef[2].setId('lock');
        this.objRef[3].setId('template_cfg');
        
        // HARDCODED STUFF
        this.objRef[1].setImgSrc( 'images/online.gif', 'images/offline.gif' );
        this.objRef[2].setImgSrc( 'images/folder_delock.gif', 'images/folder_lock.gif');
        this.objRef[3].setImgSrc( 'images/but_cat_conf2.gif', 'images/but_cat_conf2.gif' );
        return true;
    }
    
} // end function

/**
 * Loads a configuration and calls
 * the updateScreen method
 *
 * @param int Contenido Template Id
 * @param int Contenido Online flag
 * @param int Contenido Public flag
 */
contenidoConfig.prototype.load = function( Idcat, Idtpl, Online, Public, RightTpl, RightOn, RightPublic, RightTemplateCfg, RightIsSyncable, idString ) {
    this.catId = Idcat;
    this.tplId = Idtpl;
    this.isOnline = Online;
    this.isPublic = Public;
    this.idString = idString;

    this.hasRight['template'] = RightTpl;
    this.hasRight['template_cfg'] = RightTemplateCfg;
    this.hasRight['online']   = RightOn;
    this.hasRight['public']   = RightPublic;
    this.hasRight['syncable']   = RightIsSyncable;
    
    this.updateScreen();

} // end function

/**
 * Creates objects of class HTMLObj
 *
 * @param args string ID's of the objects
 * @return array Array storing the objects
 */
contenidoConfig.prototype.createRefs = function() {

    var objects = new Array;

    for ( i = 0; i < arguments.length; i ++ ) {
        objects[i] = new HTMLObj( arguments[i] );
    }

    return objects;

} // end function

/**
 * Updates the screen with the
 * given class cfg information
 * @return void
 */
contenidoConfig.prototype.updateScreen = function() {
    if ( this.status ) {
        /* Template select dropdown */
        if ( this.hasRight['template'] == 1 ) {

            /* User has right to change
               the template, enable dropdown, select template */
            this.objRef[0].obj.removeAttribute( "disabled" );
            this.objRef[0].select(this.tplId);

        } else {

            /* User has NO right to change
               the template, disable the dropdown */
            this.objRef[0].obj.setAttribute( "disabled", "true" );
            this.objRef[0].select(this.tplId);
        }

        /* On-/Offline */
        if ( 0 == this.isOnline && this.hasRight['online'] == 1 ) {
            this.objRef[1].over();
            
        } else if ( 1 == this.isOnline && this.hasRight['online'] == 1 ) {
            this.objRef[1].out();
            
        } else if ( 0 == this.hasRight['online'] ) {
            this.objRef[1].lock();
        }

        /* Public / Non-Public */
        if ( 0 == this.isPublic && 1 == this.hasRight['public'] ) {
            this.objRef[2].over();
        } else if ( 1 == this.isPublic && 1 == this.hasRight['public'] ) {
            this.objRef[2].out() ;
        } else {
            this.objRef[2].lock();			
        }
        
        /* Template Config button */
        if (this.hasRight['template_cfg'] == 1) {
            this.objRef[3].out();
        } else {
            this.objRef[3].lock();
        }

    } // end if this.status
    
} // end function

/**
 * Set the action property and
 * execute it
 * @param string action
 */
contenidoConfig.prototype.setAction = function(action) {

    this.actionFrame.location.href = action;

} // end function

/**
 * Change template for a marked category
 *
 * @return void
 * @author Jan Lengowski <jan.lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
contenidoConfig.prototype.changeTemplate = function() {

    if ( this.catId && this.hasRight['template'] == 1 ) {

        /* create action string */
        str  = "";
        str += "main.php?area=con";
        str += "&action=con_changetemplate";            // action
        str += "&frame=2";                              // frame 2
        str += "&idcat=" + cfg.catId;                   // idcat of marked category
        str += "&idtpl=" + this.objRef[0].getValue();   // id of selected template
        str += "&contenido=" + sid;                     // contenido session id

        /* execute action */
        this.setAction(str);

        /* set flag for changed template */
        this.nTplId = this.objRef[0].getValue();
        this.tplId  = this.objRef[0].getValue();
    }
}

/**
 * Return template changed status
 *
 * @return bool has template changed?
 */
contenidoConfig.prototype.templateChanged = function () {

    return ( this.nTplId != null ) ? true : false;

} // end function



/**
 * Return the rowId String
 * @return String RowId String
 */
contenidoConfig.prototype.getRowId = function() {

    /* Build the data string.
    0 -> category id
    1 -> category template id
    2 -> category online
    3 -> category public
    4 -> has right for: template
    5 -> has right for: online
    6 -> has right for: public 
    7->   has right for template_cfg
    8->   category is syncable*/
        
    var sRowId = "";
    
    sRowId += this.catId    + "-";
    sRowId += this.tplId    + "-";
    sRowId += this.isOnline + "-";
    sRowId += this.isPublic + "-";
    sRowId += this.hasRight['template'] + "-";
    sRowId += this.hasRight['online']   + "-";
    sRowId += this.hasRight['public'] + "-";
    sRowId += this.hasRight['template_cfg'] + "-";
    sRowId += this.hasRight['syncable'];

    return sRowId;

} // end function

/**
 * Reset the config object -> load default values;
 * @return String RowId String
 */
contenidoConfig.prototype.reset = function() {

    this.catId    = 0;
    this.tplId    = 0;
    this.isOnline = 0;
    this.isPublic = 0;
    
    this.hasRight['template_cfg']   = 0;
    this.hasRight['template'] = 0;
    this.hasRight['online']   = 0;
    this.hasRight['public']   = 0;

    this.updateScreen();

} // end function

