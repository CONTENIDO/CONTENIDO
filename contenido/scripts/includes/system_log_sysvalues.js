/**
 * CONTENIDO JavaScript system_log_sysvalues.js module
 *
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */


(function(Con, $) {

    /**
     * Class SystemLogSysValues
     * @param {Object} options - Options
     * @param {jQuery} optiond.root - The root element jQuery object
     * @param {String} optiond.fileIsTooLargeMsg - Message to display for too large files
     * @constructor
     */
    function SystemLogSysValues (options) {
        this.root = options.root;
        this.fileIsTooLargeMsg = options.fileIsTooLargeMsg;
        this.select = this.root.find('select[name="logfile"]');
        this.msgNode = this.root.find('.js-message');
        this.textarea = this.root.find('textarea[name="logfile-content"]');
        this.numberOfLines = this.root.find('input[name="number-of-lines"]');
        this.keepLastLines = this.root.find('input[name="keep-last-lines"]');
        this.showLogLink = this.root.find('.js-action-show-log');
        this.clearLogLink = this.root.find('.js-action-clear-log');
        this.deleteLogLink = this.root.find('.js-action-delete-log');

        registerEvents(this);
        syncSelectedFileState(this);
    }

    function registerEvents(context) {
        context.showLogLink.click(function (e) {
            e.preventDefault();
            showLog(context);
        });

        context.deleteLogLink.click(function (e) {
            e.preventDefault();
            deleteLogFile(context);
        });

        context.clearLogLink.click(function (e) {
            e.preventDefault();
            clearLogFile(context);
        });

        // load new log contents each time another log is chosen
        context.select.change(function() {
            syncSelectedFileState(context);
            loadLogFile(context);
        });
    }

    /**
     * Loads a log file.
     *
     * @param {SystemLogSysValues} context
     */
    function loadLogFile (context) {
        var numberOfLines;

        if (isSelectedFileReadable(context)) {
            numberOfLines = context.numberOfLines.val();
            $.ajax({
                type : 'POST',
                url : 'ajaxmain.php',
                data : 'ajax=logfilecontent&logfile=' + context.select.val() + '&numberOfLines=' + numberOfLines,
                success : function(msg) {
                    if (Con.checkAjaxResponse(msg) === false)  {
                        return false;
                    }

                    context.textarea.val(msg);
                }
            });
        } else {
            context.textarea.val('');
        }
    }

    /**
     * Deletes the selected log file.
     *
     * @param {SystemLogSysValues} context
     */
    function deleteLogFile(context) {
        var logfile = context.select.val();
        if (logfile !== '') {
            var url = 'main.php?area=system_log&frame=4&action=deletelog&logfile='
                + logfile;
            document.location.href = url;
        }
    }

    /**
     * Clears the selected log file and keeps the last X lines.
     *
     * @param {SystemLogSysValues} context
     */
    function clearLogFile(context) {
        if (isSelectedFileReadable(context)) {
            var keepLines = context.keepLastLines.val();
            var url = 'main.php?area=system_log&frame=4&action=clearlog&logfile='
                + context.select.val() + '&keepLines=' + keepLines;
            document.location.href = url;
        }
    }

    /**
     * Shows the specified number of lines of the specified log.
     *
     * @param {SystemLogSysValues} context
     */
    function showLog(context) {
        if (isSelectedFileReadable(context)) {
            var numberOfLines = context.numberOfLines.val();
            var url = 'main.php?area=system_log&frame=4&action=showLog&logfile='
                + context.select.val() + '&numberOfLines=' + numberOfLines;
            document.location.href = url;
        }
    }

    /**
     * Synchronizes the selected file state, displays a proper message and disables some form elements if the file is not readable
     *
     * @param {SystemLogSysValues} context
     */
    function syncSelectedFileState(context) {
        if (isSelectedFileReadable(context)) {
            context.msgNode.html('');
            context.numberOfLines.removeAttr('disabled');
            context.keepLastLines.removeAttr('disabled');
        } else {
            context.msgNode.html(context.fileIsTooLargeMsg);
            context.numberOfLines.prop('disabled', true);
            context.keepLastLines.prop('disabled', true);
        }
    }

    /**
     * Checks if selected file has the notreadable state.
     *
     * @param {SystemLogSysValues} context
     * @returns {Boolean}
     */
    function isSelectedFileReadable(context) {
        return context.select.find(':selected').data('notreadable') != '1';
    }

    Con.SystemLogSysValues = SystemLogSysValues;

})(Con, Con.$);
