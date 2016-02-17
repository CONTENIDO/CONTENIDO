/**
 * Upload files dropped on a div to the CONTENIDO backend
 *
 * @module     upl-files-upload
 * @requires   jQuery, Con
 * @author     Mischa Holz
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9
 */

(function(Con, $) {
//    'use strict';

    // #########################################################################
    // Some constants

    var NAME = 'upl-files-upload';

    var TPL_SHELF = "<div class='shelf_elem' data-uploadid='{uploadid}' id='d_bar_{uploadid}'>" +
                    "    <div class='shelf_elem_titlelabel' id='d_bar_{uploadid}_title'>{filename}...</div>" +
                    "    <div class='shelf_elem_progressbar_background'>" +
                    "        <div class='shelf_elem_progressbar_bar' id='d_bar_{uploadid}_bar'>&nbsp;</div>" +
                    "    </div>" +
                    "    <input class='shelf_elem_cancelbutton' type='button' value='{textcancel}'>" +
                    "    <div class='shelf_elem_statuslabel'>{textwaiting}</div>" +
                    "</div>",
        SELECTOR_SHELF_STATUS = '#d_bar_{id} .shelf_elem_statuslabel',
        SELECTOR_SHELF_CANCEL = '#d_bar_{id} .shelf_elem_cancelbutton',
        SELECTOR_SHELF_BAR = '#d_bar_{id} .shelf_elem_progressbar_bar';


    /**
     * Drag and drop upload class
     * @class  UplFilesUpload
     * @constructor
     * @param {Object}  options  Configuration properties as follows
     * <pre>
     *    selectorDropbox  (String)  Selector for dropbox node
     *    selectorShelf  (String)  Selector for shelf node
     *    sid   (String)  Contenido session id
     *    urlHost  (String)  Contenido backend url
     *    urlUpload  (String)  Upload url
     *    uploadPath (String)  The path to upload the file
     *    maxFileSize (Number)  Upload file size in bytes
     *    text_aborting  (String)
     *    text_finished  (String)
     *    text_error  (String)
     *    text_aborted  (String)
     *    text_uploading  (String)
     *    text_cancelButton  (String)
     *    text_waiting  (String)
     *    text_fileIsTooBig  (String)
     *    onDragDropUploadDone  (Function)  Callback function for finished upload,
     *                                      gets the success state
     * </pre>
     * @return {UplFilesUpload}
     */
    Con.UplFilesUpload = function(options) {

        // #####################################################################
        // Setup and private variables

        /**
         * @property _options
         * @type {Object}
         * @private
         */
        var _options = options,
            /**
             * @property _uploads
             * @type {Array}
             * @private
             * @default []
             */
            _uploads = [],
            /**
             * @property _runningUpload
             * @type {String|Null}
             * @private
             */
            _runningUpload = null,
            /**
             * @property _formCache
             * @type {Array}
             * @private
             * @default []
             */
            _formCache = [];

        var $dropbox = $(_options.selectorDropbox);
        if (!$dropbox) {
            Con.log('Dropbox node initialization failed!', NAME, 'error');
            return;
        }
        var $shelf = $(_options.selectorShelf);
        if (!$shelf) {
            Con.log('Shelf node initialization failed!', NAME, 'error');
            return;
        }

        // #####################################################################
        // Private functions

        /**
         * Function returns wether or not the needed JavaScript standards are supported by the browser
         * @method _supportAjaxUploadProgressEvents
         * @private
         * @return {Boolean}  Yes it is supported/No it is not
         */
        var _supportAjaxUploadProgressEvents = function() {
            if ('undefined' === typeof XMLHttpRequest) {
                return false;
            }
            var xhr = new XMLHttpRequest();
            return !!(xhr && ('upload' in xhr) && ('onprogress' in xhr.upload));
        };

        /**
         * Function aborts the upload with the id
         * @method _abortUpload
         * @private
         * @param  {Number}  id  The id of the upload
         */
        var _abortUpload = function(id) {
            var statusDiv = $(SELECTOR_SHELF_STATUS.replace('{id}', id));
            var cancelButton = $(SELECTOR_SHELF_CANCEL.replace('{id}', id));

            statusDiv.html(_options.text_aborting);
            cancelButton.css('display', 'none');

            if (_runningUpload === _uploads[id]) {
                _runningUpload = null;
                for (var i = id + 1; i < _uploads.length; i++) {
                    if (_uploads[i].readyState === 1) {
                        _runningUpload = _uploads[i];
                        _uploads[i].send(_formCache[i]);
                        break;
                    }
                }
            }
            _uploads[id].abort();
        };

        /**
         * Function gets triggered when an upload is finished
         * @method _onXHRLoad
         * @private
         * @param {Event}  e  The event's information
         */
        var _onXHRLoad = function(e) {
            var statusDiv = $(SELECTOR_SHELF_STATUS.replace('{id}', this.upload.uploadId));
            var cancelButton = $(SELECTOR_SHELF_CANCEL.replace('{id}', this.upload.uploadId));
            var progressDiv = $(SELECTOR_SHELF_BAR.replace('{id}', this.upload.uploadId));

            // change the labels
            statusDiv.html(_options.text_finished);
            cancelButton.css('display', 'none');
            progressDiv.css('width', '100%');

            _runningUpload = null;

            for (var i = 0; i < _uploads.length; i++) {
                if (_uploads[i].readyState === 1 && _runningUpload === null) {
                    _runningUpload = _uploads[i];
                    _uploads[i].send(_formCache[i]);
                }
            }

            // if there is a response
            if (this.responseText) {
                var loadResponse = true;

                // check wether all _uploads are finished or not
                for (var i = 0; i < _uploads.length; i++) {
                    if (_uploads[i].readyState !== 4) {
                        loadResponse = false;
                        break;
                    }
                    if (_uploads[i].status !== 200) {
                        loadResponse = false;
                        break;
                    }
                }

                // if all of them are finished, load the response to the frame
                if ('function' === typeof _options.onDragDropUploadDone) {
                    _options.onDragDropUploadDone.call(loadResponse);
                }
            }
        };

        /**
         * Function gets triggered when an upload failed due to an error
         * @method _onXHRError
         * @private
         * @param {Event}  e  The event's information
         */
        var _onXHRError = function(e) {
            var statusDiv = $(SELECTOR_SHELF_STATUS.replace('{id}', this.upload.uploadId));
            var cancelButton = $(SELECTOR_SHELF_CANCEL.replace('{id}', this.upload.uploadId));
            statusDiv.html(_options.text_error);
            cancelButton.css('display', 'none');
        };

        /**
         * Function gets triggered when an upload is aborted by the user
         * @method _onXHRAbort
         * @private
         * @param {Event}  e  The event's information
         */
        var _onXHRAbort = function(e) {
            var statusDiv = $(SELECTOR_SHELF_STATUS.replace('{id}', this.upload.uploadId));
            statusDiv.html(_options.text_aborted);
        };

        /**
         * Function gets triggered by the browser as long as the upload goes on - This is used to update the progress bar
         * @method _onXHRUploadProgress
         * @private
         * @param {Event}  e  The event's information
         */
        var _onXHRUploadProgress = function(e) {
            var percent = (e.loaded / e.total) * 100,
                progressDiv = $(SELECTOR_SHELF_BAR.replace('{id}', this.upload.uploadId)),
                statusDiv = $(SELECTOR_SHELF_STATUS.replace('{id}', this.upload.uploadId));
            progressDiv.css('width', percent + '%');
            statusDiv.html(_options.text_uploading);
        };

        /**
         * Function gets triggered when the user drops something to the 'dropbox' div
         * @method _onDrop
         * @private
         * @param  {Event}  e  The event's information
         */
        var _onDrop = function(e) {
            var dt = e.originalEvent.dataTransfer,
                files = dt.files;

            e.preventDefault();
            $dropbox.css('backgroundColor', '');

            // go through all the files and start to upload them
            for (var i = 0; i < files.length; i++) {
                var file = files[i];
                _uploadFile(file);
            }
        };

        /**
         * Function gets triggered the user drags a file over the 'dropbox' div.
         * This is needed to tell the OS that we are going to accept the drop
         * @method _onDrag
         * @private
         * @param  {Event}  e  The event's information
         */
        var _onDrag = function(e) {
            e.preventDefault();
        };

        /**
         * Uploads the file
         * @method _uploadFile
         * @private
         * @param {Object} file
         */
        var _uploadFile = function(file) {
            if (file.size > _options.maxFileSize && _options.maxFileSize !== 0) {
                alert(file.name + ': ' + $('<div />').html(_options.text_fileIsTooBig).text());
                return;
            }

            $(_options.selectorShelf).css("display", "block");

            var xhr = new XMLHttpRequest();

            _uploads.push(xhr);
            xhr.upload.uploadId = _uploads.length - 1;

            // assign the events
            xhr.onload = function(e) {
                _onXHRLoad.call(xhr, e);
            };
            xhr.onerror = function(e) {
                _onXHRError.call(xhr, e);
            };
            xhr.onabort = function(e) {
                _onXHRAbort.call(xhr, e);
            };
            xhr.upload.onprogress = function(e) {
                _onXHRUploadProgress.call(xhr, e);
            };

            // add the HTML for the progress bar, the labels and the button
            var html = Con.parseTemplate(TPL_SHELF, {
                uploadid: xhr.upload.uploadId,
                filename: file.name,
                textcancel: _options.text_cancelButton,
                textwaiting: _options.text_waiting
            });

            $shelf.append(html);

            // connect to the server
            xhr.open('POST', _options.urlUpload, true);

            // create the formdata and fill it
            var pData = new FormData();

            pData.append('file[]', file);
            pData.append('frame', '4');
            pData.append('area', 'upl');
            pData.append('contenido', _options.sid);
            pData.append('action', 'upl_upload');
            pData.append('path', _options.uploadPath);

            _formCache.push(pData);

            // send the request
            if (_runningUpload === null) {
                xhr.send(pData);
                _runningUpload = xhr;
            }
        };

        // #####################################################################
        // Initialize module and bind ui

        if (!_supportAjaxUploadProgressEvents()) {
            $dropbox.html(
                "<applet code=org.contenido.DropboxMain archive='./jar/dnd.jar' width='500' height='100'>" +
                "    <param name='upload_path' value='" + _options.uploadPath + "'>" +
                "    <param name='uid' value='" + _options.sid + "'>" +
                "    <param name='host' value='" + _options.urlHost + "'>" +
                "</applet>"
            );
            $dropbox.css('height', '105px');
            return;
        }

        $shelf.delegate('input.shelf_elem_cancelbutton', 'click', function(e) {
            var id = parseInt($(this).parent('.shelf_elem').data('uploadid'), 10);
            _abortUpload(id);
        });

        $dropbox.on('drop', function(e) {
            _onDrop(e);
        })
        .on('dragenter', function(e) {
            _onDrag(e);
        })
        .on('dragover', function(e) {
            $dropbox.css('backgroundColor', '#a9d0f5');
            _onDrag(e);
        })
        .on('dragleave', function(e) {
            $dropbox.css('backgroundColor', '');
        });

        // #####################################################################
        // Public interface

        return {
            // theree is nothing public
        };

    };

})(Con, Con.$);

