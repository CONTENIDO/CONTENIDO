/*****************************************
*
* $Id: addImageTags.js,v 1.0 2008/02/25 13:41:52 timo.trautmann Exp $
*
* File      :   $RCSfile: addImageTags.js,v $
* Project   : Contenido
* Descr     : File contains functions vor adding label tags to images
                  by javascript for smaller html documents
*
* Author    :   $Author: timo.trautmann$
* Modified  :   $Date: 2008/02/25 13:41:52 $
*
* © four for business AG, www.4fb.de
******************************************/

/**
 * Function sets alt and title tag of a html image tag
 * 
 * @param object oImg - reference to corresponding image tag
 * @param string sName - labeltext to append
 * 
 */   
function setImgAltTitle (oImg, sName) {
    if (!oImg.title) {
        oImg.title = sName;
    }
    
    if (!oImg.alt) {
        oImg.alt = sName;
    }
}

/**
 * Function sets alt and title tag for all image tags in actual document
 * 
 * @param array aImageTitles - assocaiative array, which contains a label for image
                                                    aImageTitles['test.gif'] = 'This is a test'
 * 
 */  
function setImageTags (aImageTitles) {
    //get all images
    var images = document.getElementsByTagName('img');
  
    for (var i = 0; i < images.length; i++) {
        var sImgSrc = images[i].src;
        if (sImgSrc != '') {
            //seperate imagepath and name
            var iPos = sImgSrc.lastIndexOf('/');
            if (iPos > 0) {
                var sImgName = sImgSrc.slice(iPos+1, sImgSrc.length);
            } else {
                var sImgName = sImgSrc;
            }
            
            //if there is a imagetitle for actual image, set it
            if (aImageTitles[sImgName]) {
                setImgAltTitle(images[i], aImageTitles[sImgName]);   
            }
        }
    }
}