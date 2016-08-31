/**
 * infoBox Class
 *
 * Displays some text somewhere =/
 *
 * @deprecated [2015-05-21] This file is no longer supported
 */
function infoBox(objId) {
    this.objId = objId;
    this.obj = document.getElementById(this.objId);
    this.oldText = this.obj.innerHTML;
}

/**
 * @deprecated [2015-05-21] This file is no longer supported
 */
infoBox.prototype.show = function (text) {
    this.obj.innerHTML = text || this.oldText;
};
