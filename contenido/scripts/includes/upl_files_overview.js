/**
 * CONTENIDO JavaScript upl_files_overview.js module
 *
 * @version    SVN Revision $Rev: 5937 $
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @todo       Rework this, make a CONTENIDO module
 */
(function(Con, $) {

    // to reload the left frame after delete/insert new files
    var frame = Con.getFrame('left_bottom');
    if (frame.location !== 'about:blank') {
        frame.location.href = Con.UtilUrl.replaceParams(frame.location.href, {action: null});
    }

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

    // @todo Use $(element).offset();
    function getY(e) {
        var y = 0;
        while (e) {
            y += e.offsetTop;
            e = e.offsetParent;
        }
        return y;
    }

    // @todo Use $(element).offset();
    function getX(e) {
        var x = 0;
        while (e) {
            x += e.offsetLeft;
            e = e.offsetParent;
        }
        return x;
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
        previewImage.style.marginTop = getY(theImage);
        previewImage.style.marginLeft = getX(theImage) + 100;
    }

    window.invertSelection = invertSelection;
    window.getY = getY;
    window.getX = getX;
    window.findPreviewImage = findPreviewImage;
    window.correctPosition = correctPosition;

})(Con, Con.$);
