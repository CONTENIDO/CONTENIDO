<?php
/**
 * @deprecated [2015-05-21] This file is not longer supported
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Moving article related logic to the front_end
 *
 * @package    CONTENIDO Backend Scripts
 * @version    SVN Revision $Rev$
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.7
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
include_once('../includes/startup.php');

header('Content-Type: application/javascript');

cRegistry::bootstrap(array(
    'sess' => 'cSession',
    'auth' => 'cAuthHandlerBackend',
    'perm' => 'cPermission'
));

i18nInit($cfg['path']['contenido_locale'], $belang);
require(cRegistry::getBackendPath() . 'includes/functions.includePluginConf.php');
// do not call cRegistry::shutdown(); here because
// it will print <script> tags which result in errors

// Fetch chains
$iterator = $_cecRegistry->getIterator('Contenido.Article.RegisterCustomTab');

$aTabs = array();
while ($chainEntry = $iterator->next()) {
    $aTmpArray = $chainEntry->execute();
    if (is_array($aTmpArray)) {
        $aTabs = array_merge($aTabs, $aTmpArray);
    }
}

$aCustomTabs = array();

foreach ($aTabs as $key => $sTab) {
    $iterator = $_cecRegistry->getIterator('Contenido.Article.GetCustomTabProperties');
    while ($chainEntry = $iterator->next()) {
        $aTmpArray = $chainEntry->execute($sTab);
        if (is_array($aTmpArray)) {
            $aCustomTabs[$sTab] = array(
                'area' => $aTmpArray[0],
                'action' => $aTmpArray[1],
                'custom' => $aTmpArray[2],
            );
            break;
        }
    }
}

$aCustomTabs['foo'] = array(
    'area' => 'foo_area',
    'action' => 'foo_action',
    'custom' => 'foo_custom',
);
$aCustomTabs['bar'] = array(
    'area' => 'bar_area',
    'action' => 'bar_action',
    'custom' => 'bar_custom',
);


?>
/**
 * @deprecated [2015-05-21] This file is not longer supported
 * Article related logic module
 *
 * @module     article-object
 * @version    SVN Revision $Rev$
 * @requires   jQuery, Con
 * @author     Unknown
 * @author     Jan Lengowski <Jan.Lengowski@4fb.de>
 * @author     Timo Trautmann <timo.trautmann@4fb.de>
 * @author     Murat Purc <murat@purc.de>
 * @version    SVN Revision $Rev$
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {
//    'use strict';

    var NAME = 'article-object';

    /**
     * Article object class
     * @class  ArticleObject
     * @constructor
     */
     Con.ArticleObject = function(actionFrameName, frameNumber) {
        /* Name of the Actionframe. Defaults to 'right_bottom' */
        this.actionFrameName = actionFrameName || 'right_bottom';

        /* Reference to the Actionframe */
        this.actionFrame = Con.getFrame(this.actionFrameName);

        /* Frame-number. Defaults to '4' */
        this.frame      = frameNumber || 4;

        /* Reference to navigation frame */
        this.navFrame = Con.getFrame('right_top');

        /* Filename of the CONTENIDO main file - defaults to 'main.php' */
        this.filename   = 'main.php?';

        /* CONTENIDO session name - defaults to 'contenido' */
        this.sessionName = 'contenido';

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
        this.idlang     = 0;

        /* Menu visible / invisible */
        this.vis        = 1;

        this.customTabs = new Array();

        /* Href of OverviewPage */
        this.hrefOverview = null;

        /* Dynamically created custom tabs */
<?php
$cutomTabsJs = '';
$prefix = str_repeat(' ', 8);
foreach ($aCustomTabs as $key => $params) {
    $cutomTabsJs .= $prefix . "this.customTabs.{$key} = {area: '{$params['area']}', action: '{$params['action']}', custom: '{$params['custom']}'};\n";
}
echo $cutomTabsJs;
?>

    };

    Con.ArticleObject.prototype = {
        /**
         * Define required global variables
         * @method setGlobalVars
         * @param  {String}  sessid  Session id
         * @param  {Number}  client  Client id
         * @param  {Number}  lang  Language id
         */
        setGlobalVars: function(sessid, client, lang) {
            this.sessid = sessid;
            this.client = client;
            this.lang   = lang;
        },

        /**
         * Sets href to overview page, which was last visited
         * @method setHrefOverview
         * @param  {String}  href
         */
        setHrefOverview: function(href) {
            // copy url - cut all actions
            if (href.match(/backend_search.php$/g)) {
                this.hrefOverview = 'javascript:Con.getFrame(\'left_top\').document.getElementById(\'backend_search\').submit.click();';
            } else if (href.match(/backend_search/g) || href.match(/area=con_workflow/g)) {
                this.hrefOverview = href.replace(/action=([^&]*)&?/g, '');
            } else {
                this.hrefOverview = null;
            }
        },

        /**
         * Reset properties
         * @method reset
         */
        reset: function() {
            this.idart = 0;
            this.idartlang = 0;
            this.idcatlang = 0;
            this.idcatart = 0;
            this.idlang = 0;
        },

        /**
         * Adds the frame and session parameter to the given url
         * @method sessUrl
         * @param  {String}  str
         * @return {String}  String with attached frame & session parameters
         */
        sessUrl: function(str) {
            var url = str + '&frame=' + this.frame + '&' + this.sessionName + '=' + this.sessid;
            return url;
        },

        /**
         * Execute an action.
         * @method doAction
         * @param   {String}  str  The action to execute
         * @return  {Boolean}  Action executes Yes/No
         */
        doAction: function(str) {
            // Flag if action will be executed
            var doAction = false;

            // Notify Headline
            var headline = "<?php echo i18n("Error"); ?>";

            // Default error string
            var error = "<?php echo i18n("Error"); ?>";

            var url = '';

            switch (str) {
                // Article overview mask
                case 'con':
                    // Check if required parameters are set
                    if (this.hrefOverview) {
                        url = this.hrefOverview;
                        doAction = true;
                    } else {
                        if (0 != this.idcat) {
                            url = this.sessUrl(this.filename + 'area=' + str + '&idcat=' + this.idcat + '&next=' + this.next);
                            doAction = true;
                        } else {
                            // This ERROR should never happen, i.e. the property idcat will not
                            // be reseted once set.
                            error = "<?php echo i18n("Overview cannot be displayed"); ?>";
                        }
                    }
                    break;

                // Edit article properties
                case 'con_editart':
                    if (this.lang != 0 && this.idlang != 0 && this.lang != this.idlang) {
                        error = "<?php echo i18n("Editor can't be displayed") . '<br>' . i18n("Can't edit articles in foreign languages."); ?>";
                        Con.markSubmenuItem('c_0');
                    } else {
                        // Check if required parameters are set
                        if (0 != this.idart) {
                            url = this.sessUrl(this.filename + 'area=' + str + '&action=con_edit&idart=' + this.idart + '&idcat=' + this.idcat);
                            doAction = true;
                        } else {
                            // There is no selected article, we do not have the neccessary
                            // data to display the Article- properties mask
                            error = "<?php echo i18n("Article can't be displayed") . '<br>' . i18n("No article was selected"); ?>";
                            Con.markSubmenuItem('c_0');
                        }
                    }
                    break;

                // Template configuration
                case 'con_tplcfg':
                    // Check if required parameters are set
                    if (this.lang != 0 && this.idlang != 0 && this.lang != this.idlang) {
                        error = "<?php echo i18n("Editor can't be displayed") . '<br>' . i18n("Can't edit articles in foreign languages."); ?>";
                        Con.markSubmenuItem('c_0');
                    } else {
                        if (0 != this.idart && 0 != this.idcat) {
                            url = this.sessUrl(this.filename + 'area=' + str + '&action=tplcfg_edit&idart=' + this.idart + '&idcat=' + this.idcat);
                            doAction = true;
                        } else {
                            // There is no selected article, we do not have the neccessary
                            // data to display the Template- configuration mask
                            error = "<?php echo i18n("Template configuration can't be displayed") . '<br>' . i18n("No article was selected"); ?>";
                            Con.markSubmenuItem('c_0');
                        }
                    }
                    break;

                // Edit article
                case 'con_editcontent':
                    if (this.lang != 0 && this.idlang != 0 && this.lang != this.idlang) {
                        error = "<?php echo i18n("Editor can't be displayed") . '<br>' . i18n("Can't edit articles in foreign languages."); ?>";
                        Con.markSubmenuItem('c_0');
                    } else {
                        // Check if required parameters are set
                        if (0 != this.idart && 0 != this.idartlang && 0 != this.idcat) {
                            url = this.sessUrl(this.filename + 'area=' + str + '&action=con_editart&changeview=edit&idart=' + this.idart + '&idartlang=' + this.idartlang + '&idcat=' + this.idcat);
                            doAction = true;
                        } else {
                            // There is no selected article, we do not have the neccessary data to display the Editor
                            error = "<?php echo i18n("Editor can't be displayed") . '<br>' . i18n("No article was selected"); ?>";
                            Con.markSubmenuItem('c_0');
                        }
                    }
                    break;

                // Preview article
                case 'con_preview':
                    // Check if required parameters are set
                    if (0 != this.idart && 0 != this.idartlang && 0 != this.idcat) {
                        url = this.sessUrl(this.filename + 'area=con_editcontent&action=con_editart&changeview=prev&idart=' + this.idart + '&idartlang=' + this.idartlang + '&idcat=' + this.idcat + '&tmpchangelang='+ this.idlang);
                        doAction = true;
                    } else {
                        // There is no selected article, we do not have the neccessary
                        // data to display the Editor
                        error = "<?php echo i18n("Preview can't be displayed") . '<br>' . i18n("No article was selected"); ?>";
                        Con.markSubmenuItem('c_0');
                    }
                    break;

                // Meta article
                case 'con_meta':
                    if (this.lang != 0 && this.idlang != 0 && this.lang != this.idlang) {
                        error = "<?php echo i18n("Editor can't be displayed") . '<br>' . i18n("Can't edit articles in foreign languages."); ?>";
                        Con.markSubmenuItem('c_0');
                    } else {
                        // Check if required parameters are set
                        if (0 != this.idart && 0 != this.idcat) {
                            url = this.sessUrl(this.filename + 'area=' + str + '&action=con_meta_edit&idart=' + this.idart + '&idcat=' + this.idcat);
                            doAction = true;
                        } else {
                            // There is no selected article, we do not have the neccessary
                            // data to display the Article- properties mask
                            error = "<?php echo i18n("Article can't be displayed") . '<br>' . i18n("No article was selected"); ?>";
                            Con.markSubmenuItem('c_0');
                        }
                    }
                    break;

                // Content: list of all content_type
                case 'con_content_list':
                    if (this.lang != 0 && this.idlang != 0 && this.lang != this.idlang) {
                        error = "<?php echo i18n("Editor can't be displayed") . '<br>' . i18n("Can't edit articles in foreign languages."); ?>";
                        Con.markSubmenuItem('c_0');
                    } else {
                        // Check if required parameters are set
                        if (0 != this.idart && 0 != this.idartlang && 0 != this.idcat) {
                            url = this.sessUrl(this.filename + 'area=' + str + '&action=con_content&changeview=edit&idart=' + this.idart + '&idartlang=' + this.idartlang + '&idcat=' + this.idcat + '&client=' + this.client + '&lang=' + this.lang);
                            doAction = true;
                        } else {
                            // There is no selected article, we do not have the neccessary
                            // data to display the Editor
                            error = "<?php echo i18n("Editor can't be displayed") . '<br>' . i18n("No article was selected"); ?>";
                            Con.markSubmenuItem('c_0');
                        }
                    }
                    break;

                default:
                    if (this.customTabs[str]) {
                        var obj = this.customTabs[str];
                        if (0 != this.idart && 0 != this.idartlang && 0 != this.idcat) {
                            url = this.sessUrl(this.filename + 'area=' + obj.area + '&action=' + obj.action + '&idart=' + this.idart + '&idartlang=' + this.idartlang + '&idcat=' + this.idcat + '&tmpchangelang='+ this.idlang + '&' + obj.custom);
                            doAction = true;
                        } else {
                            // There is no selected article, we do not have the neccessary
                            // data to display the Editor
                            error = "<?php echo i18n("Tab can't be displayed") . '<br>' . i18n("No article was selected"); ?>";
                            Con.markSubmenuItem('c_0');
                        }
                    }
                    break;
            }

            if (doAction) {
                this.actionFrame.location.href = url;
                return true;
            } else {
                Con.showNotification(headline, error);
            }

            return false;
        },

        /**
         * Define article and category related properties
         * @method setProperties
         * @param  {Number}  idart
         * @param  {Number}  idartlang
         * @param  {Number}  idcat
         * @param  {Number}  idcatlang
         * @param  {Number}  idcatart
         * @param  {Number}  idlang
         */
        setProperties: function(idart, idartlang, idcat, idcatlang, idcatart, idlang) {
            this.idart      = idart;
            this.idartlang  = idartlang;
            this.idcat      = idcat;
            this.idcatlang  = idcatlang;
            this.idcatart   = idcatart;
            this.idlang     = idlang;
        },

        /**
         * Disables the navigation
         * @method disable
         */
        disable: function() {
            var oDoc = $(this.navFrame.document);
            var index = 0;

            if (this.vis == 1) {
                oDoc.find('ul#navlist li').each(function() {
                    if (index > 0) {
                        $(this).css('visibility', 'hidden');
                    }
                    index++;
                });

                this.navFrame.Con.Subnav.clickedById(oDoc.find('ul#navlist li:nth-child(1)').attr('id'));
            }

            this.vis = 0;
        },

        /**
         * Disables the navigation
         * @method disableNavForNewArt
         */
        disableNavForNewArt: function() {
            var oDoc = $(this.navFrame.document);
            var index = 0;

            oDoc.find('ul#navlist li').each(function() {
                if (index > 1) {
                    $(this).css('visibility', 'hidden');
                }
                index++;
            });

            this.navFrame.Con.Subnav.clickedById(oDoc.find('ul#navlist li:nth-child(2)').attr('id'));
        },

        /**
         * Enables the navigation
         * @method enableNavForArt
         */
        enableNavForArt: function() {
            var oDoc = $(this.navFrame.document);
            var index = 0;

            oDoc.find('ul#navlist li').each(function() {
                if (index > 1) {
                    $(this).css('visibility', 'visible');
                }
                index++;
            });

            this.navFrame.Con.Subnav.clickedById(oDoc.find('ul#navlist li:nth-child(2)').attr('id'));
        },

        /**
         * Enables the navigation
         * @method enable
         */
        enable: function() {
            var oDoc = $(this.navFrame.document);
            var index = 0;

            if (this.vis == 0) {
                oDoc.find('ul#navlist li').each(function() {
                    if (index > 0) {
                        $(this).css('visibility', 'visible');
                    }
                    index++;
                });

                this.navFrame.Con.Subnav.clickedById(oDoc.find('ul#navlist li:nth-child(1)').attr('id'));
            }

            this.vis = 1;
        }

    };

})(Con, Con.$);
