var bMsie = (document.all) ? true : false;

var sExpandString = '';
var sCollapseString = '';
var sCollapseButton = './images/close_all.gif';
var sExpandButton = './images/open_all.gif';

function init(transOpen, transClose) {
    sCollapseString = transClose;
    sExpandString = transOpen;

    var aStatrows = document.getElementsByTagName('tr');
    var sDisplay = '';

    if (bMsie) {
        sDisplay = 'block';
    } else {
        sDisplay = 'table-row';
    }
    var preButton = null;
    var preAIds = null;
    var level = 1;

    for (var i = 0; i < aStatrows.length; i++) {
        if (aStatrows[i].id) {
            aStatrows[i].style.display = sDisplay;

            var oButton = aStatrows[i].getElementsByTagName('img')[1];
            var aIds = aStatrows[i].id.split('_');

            if (oButton && oButton.id == aStatrows[i].id+'_img') {
                if (aIds.length > level) {
                    preButton.src = sCollapseButton;
                    preButton.title = sCollapseString;
                    preButton.alt = sCollapseString;
                    preButton.parentNode.href = 'javascript:changeVisibility(\''+aStatrows[(i-1)].id+'\', '+(preAIds.length-1)+', '+aIds[preAIds.length-1]+');';
                }
                preButton = oButton;
                preAIds = aIds;
                level = aIds.length;
            }
        }
    }
}

function changeVisibility (sIdClicked, iLevel, iIdCat) {
    var sDisplay = '';
    var aIdsClicked = sIdClicked.split('_');

    var oButton = document.getElementById (sIdClicked+'_img');

    if (oButton.src.match(/open_all.gif/)) {
        oButton.src = sCollapseButton;
        oButton.title = sCollapseString;
        oButton.alt = sCollapseString;
        if (bMsie) {
            sDisplay = 'block';
        } else {
            sDisplay = 'table-row';
        }
    } else {
        oButton.src = sExpandButton;
        oButton.title = sExpandString;
        oButton.alt = sExpandString;
        sDisplay = 'none';
    }

    var preButton = null;
    var level = iLevel;
    var aStatrows = document.getElementsByTagName('tr');

    for (var i = 0; i < aStatrows.length; i++) {
        var aIds = aStatrows[i].id.split('_');

        if (aIds[iLevel] == iIdCat) {
            if (sDisplay == 'none' && aIds.length > aIdsClicked.length) {
                aStatrows[i].style.display = sDisplay;

                var oButton = aStatrows[i].getElementsByTagName('img')[1];
                if (oButton && oButton.id == aStatrows[i].id+'_img') {
                    if (aIds.length > level && preButton) {
                        preButton.src = sExpandButton;
                        preButton.title = sExpandString;
                        preButton.alt = sExpandString;
                    }
                    preButton = oButton;
                    level = aIds.length;
                }
            } else if ((aIdsClicked.length+1) == aIds.length) {
                aStatrows[i].style.display = sDisplay;
            }
        }
    }
}