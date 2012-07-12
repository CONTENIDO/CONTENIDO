function showDelMsg(strElement, path, file, page) {
    box.confirm("'.$sDelTitle.'", "'.$sDelDescr.'<b>" + strElement + "</b>", "deleteFile(\'" + path + "\', \'" + file + "\', " + page + ")");
}

// Function for deleting items
function deleteFile(path, file, page) {
    url  = \'main.php?area=upl\';
    url += \'&action=upl_delete\';
    url += \'&frame=4\';
    url += \'&path=\' + path;
    url += \'&file=\' + file;
    url += \'&startpage=\' + page;
    url += \'&contenido=\' + sid;
    url += \'&appendparameters='.$appendparameters.'\';

    window.location.href = url;
}

function renameFile (oldname, path, page) {
    var newname = prompt("{RENAME}", oldname),
        url;

    if (newname) {
        url  = \'main.php?area=upl\';
        url += \'&action=upl_renamefile\';
        url += \'&frame=4\';
        url += \'&newname=\' + newname;
        url += \'&oldname=\' + oldname;
        url += \'&startpage=\' + page;
        url += \'&path=\' + path;
        url += \'&contenido=\' + sid;

        window.location.href = url;
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
    var prevImages = document.getElementsByName("prevImage"),

        i;
    for (i=0; i<prevImages.length; i++) {
        if (prevImages[i].src == smallImg.src) {
            return prevImages[i];
        }
    }
}

// Hoverbox
function correctPosition(theImage, iWidth, iHeight) {
    var previewImage = findPreviewImage(theImage);

    if (typeof(previewShowIe6) == "function") {
        previewShowIe6(previewImage);
    }
    previewImage.style.width = iWidth;
    previewImage.style.height = iHeight;
    previewImage.style.marginTop = getY(theImage);
    previewImage.style.marginLeft = getX(theImage) + 100;
}

// Invert selection of checkboxes
function invertSelection() {
    var delcheckboxes = document.getElementsByName("fdelete[]"),

        i;
    for (i=0; i<delcheckboxes.length; i++) {
        delcheckboxes[i].checked = !(delcheckboxes[i].checked);
    }
}

if (parent.parent.frames["right"].frames["right_top"].document.getElementById(\'c_0\')) {
    menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById(\'c_0\');
    parent.parent.frames["right"].frames["right_top"].sub.clicked(menuItem.firstChild);
}