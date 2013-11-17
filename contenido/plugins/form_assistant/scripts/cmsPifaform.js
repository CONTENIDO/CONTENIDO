/**
 * @module  content-type
 * @submodule  content-type-pifa-form
 * @package Core
 * @subpackage Content Type
 * @version SVN Revision $Rev:$
 * @author Fulai Zhang, Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

(function(Con, $) {

    var NAME = 'content-type-pifa-form';

    /**
     * Creates a new cContentTypePifaForm with the given properties. You most
     * probably want to call initialise() after creating a new object of this class.
     *
     * @class cContentTypePifaForm
     * @constructor
     * @param {String} frameId The ID of the frame in which the content type can
     *           be set up.
     * @param {String} imageId The ID of the button on which one clicks in order
     *           to edit the content type.
     * @param {String} pathBackend The path to the CONTENIDO backend.
     * @param {String} pathFrontend The path to the CONTENIDO frontend.
     * @param {Number} idArtLang The idArtLang of the article which is currently
     *           being edited.
     * @param {Number} id The ID of the content type, e.g. 3 if CMS_TEASER[3] is
     *           used.
     * @param {Array} fields Form element names which are used by this content
     *           type.
     * @param {String} prefix The prefix of the content type.
     * @param {String} session The session ID of the admin user.
     * @param {Object|String} settings The settings of this content type.
     */
    function cContentTypePifaForm(frameId, imageId, pathBackend, pathFrontend,
            idArtLang, id, fields, prefix, session, settings) {
        // call the constructor of the parent class with the same arguments
        cContentTypeAbstractTabbed.apply(this, arguments);
    }

    // inherit from cContentTypeAbstractTabbed
    cContentTypePifaForm.prototype = new cContentTypeAbstractTabbed();

    // correct the constructor function (it points to the cContentTypeAbstractTabbed
    // constructor)
    cContentTypePifaForm.prototype.constructor = cContentTypePifaForm;

    Con.cContentTypePifaForm = cContentTypePifaForm;

    // @deprecated [2013-11-15] Assign to windows scope (downwards compatibility)
    window.cContentTypePifaForm = cContentTypePifaForm;

})(Con, Con.$);
