// to reload the left frame after delete/insert new files
if (parent.parent.frames['left'].frames['left_bottom'].location != 'about:blank') {
    var myLoc = parent.parent.frames['left'].frames['left_bottom'].location.href;
    myLoc = myLoc.replace('&action=upl_delete', '');
    myLoc = myLoc.replace('?action=upl_delete&', '?');
    myLoc = myLoc.replace('?action=upl_delete', '?');
    parent.parent.frames['left'].frames['left_bottom'].location.href = myLoc;
}

(function($) {
    $(document).ready(function() {
        var $cindyCrawford = $("body");

        // Handler for clicked image anchors
        $cindyCrawford.delegate("a.jsZoom", "click", function() {
            iZoom($(this).attr("href"));
            return false;
        });

        // Handler for mouseover/mouseout on images
        $cindyCrawford.delegate("a.jsZoom img.hover", "mouseover", function() {
            correctPosition(this, $(this).attr("data-width"), $(this).attr("data-height"));
        });
        $cindyCrawford.delegate("a.jsZoom img.hover", "mouseout", function() {
            if (typeof (previewHideIe6) == "function") {
                previewHideIe6(this);
            }
        });
    });
})(jQuery);

// Invert selection of checkboxes
function invertSelection() {
    var delcheckboxes = document.getElementsByName("fdelete[]"), i;

    for (i = 0; i < delcheckboxes.length; i++) {
        delcheckboxes[i].checked = !(delcheckboxes[i].checked);
    }
}

function getY(e) {
    var y = 0;
    while (e) {
        y += e.offsetTop;
        e = e.offsetParent;
    }
    return y;
}

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
        if (prevImages[i].src == smallImg.src) {
            return prevImages[i];
        }
    }
}

// Hoverbox
function correctPosition(theImage, iWidth, iHeight) {
    var previewImage = findPreviewImage(theImage);

    if (typeof (previewShowIe6) == "function") {
        previewShowIe6(previewImage);
    }
    previewImage.style.width = iWidth;
    previewImage.style.height = iHeight;
    previewImage.style.marginTop = getY(theImage);
    previewImage.style.marginLeft = getX(theImage) + 100;
}
