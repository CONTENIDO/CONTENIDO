/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * File contains functions for displaying plugin information layer, hiding and
 * crorrecting its position, when browser window is resized
 *
 * @package    CONTENIDO Setup
 * @version    1.0
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */


/**
 * Function returns offset left, top, width and heigth of a given htnmlelement as array
 *
 * @param  {Object}  oElement - Object which should be analyzed
 * @return {Array}  Containing dimension information
 */
function getElementPostion(oElement) {
    var iHeigth = oElement.offsetHeight,
        iWidth = oElement.offsetWidth,
        iTop = 0, iLeft = 0;
    while (oElement) {
        iTop += oElement.offsetTop  || 0;
        iLeft += oElement.offsetLeft || 0;
        oElement = oElement.offsetParent;
    };
    return [iLeft, iTop, iHeigth, iWidth];
}

/**
 * Function set layer position absolutely. Basis for position is element plugin_layer,
 * which contains all avariable plugins
 */
function setLayerPostion() {
    var oPluginLayer = document.getElementById('plugin_layer'),
        aPluginListPos;
    //only correct position, if layer is currently displayed
    if (oPluginLayer.style.display == 'block') {
        aPluginListPos = getElementPostion(document.getElementById('plugin_list'));
        oPluginLayer.style.top = aPluginListPos[1];
        oPluginLayer.style.left = aPluginListPos[0]+200;
    }
}

/**
 * Function shows plugin information layer and fills it with given information
 * @param  {String}  sHeader   Name of the plugin
 * @param  {String}  sDescription  Description of the plugin
 */
function showPluginInfo(sHeader, sDescription) {
    setLayerPostion();

    //get plugin layer
    var oPluginLayer = document.getElementById('plugin_layer');

    //set layer data and display it
    document.getElementById('plugin_header').innerHTML = sHeader;
    document.getElementById('plugin_description').innerHTML = sDescription;
    oPluginLayer.style.display = 'block';
}

/**
 * Function hides plugin information layer
 */
function hidePluginInfo() {
    document.getElementById('plugin_layer').style.display = 'none';
}

//update layer position when browser window is resized
window.onresize = setLayerPostion;