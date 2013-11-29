/**
 * CONTENIDO JavaScript system_log_sysvalues.js module
 *
 * @version    SVN Revision $Rev: 5937 $
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */


(function(Con, $) {

    $(function() {
        // load new log contents each time another log is chosen
        $('select[name="logfile"]').change(function() {
            var numberOfLines = $('input[name="number-of-lines"]').val();
            $.ajax({
                type : 'POST',
                url : 'ajaxmain.php',
                data : 'ajax=logfilecontent&logfile=' + $(this).val() + '&numberOfLines=' + numberOfLines,
                success : function(msg) {
                    $('textarea[name="logfile-content"]').val(msg);
                }
            });
        });
    });

    /**
     * Deletes the selected log file.
     *
     * @returns {Boolean}
     */
    function deleteLogFile() {
        var logfile = $('select[name="logfile"]').val();
        var url = 'main.php?area=system_log&frame=4&action=deletelog&logfile='
                + logfile;
        document.location.href = url;
        return false;
    }

    /**
     * Clears the selected log file and keeps the last X lines.
     *
     * @returns {Boolean}
     */
    function clearLogFile() {
        var logfile = $('select[name="logfile"]').val();
        var keepLines = $('input[name="keep-last-lines"]').val();
        var url = 'main.php?area=system_log&frame=4&action=clearlog&logfile='
                + logfile + '&keepLines=' + keepLines;
        document.location.href = url;
        return false;
    }

    /**
     * Shows the specified number of lines of the specified log.
     *
     * @returns {Boolean}
     */
    function showLog() {
        var logfile = $('select[name="logfile"]').val();
        var numberOfLines = $('input[name="number-of-lines"]').val();
        var url = 'main.php?area=system_log&frame=4&action=showLog&logfile='
                + logfile + '&numberOfLines=' + numberOfLines;
        document.location.href = url;
        return false;
    }

    window.deleteLogFile = deleteLogFile;
    window.clearLogFile = clearLogFile;
    window.showLog = showLog;

})(Con, Con.$);
