/**
 * infoBox Class
 *
 * Displays some text somewhere =/
 */
function infoBox( objId ) {

    this.objId = objId;
    this.obj = document.getElementById( this.objId );
    this.oldText = this.obj.innerHTML;

} // end function

infoBox.prototype.show = function( text ) {

    this.obj.innerHTML = text || this.oldText;

} // end function


