/**
 * CONTENIDO JavaScript upl_files_overview.js module
 *
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @todo       Rework this, make a CONTENIDO module
 */
(function(Con, $) {
    $(function() {
        var $body = $("body");

        // Handler for clicked image anchors
        $body.delegate("a.jsZoom", "click", function() {
            iZoom($(this).attr("href"));
            return false;
        });

        // Handler for mouseover/mouseout on images
        $body.delegate("a.jsZoom img.hover", "mouseover", function() {
            correctPosition(this, $(this).attr("data-width"), $(this).attr("data-height"));
        });
        $body.delegate("a.jsZoom img.hover", "mouseout", function() {
            if ("function" === typeof (previewHideIe6)) {
                previewHideIe6(this);
            }
        });
    });

    // Invert selection of checkboxes
    function invertSelection() {
        var delcheckboxes = document.getElementsByName("fdelete[]"), i;

        for (i = 0; i < delcheckboxes.length; i++) {
            delcheckboxes[i].checked = !(delcheckboxes[i].checked);
        }
    }

    /**
     * Returns top positition relative to document
     * @param {HTMLElement} e
     * @return {Number}
     */
    function _getY(e) {
        var offset = $(e).offset();
        return offset ? offset.top : 0;
    }

    /**
     * Returns left positition relative to document
     * @param {HTMLElement} e
     * @return {Number}
     */
    function _getX(e) {
        var offset = $(e).offset();
        return offset ? offset.left : 0;
    }

    function findPreviewImage(smallImg) {
        var prevImages = document.getElementsByName("prevImage");

        for (var i = 0; i < prevImages.length; i++) {
            if (prevImages[i].src === smallImg.src) {
                return prevImages[i];
            }
        }
    }

    // Hoverbox
    function correctPosition(theImage, iWidth, iHeight) {
        var previewImage = findPreviewImage(theImage);

        if ("function" === typeof (previewShowIe6)) {
            previewShowIe6(previewImage);
        }
        previewImage.style.width = iWidth;
        previewImage.style.height = iHeight;
        previewImage.style.marginTop = _getY(theImage) + "px";
        previewImage.style.marginLeft = (_getX(theImage) + 100) + "px";
    }

    window.invertSelection = invertSelection;
    window.getY = _getY;
    window.getX = _getX;
    window.findPreviewImage = findPreviewImage;
    window.correctPosition = correctPosition;

})(Con, Con.$);
