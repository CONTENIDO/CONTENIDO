/**
 * CONTENIDO calendar.mycontenido.tasks.edit JavaScript module.
 *
 * @version    SVN Revision $Rev$
 * @requires   jQuery, Con
 * @author     ???
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

(function(Con, $) {
    $(function() {
        $('#reminderdate').datetimepicker({
            buttonImage: 'images/calendar.gif',
            buttonImageOnly: true,
            showOn: 'both',
            dateFormat: 'yy-mm-dd',
            /*separator: ' ',
            timeFormat: 'hh:mm',*/
            onClose: function(dateText, inst) {
                var endDateTextBox = $('#enddate');
                if (endDateTextBox.val() != '') {
                    var testStartDate = new Date(dateText);
                    var testEndDate   = new Date(endDateTextBox.val());
                    if (testStartDate > testEndDate) {
                        endDateTextBox.val(dateText);
                    }
                } else {
                    endDateTextBox.val(dateText);
                }
            },
            onSelect: function (selectedDateTime) {
                var start = $(this).datetimepicker('getDate');
                $('#enddate').datetimepicker('option', 'minDate', new Date(start.getTime()));
            }
        });

        $('#enddate').datetimepicker({
            buttonImage: 'images/calendar.gif',
            buttonImageOnly: true,
            showOn: 'both',
            dateFormat: 'yy-mm-dd',
            /*separator: ' ',
            timeFormat: 'hh:mm',*/
            onClose: function(dateText, inst) {
                var startDateTextBox = $('#reminderdate');
                if (startDateTextBox.val() != '') {
                    var testStartDate = new Date(startDateTextBox.val());
                    var testEndDate   = new Date(dateText);
                    if (testStartDate > testEndDate) {
                        startDateTextBox.val(dateText);
                    }
                } else {
                    startDateTextBox.val(dateText);
                }
            },
            onSelect: function (selectedDateTime) {
                var end = $(this).datetimepicker('getDate');
                $('#reminderdate').datetimepicker('option', 'maxDate', new Date(end.getTime()));
            }
        });
    });
})(Con, Con.$);
