<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Moving article related logic to the front_end
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend sripts
 * @version    1.0.4
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.7
 *
 * {@internal
 *   created  2003-05-23
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
include_once('../includes/startup.php');

header('Content-Type: text/javascript');

cRegistry::bootstrap(array('sess' => 'cSession',
                'auth' => 'Contenido_Challenge_Crypt_Auth',
                'perm' => 'cPermission'));

i18nInit($cfg['path']['contenido_locale'], $belang);
require($cfg['path']['contenido'].'includes/functions.includePluginConf.php');
// do not call cRegistry::shutdown(); here because
// it will print <script> tags which result in errors


/* Fetch chains */
$iterator = $_cecRegistry->getIterator('Contenido.Article.RegisterCustomTab');

echo '//itsameA';

$aTabs = array();
while ($chainEntry = $iterator->next())
{
    $aTmpArray = $chainEntry->execute();

    if (is_array($aTmpArray))
    {
        echo '//itsame';
        $aTabs = array_merge($aTabs, $aTmpArray);
    }
}
?>

/**
 * Object of an article
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function articleObject(actionFrameName, frameNumber)
{
    /* Name of the Actionframe.
       Defaults to 'right_bottom' */
    this.actionFrameName = actionFrameName || 'right_bottom';

    /* Reference to the Actionframe */
    this.actionFrame = parent.parent.frames["right"].frames[this.actionFrameName];

    /* Frame-number.
       Defaults to '4' */
    this.frame      = frameNumber || 4;

    /* Filename of the CONTENIDO
       main file - defaults to 'main.php' */
    this.filename   = "main.php?"

    /* CONTENIDO session name -
       defaults to 'contenido' */
    this.sessionName = "contenido"

    /* Current page selection (first shown article number) */
    this.next       = 0;

    /* Global Vars */
    this.sessid     = 0;
    this.client     = 0;
    this.lang       = 0;

    /* Article Properties*/
    this.idart      = 0;
    this.idartlang  = 0;
    this.idcat      = 0;
    this.idcatlang  = 0;
    this.idcatart   = 0;
    this.idlang        = 0;

    /* Menu visible / invisible */
    this.vis        = 1;

    this.customTabs = new Array();

    /* Href of OverviewPage */
    this.hrefOverview = null;

    <?php
    print("/*DUMP:<pre>"); var_Dump($aTabs ); print("</pre>*/");

    foreach ($aTabs as $key => $sTab)
    {
        echo 'this.customTabs[\''.$sTab.'\'] = new Object();'."\n";

        $iterator = $_cecRegistry->getIterator("Contenido.Article.GetCustomTabProperties");

        $aTabs = array();
        while ($chainEntry = $iterator->next())
        {
            $aTmpArray = $chainEntry->execute($sTab);

            if (is_array($aTmpArray))
            {
                break;
            }
        }
        echo 'this.customTabs[\''.$sTab.'\'][\'area\'] = "'.$aTmpArray[0].'";'."\n";
        echo 'this.customTabs[\''.$sTab.'\'][\'action\'] = "'.$aTmpArray[1].'";'."\n";
        echo 'this.customTabs[\''.$sTab.'\'][\'custom\'] = "'.$aTmpArray[2].'";'."\n";
    }
    ?>
}

/**
 * Define required global variables
 *
 * @return void
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
articleObject.prototype.setGlobalVars = function(sessid, client, lang)
{
    this.sessid = sessid;
    this.client = client;
    this.lang   = lang;
}

/**
 * Sets href to overview page, which was last visited
 *
 * @return void
 * @author Timo Trautmann <timo.trautmann@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
articleObject.prototype.setHrefOverview = function(href)
{
    /*copy url - cut all actions*/
    if (href.match(/backend_search.php$/g)) {

        this.hrefOverview = 'javascript:top.content.left.left_top.document.getElementById(\'backend_search\').submit.click();';
    } else if (href.match(/backend_search/g) || href.match(/area=con_workflow/g)) {
        this.hrefOverview = href.replace(/action=([^&]*)&?/g, '');
    } else {
        this.hrefOverview = null;
    }
}

/**
 * Reset properties
 *
 * @return void
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
articleObject.prototype.reset = function()
{
    this.idart      = 0;
    this.idartlang  = 0;
    this.idcatlang  = 0;
    this.idcatart   = 0;
    this.idlang     = 0;
}

/**
 * Define required global variables
 *
 * @return string with attached frame & session parameters
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
articleObject.prototype.sessUrl = function(str)
{
    var tmp_str = str;
    tmp_str += '&frame=' + this.frame;
    tmp_str += '&'+this.sessionName+'='+this.sessid;
    return tmp_str;
}

/**
 * Execute an action
 *
 * @return bool Action executes Yes/No
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
articleObject.prototype.doAction = function(str)
{

    /* Flag if action will be executed. */
    var doAction = false;

    /* create messageBox instance */
    var box = new messageBox("", "", "", 0, 0);

    /* Notify Headline */
    var headline = "<?php echo i18n("Error"); ?>";

    /* Default error string */
    var err_str = "<?php echo i18n("Error"); ?>";

    switch (str)
    {
        /* Article overview mask */
        case 'con':
            /* Check if required parameters are set  */
            if (this.hrefOverview) {
                url_str = this.hrefOverview;
                doAction = true;
            } else {
                if ( 0 != this.idcat ) {
                    url_str = this.sessUrl(this.filename + "area=" + str + "&idcat=" + this.idcat + "&next=" + this.next);
                    doAction = true;
                } else {
                    /* This ERROR should never happen, i.e. the property idcat will not
                       be reseted once set. */
                    err_str = "<?php echo i18n("Overview cannot be displayed"); ?>";
                }
            }
            break;

        /* Edit article properties */
        case 'con_editart':
            if (this.lang != 0 && this.idlang != 0 && this.lang != this.idlang)
            {
                err_str = "<?php echo i18n("Editor can't be displayed")."<br>".i18n("Can't edit articles in foreign languages."); ?>";

                if (parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0"))
                {
                    menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0");
                    parent.parent.frames["right"].frames["right_top"].sub.click(menuItem);
                }
            } else {
                /* Check if required parameters are set  */
                if ( 0 != this.idart ) {
                    url_str = this.sessUrl(this.filename + "area=" + str + "&action=con_edit&idart=" + this.idart + "&idcat=" + this.idcat);
                    doAction = true;
                } else {
                    /* There is no selected article,
                       we do not have the neccessary
                       data to display the Article-
                       properties mask */
                    err_str = "<?php echo i18n("Article can't be displayed")."<br>".i18n("No article was selected"); ?>";

                    if ( parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0") ) {
                        menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0");
                        parent.parent.frames["right"].frames["right_top"].sub.click(menuItem);
                    }
                }
            }
            break;

        /* Template configuration */
        case 'con_tplcfg':

            /* Check if required parameters are set  */
            if (this.lang != 0 && this.idlang != 0 && this.lang != this.idlang)
            {
                err_str = "<?php echo i18n("Editor can't be displayed")."<br>".i18n("Can't edit articles in foreign languages."); ?>";

                if ( parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0") ) {
                    menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0");
                    parent.parent.frames["right"].frames["right_top"].sub.click(menuItem);
                }
            } else {
                if ( 0 != this.idart && 0 != this.idcat ) {
                    url_str = this.sessUrl(this.filename + "area=" + str + "&action=tplcfg_edit&idart=" + this.idart + "&idcat=" + this.idcat);
                    doAction = true;
                } else {
                    /* There is no selected article,
                       we do not have the neccessary
                       data to display the Template-
                       configuration mask */
                    err_str = "<?php echo i18n("Template configuration can't be displayed")."<br>".i18n("No article was selected"); ?>";

                    if ( parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0") ) {
                        menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0");
                        parent.parent.frames["right"].frames["right_top"].sub.click(menuItem);
                    }
                }
            }
            break;

        /* Edit article */
        case 'con_editcontent':
            if (this.lang != 0 && this.idlang != 0 && this.lang != this.idlang)
            {
                err_str = "<?php echo i18n("Editor can't be displayed")."<br>".i18n("Can't edit articles in foreign languages."); ?>";

                if ( parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0") ) {
                    menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0");
                    parent.parent.frames["right"].frames["right_top"].sub.click(menuItem);
                }
            } else {

                /* Check if required parameters are set  */
                if ( 0 != this.idart && 0 != this.idartlang && 0 != this.idcat ) {
                    url_str = this.sessUrl(this.filename + "area=" + str + "&action=con_editart&changeview=edit&idart=" + this.idart + "&idartlang=" + this.idartlang + "&idcat=" + this.idcat);
                    doAction = true;
                } else {
                    /* There is no selected article,
                       we do not have the neccessary
                       data to display the Editor */
                    err_str = "<?php echo i18n("Editor can't be displayed")."<br>".i18n("No article was selected"); ?>";

                    if ( parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0") ) {
                        menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0");
                        parent.parent.frames["right"].frames["right_top"].sub.click(menuItem);
                    }
                }
            }
            break;

        /* Preview article */
        case 'con_preview':

            /* Check if required parameters are set  */
            if ( 0 != this.idart && 0 != this.idartlang && 0 != this.idcat ) {
                url_str = this.sessUrl(this.filename + "area=con_editcontent&action=con_editart&changeview=prev&idart=" + this.idart + "&idartlang=" + this.idartlang + "&idcat=" + this.idcat + "&tmpchangelang="+ this.idlang);
                doAction = true;
            } else {
                /* There is no selected article,
                   we do not have the neccessary
                   data to display the Editor */
                if ( parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0") ) {
                    menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0");
                    parent.parent.frames["right"].frames["right_top"].sub.click(menuItem);
                }
                err_str = "<?php echo i18n("Preview can't be displayed")."<br>".i18n("No article was selected"); ?>";
            }
            break;

        /* Meta article */
        case 'con_meta':
            if (this.lang != 0 && this.idlang != 0 && this.lang != this.idlang)
            {
                err_str = "<?php echo i18n("Editor can't be displayed")."<br>".i18n("Can't edit articles in foreign languages."); ?>";

                if (parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0"))
                {
                    menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0");
                    parent.parent.frames["right"].frames["right_top"].sub.click(menuItem);
                }
            } else {
                /* Check if required parameters are set  */
                if ( 0 != this.idart && 0 != this.idcat ) {
                    url_str = this.sessUrl(this.filename + "area=" + str + "&action=con_meta_edit&idart=" + this.idart + "&idcat=" + this.idcat);
                    doAction = true;
                } else {
                    /* There is no selected article,
                       we do not have the neccessary
                       data to display the Article-
                       properties mask */
                    err_str = "<?php echo i18n("Article can't be displayed")."<br>".i18n("No article was selected"); ?>";

                    if ( parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0") ) {
                        menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0");
                        parent.parent.frames["right"].frames["right_top"].sub.click(menuItem);
                    }
                }
            }
            break;

        /* Content: list of all content_type */
        case 'con_content_list':
            if (this.lang != 0 && this.idlang != 0 && this.lang != this.idlang)
            {
                err_str = "<?php echo i18n("Editor can't be displayed")."<br>".i18n("Can't edit articles in foreign languages."); ?>";

                if ( parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0") ) {
                    menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0");
                    parent.parent.frames["right"].frames["right_top"].sub.click(menuItem);
                }
            } else {

                /* Check if required parameters are set  */
                if ( 0 != this.idart && 0 != this.idartlang && 0 != this.idcat ) {
                    url_str = this.sessUrl(this.filename + "area=" + str + "&action=con_content&changeview=edit&idart=" + this.idart + "&idartlang=" + this.idartlang + "&idcat=" + this.idcat + "&client=" + this.client + "&lang=" + this.lang);
                    doAction = true;
                } else {
                    /* There is no selected article,
                       we do not have the neccessary
                       data to display the Editor */
                    err_str = "<?php echo i18n("Editor can't be displayed")."<br>".i18n("No article was selected"); ?>";

                    if ( parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0") ) {
                        menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0");
                        parent.parent.frames["right"].frames["right_top"].sub.click(menuItem);
                    }
                }
            }
            break;

        default:
            if (this.customTabs[str])
            {
                var obj = this.customTabs[str];
                if ( 0 != this.idart && 0 != this.idartlang && 0 != this.idcat ) {
                    url_str = this.sessUrl(this.filename + "area=" + obj["area"] + "&action=" + obj["action"] + "&idart=" + this.idart + "&idartlang=" + this.idartlang + "&idcat=" + this.idcat + "&tmpchangelang="+ this.idlang + "&" + obj["custom"]);
                    doAction = true;
                } else {
                    /* There is no selected article,
                       we do not have the neccessary
                       data to display the Editor */
                    if ( parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0") ) {
                        menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById("c_0");
                        parent.parent.frames["right"].frames["right_top"].sub.click(menuItem);
                    }
                    err_str = "<?php echo i18n("Tab can't be displayed")."<br>".i18n("No article was selected"); ?>";
                }
            }
            break;
    }

    if (doAction) {
        this.actionFrame.location.href = url_str;
        return true;
    } else {
        box.notify(headline, err_str);
    }

    return false;
}

/**
 * Define article and category related properties
 *
 * @return void
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
articleObject.prototype.setProperties = function()
{
    this.idart      = arguments[0];
    this.idartlang  = arguments[1];
    this.idcat      = arguments[2];
    this.idcatlang  = arguments[3];
    this.idcatart   = arguments[4];
    this.idlang        = arguments[5];
}

/**
 * Disables the navigation
 *
 * @param none
 * @return void
 */
articleObject.prototype.disable = function()
{
    var oRef = [];

    oRef[0] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_0" );
    oRef[1] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_1" );
    oRef[2] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_2" );
    oRef[3] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_3" );
    oRef[4] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_4" );
    oRef[5] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_5" );
    oRef[6] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_6" );
    oRef[7] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_7" );

    if (this.vis == 1)
    {
        for (i=1; i<oRef.length; i++)
        {
            links = oRef[i].getElementsByTagName("a");
            links[0].style.visibility = "hidden";
        }
        parent.parent.frames["right"].frames["right_top"].sub.clicked(oRef[0].getElementsByTagName('a')[0]);

        // This deselects the selected submenu item
        // parent.parent.frames["right"].frames["right_top"].sub.click(oRef[0]);
        // parent.parent.frames["right"].frames["right_top"].sub.markedRow.style.backgroundColor = "#C6C6D5";
    }

    this.vis = 0;

} // end function

/**
 * Enables the navigation
 *
 * @param none
 * @return void
 */
articleObject.prototype.enable = function()
{
    var oRef = [];

    oRef[0] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_0" );
    oRef[1] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_1" );
    oRef[2] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_2" );
    oRef[3] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_3" );
    oRef[4] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_4" );
    oRef[5] = parent.parent.frames["right"].frames["right_top"].document.getElementById( "c_5" );

    if ( this.vis == 0 )
    {
        for (i=0; i<oRef.length; i++)
        {
            links = oRef[i].getElementsByTagName("a");
            links[0].style.visibility = "visible";
        }
        parent.parent.frames["right"].frames["right_top"].sub.clicked(oRef[0].getElementsByTagName('a')[0]);
    }

    this.vis = 1;
} // end function