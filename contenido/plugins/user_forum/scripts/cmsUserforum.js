/**
 * @package Core
 * @subpackage Content Type
 * @version SVN Revision $Rev:$
 * @author Fulai Zhang, Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * Creates a new cContentTypePifaForm with the given properties. You most
 * probably want to call initialise() after creating a new object of this class.
 *
 * @constructor
 * @property {String} frameId The ID of the frame in which the content type can
 *           be set up.
 * @property {String} imageId The ID of the button on which one clicks in order
 *           to edit the content type.
 * @property {String} pathBackend The path to the CONTENIDO backend.
 * @property {String} pathFrontend The path to the CONTENIDO frontend.
 * @property {Number} idArtLang The idArtLang of the article which is currently
 *           being edited.
 * @property {Number} id The ID of the content type, e.g. 3 if CMS_TEASER[3] is
 *           used.
 * @property {Array} fields Form element names which are used by this content
 *           type.
 * @property {String} prefix The prefix of the content type.
 * @property {String} session The session ID of the admin user.
 * @property {Object|String} settings The settings of this content type.
 */
function cContentTypeUserForum(frameId, imageId, pathBackend, pathFrontend,
        idArtLang, id, fields, prefix, session, settings) {
    // call the constructor of the parent class with the same arguments
    cContentTypeAbstractTabbed.apply(this, arguments);
}

cContentTypeUserForum.prototype = new cContentTypeAbstractTabbed();
cContentTypeUserForum.prototype.constructor = cContentTypeUserForum;


    


