/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Upload files dropped on a div to the CONTENIDO backend
 *
 *
 * @package    CONTENIDO Backend Scripts
 * @version    1.0.0
 * @author     Mischa Holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9
 *
 * {@internal
 *   created 2012-01-13
 *
 *   $Id$:
 * }}
 *
 */



/*
* Array keeps track of all the XHR objects
*/
var uploads = new Array();
var running_upload = null;
var upload_status = new Array();
var form_cache = new Array();

/*
* Function returns wether or not the needed JavaScript standards are supported by the browser
*
* @return bool Yes it is supported/No it is not
*/
function supportAjaxUploadProgressEvents() {
    var xhr = new XMLHttpRequest();
    return !! (xhr && ('upload' in xhr) && ('onprogress' in xhr.upload));
}

/*
* Function aborts the upload with the id
*
* @param int id - The id of the upload
*/
function abortUpload(id) {
    var statusDiv = document.getElementById("d_bar_" + id + "_status");
    var cancelButton = document.getElementById("d_bar_" + id + "_cancelbutton");

    statusDiv.innerHTML = text_aborting;
    cancelButton.style.display = "none";

    if(running_upload == uploads[id]) {
           running_upload = null;
        for(var i = id + 1; i < uploads.length; i++) {
            if(uploads[i].readyState == 1) {
                running_upload = uploads[i];
                uploads[i].send(form_cache[i]);
                break;
            }
        }
    }
    uploads[id].abort();
}

/*
* Function returns the folder of the document currently loaded
*
* @return string - the folder starting with "http://..."
*/
function getFolderOfDocument() {
    return unescape(window.location.href.substring(0, (window.location.href.lastIndexOf("/", window.location.href.indexOf("?")) + 1)));
}

/*
* Function gets triggered when an upload is finished
*
* @param e - The event's information
*/
function onXHRLoad(e) {
       var statusDiv = document.getElementById("d_bar_" + this.upload.uploadId + "_status");
       var cancelButton = document.getElementById("d_bar_" + this.upload.uploadId + "_cancelbutton");

       //change the labels
       statusDiv.innerHTML = text_finished;
       cancelButton.style.display = "none";

    running_upload = null;

    for(var i = 0; i < uploads.length; i++) {
        if(uploads[i].readyState == 1 && running_upload == null) {
            running_upload = uploads[i];
            uploads[i].send(form_cache[i]);
        }
    }
       //if there is a response
       if(this.responseText) {
           var loadResponse = true;

           //check wether all uploads are finished or not
           for(var i = 0; i < uploads.length; i++) {
               if(uploads[i].readyState != 4) {
                   loadResponse = false;
                   break;;
               }
               if(uploads[i].status != "200") {
                   loadResponse = false;
                   break;
               }
           }

           //if all of them are finished, load the response to the frame
           if(loadResponse) {
               self.location.href = "main.php?area=upl&frame=4&path=" + upload_path + "&appendparameters=&contenido=" + contenido_id;
           }
       }
}

/*
* Function gets triggered when an upload failed due to an error
*
* @param e - The event's information
*/
function onXHRError(e) {
    var statusDiv = document.getElementById("d_bar_" + this.upload.uploadId + "_status");
       var cancelButton = document.getElementById("d_bar_" + this.upload.uploadId + "_cancelbutton");

       statusDiv.innerHTML = text_error;
       cancelButton.style.display = "none";
}

/*
* Function gets triggered when an upload is aborted by the user
*
* @param e - The event's information
*/
function onXHRAbort(e) {
    var statusDiv = document.getElementById("d_bar_" + this.upload.uploadId + "_status");
    statusDiv.innerHTML = text_aborted;
}

/*
* Function gets triggered by the browser as long as the upload goes on - This is used to update the progress bar
*
* @param e - The event's information
*/
function onXHRUploadProgress(e) {
       var percent = (e.loaded / e.total) * 100;
       var progressDiv = document.getElementById("d_bar_" + this.uploadId + "_bar");
       var statusDiv = document.getElementById("d_bar_" + this.uploadId + "_status");

       progressDiv.style.width = percent + "%";
       statusDiv.innerHTML = text_uploading;
}

/*
* Function gets triggered when the user drops something to the 'dropbox' div
*
* @param e - The event's information
*/
function onDrop(e) {
    var dt = e.dataTransfer;
    var files = dt.files;

    //go through all the files and start to upload them
    for(var i = 0; i < files.length; i++) {
        var file = files[i];
        if(file.size > maxFileSize && maxFileSize != 0) {
        	alert(file.name + ": " + $('<div />').html(text_fileIsTooBig).text());
            e.preventDefault();
        	continue;
        }
        
        var xhr = new XMLHttpRequest();

           uploads.push(xhr);
           xhr.upload.uploadId = uploads.length - 1;

           //assign the events
        xhr.onload = onXHRLoad;
        xhr.onerror = onXHRError;
        xhr.onabort = onXHRAbort;
        xhr.upload.onprogress = onXHRUploadProgress;

        //add the HTML for the progress bar, the labels and the button
        shelf.innerHTML = shelf.innerHTML + "<div class='shelf_elem' id='d_bar_" + xhr.upload.uploadId + "'><div class='shelf_elem_titlelabel' id='d_bar_" + xhr.upload.uploadId + "_title'>" + file.name + "...</div><div class='shelf_elem_progressbar_background' id='d_bar_" + xhr.upload.uploadId + "_outbar'><div class='shelf_elem_progressbar_bar' id='d_bar_" + xhr.upload.uploadId + "_bar'>&nbsp;</div></div><input class='shelf_elem_cancelbutton' id='d_bar_" + xhr.upload.uploadId + "_cancelbutton' type='button' onclick='abortUpload(" + xhr.upload.uploadId + ")' value='" + text_cancelButton + "'><div class='shelf_elem_statuslabel' id='d_bar_" + xhr.upload.uploadId + "_status'>" + text_waiting + "</div></div><br>";

           //connect to the server
           xhr.open("POST", getFolderOfDocument() + "main.php", true);

        //create the formdata and fill it
        var pData = new FormData();

        pData.append("file[]", file);
        pData.append("frame", "4");
        pData.append("area", "upl");
        pData.append("contenido", contenido_id);
        pData.append("action", "upl_upload");
        pData.append("path", upload_path);

        form_cache.push(pData);

        //send the request
        if(running_upload == null) {
            xhr.send(pData);
            running_upload = xhr;
        }

        e.preventDefault();
    } //end for
}

/*
* Function gets triggered the user drags a file over the 'dropbox' div. This is needed to tell the OS that we are going to accept the drop
*
* @param e - The event's information
*/
function onDrag(e) {
    e.preventDefault();
}

/*
* Function assigns the events and activates the applet if the browser does not support the necessary APIs
*
*/
function loadDragDrop() {
    var dropbox = document.getElementById("dropbox");
    var dropbox_area = document.getElementById("dropbox_area");
    var shelf = document.getElementById("shelf");

    if(!supportAjaxUploadProgressEvents()) {
        dropbox.innerHTML = "<applet code=org.contenido.DropboxMain archive='./jar/dnd.jar' width='500' height='100'><param name='upload_path' value='" + upload_path + "'><param name='uid' value='" + contenido_id + "'><param name='host' value='" + getFolderOfDocument() + "'></applet>";
        dropbox.style.height = "105";
        return;
    }

    dropbox.ondrop = onDrop;
    dropbox.ondragenter = onDrag;
    dropbox.ondragover = onDrag;
}

jQuery(document).ready(loadDragDrop);


