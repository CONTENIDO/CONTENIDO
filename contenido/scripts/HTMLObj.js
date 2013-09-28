/**
 * HTMLObj Class
 *
 * Object of an HTML Element.
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function HTMLObj(objId) {

    this.objId = objId;
    this.obj = document.getElementById(this.objId);
    this.type = this.getElementType();
    this.id = null;
    this.status = 0; /* status for images / other elements..
                        0 - normal
                        1 - out
                        2 - locked */

} // end function

/**
 * Defines the HTML Element Type
 * and calls the setMethods() method
 * to add the corresponding methods
 * to the HTMLObj instance.
 *
 * @return type string Type of the HTML Element ('image'/'select')
 */
HTMLObj.prototype.getElementType = function() {
    var type = 'undefined';

    switch (this.obj.tagName) {
        case 'IMG':
                type = 'image';
            break;
        case 'SELECT':
                type = 'select';
            break;
    }

    if ('undefined' != type) {
        this.setMethods(type);
    }

    return type;
}

/**
 * Set methods depending on
 * the HTML Element type
 */
HTMLObj.prototype.setMethods = function(type) {
    switch (type) {

        case 'image':

            /* .over() method */
            this.over = function() {
                if ('' != this.oImgSrc) {
                    this.obj.src = this.oImgSrc;
                    this.status = "over";
                    /* If there is a corresponding label, show it */
                    if (document.getElementById(this.objId+'_label')) {
                         document.getElementById(this.objId+'_label').style.display = 'block';
                    }
                }
            }

            /* .out() method */
            this.out = function() {
                if ('' != this.nImgSrc) {
                    this.obj.src = this.nImgSrc
                    this.status = "out";
                    /* If there is a corresponding label, show it */
                    if (document.getElementById(this.objId+'_label')) {
                         document.getElementById(this.objId+'_label').style.display = 'block';
                    }
                }
            }

            /* Set image sources */
            this.setImgSrc = function(nImgSrc, oImgSrc) {
                this.nImgSrc = nImgSrc;
                this.oImgSrc = oImgSrc;
            }

            /* Set the intance id */
            this.setId = function(id) {
                /* JS Object */
                this.id = id;
                /* HTML Object */
                this.obj.id = id;
            }

            /* Lock the image */
            this.lock = function() {
                this.obj.src = "images/spacer.gif";
                /* If there is a corresponding label, hide it */
                if (document.getElementById(this.objId+'_label')) {
                    document.getElementById(this.objId+'_label').style.display = 'none';
                }
            }

            this.obj.onclick = doAction;

            //Also make corresponding Labels clickable
            if (this.obj.parentNode.nextSibling) {
                this.obj.parentNode.nextSibling.onclick = doAction;
            }

            break;

        case 'select':

            /**
             * Select an entry
             * @param string value of the entry
             * @return void
             */
            this.select = function(selectedValue) {

                var options = this.obj.getElementsByTagName('option');
                var index = 0;

                for (i = 0; i < options.length; i ++) {
                    if (selectedValue == options[i].value) {
                        index = i;
                    }
                }

                this.obj.selectedIndex = index;

            }

            /**
             * Return value of the
             * select
             */
            this.getValue = function() {
                return this.obj.value;
            }

            /**
             * Return 'object HTMLCollection' for 'options'
             * @return object HTMLCollection All 'option' objects
             */
            this.getCollection = function() {
                return this.obj.getElementsByTagName('option');
            }

            break;
    }
}

/**
 * Controls the execution of
 * the actions depending on
 * the cfg properties
 *
 * @return void
 * @author Jan Lengowski <jan.lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function doAction() {
    var str = this.src;

    //if Label is clicked, get src from previous image
    if (!str) {
        var str = this.previousSibling.firstChild.src;
    }

    // Set Category Offline
    if (str.indexOf('online.gif') != -1) {
        str  = "";
        str += "main.php?area=con";
        str += "&action=con_makecatonline";
        str += "&frame=2";
        str += "&idcat=" + cfg.catId;
        str += "&online=" + 0;
        str += "&contenido=" + sid;

        if (cfg.catId != 0 && cfg.hasRight['online']) {
            cfg.setAction(str);

            /* change image source */
            if (this.status == "out") {
                cfg.objRef[1].over()
                cfg.isOnline = (cfg.isOnline == 0) ? 1 : 0;
            } else {
                cfg.objRef[1].out()
                cfg.isOnline = (cfg.isOnline == 0) ? 1 : 0;
            }
        }
    } else if (str.indexOf('offline.gif') != -1) {
        // Set Category Online
        str  = "";
        str += "main.php?area=con";
        str += "&action=con_makecatonline";
        str += "&frame=2";
        str += "&idcat=" + cfg.catId;
        str += "&online=" + 1;
        str += "&contenido=" + sid;

        if (cfg.catId != 0  && cfg.hasRight['online']) {
            cfg.setAction(str);

            /* change image source */
            if (this.status == "out") {
                cfg.objRef[1].over()
                cfg.isOnline = (cfg.isOnline == 0) ? 1 : 0;
            } else {
                cfg.objRef[1].out()
                cfg.isOnline = (cfg.isOnline == 0) ? 1 : 0;
            }
        }
    } else if (str.indexOf('folder_delock.gif') != -1) {
        str  = "";
        str += "main.php?area=con";
        str += "&action=con_makepublic";
        str += "&frame=2";
        str += "&idcat=" + cfg.catId;
        str += "&public=" + 0;
        str += "&contenido=" + sid;

        if (cfg.catId != 0 && cfg.hasRight['public']) {
            cfg.setAction(str);

            /* change image source */
            if (this.status == "out") {
                cfg.objRef[2].over()
                cfg.isPublic = (cfg.isPublic == 0) ? 1 : 0;
            } else {
                cfg.objRef[2].out()
                cfg.isPublic = (cfg.isPublic == 0) ? 1 : 0;
            }
        }
    } else if (str.indexOf('folder_lock.gif') != -1) {
        // Set C
        str  = "";
        str += "main.php?area=con";
        str += "&action=con_makepublic";
        str += "&frame=2";
        str += "&idcat=" + cfg.catId;
        str += "&public=" + 1;
        str += "&contenido=" + sid;

        if (cfg.catId != 0 && cfg.hasRight['public']) {

            cfg.setAction(str);

            /* change image source */
            if (this.status == "out") {
                cfg.objRef[2].over();
                cfg.isPublic = (cfg.isPublic == 0) ? 1 : 0;
            } else {
                cfg.objRef[2].out();
                cfg.isPublic = (cfg.isPublic == 0) ? 1 : 0;
            }
        }
    }
}
