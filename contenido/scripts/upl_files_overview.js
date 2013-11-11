

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
console.log("findPreviewImage smallImg", smallImg);
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
})(Con, Con.$);
