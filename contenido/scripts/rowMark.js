/**
 * CONTENIDO JavaScript rowMark module
 *
 * @author     Jan Lengowski <Jan.Lengowski@4fb.de>
 * @author     Timo Trautmann <timo.trautmann@4fb.de>
 * @copyright  Jan Lengowski 2002
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @todo       Rework this, make a CONTENIDO module
 */

/**
 * Table rowMark
 *
 * myRow = new rowMark(1,2,3,4)
 *
 *   1:    Farbe des Over Effekts z.B. "#ff0000" - string
 *   2:    Farbe des Mark Effeks - string
 *   3:    Farbe des Over Effeks bei der Marked Row - string
 *   4:    Funktion die bei onClick aufgerufen wird - string
 *
 *   <tr class="grau" onMouseOver="myRow.over(this)" onMouseOut="myRow.out(this)" onClick="myRow.click(this)">
 *       <td>eine Zeile</td>
 *       <td><img src="einbild.gif"></td>
 *   </tr>
 *
 * Alternative Lösung ohne inline JavaScript event handler.
 *   <table id="myTable">
 *   <tr class="grau row_mark">
 *       <td>eine Zeile</td>
 *       <td><img src="einbild.gif"></td>
 *   </tr>
 *   </table>
 *   <script>
 *   (function(Con, $) {
 *      $(function() {
 *         Con.RowMark.initialize('#myTable .row_mark', 'myRow');
 *      });
 *   })(Con, Con.$);
 *   </script>
 *
 * @param {String} overColor - Over-Color
 * @param {String} markedColor - Marked-Color
 * @param {String} overMarked - Over-Marked-Color
 * @param {String} onClick - Function to evaluate on onclick event
 * @param {String} instanceName - Instance name in global scope
 */
function rowMark(overColor, markedColor, overMarked, onClick, instanceName) {

    /**
     * Set class properties
     * @access private
     */
    this.instanceName = instanceName;
    this.overColor = overColor;
    this.markedColor = markedColor;
    this.overMarked = overMarked;
    this.onClick = onClick;
    this.highlightFrame = "#CCCCCC";
    this.backgroundColor = "#E2E2E2";
    this.markedSyncColor = "#AED2F1";
    this.syncColor = "#ddecf9";

    /**
     * dynamic properties
     * @access private
     */
    this.oldColor = '';
    this.oldColorMarked = '';
    this.markedRow = '';

    /**
     * Define class methods
     * @access private
     */
    this.over = rowMark_over;
    this.out = rowMark_out;
    this.click = rowMark_click;
    this.reset = rowMark_reset;

    /**
     * Browser check
     * @access private
     */
    this.browser = '';
}

/**
 * rowMark::over()
 * @param {HTMLTableRowElement} oRow table row object
 */
function rowMark_over(oRow) {
    if (oRow == null) {
        return;
    }

    if (oRow.style.backgroundColor != this.markedColor) {
        this.oldColor = oRow.style.backgroundColor;
    }

    oRow.style.backgroundColor = this.overColor;

    //if (oRow.style.backgroundColor == this.markedColor) {
    //    oRow.style.backgroundColor = this.overMarked;
    //} else {
    //    oRow.style.backgroundColor = this.overColor;
    //}
}

/**
 * rowMark::out()
 * @param {HTMLTableRowElement} oRow table row object
 */
function rowMark_out(oRow) {
    if (oRow == this.markedRow) {
        if (oRow.className === "con_sync") {
            oRow.style.backgroundColor = this.markedSyncColor;
        } else {
            oRow.style.backgroundColor = this.markedColor;
        }
    } else {
        oRow.style.backgroundColor = this.oldColor;
    }

}

function rowMark_reset() {
    var oObjects = document.getElementsByTagName('tr');
    var pattern=eval("/" + this.instanceName + "\\.click\\(this\\)/m");

    for (var i = 0; i < oObjects.length; i++) {
        var sOnclick = String(oObjects[i].onclick);
        if (sOnclick != '' && sOnclick !== 'undefined') {
            if (sOnclick.match(pattern)) {
                if (oObjects[i].className === 'con_sync') {
                    oObjects[i].style.backgroundColor = this.syncColor;
                } else {
                    oObjects[i].style.backgroundColor = '#FFFFFF';
                }
            }
        }
    }
    this.markedRow = '';
}

/**
 * rowMark::over()
 * @param {HTMLTableRowElement} oRow table row object
 */
function rowMark_click(oRow) {
    if (oRow == null) {
        return;
    }
    if (typeof this.markedRow !== 'object') {
        if (oRow.className === 'con_sync') {
            oRow.style.backgroundColor = this.markedSyncColor;
        } else {
            oRow.style.backgroundColor = this.markedColor;
        }

        this.markedRow = oRow;
        this.oldColorMarked = this.oldColor;
        if (this.onClick != '') {
            eval(this.onClick);
        }
   } else if (this.markedRow != oRow) {
        // reset old
        this.markedRow.style.backgroundColor = this.oldColorMarked;
        // highlight new
        if (oRow.className === 'con_sync') {
            oRow.style.backgroundColor = this.markedSyncColor;
        } else {
            oRow.style.backgroundColor = this.markedColor;
        }

        this.markedRow = oRow;
        this.oldColorMarked = this.oldColor;

        if (this.onClick != '') {
            eval(this.onClick);
        }
    }
}

/**
 * Table rowMark with image rollover
 *
 * REQUIRES rowMark CLASS!
 *
 * myRow = new imgMark(1, 2, 3, 4, 5, 6);
 *
 *  1:  Farbe des Over Effekts z.B. "#ff0000" - string
 *  2:  Farbe des Mark Effeks - string
 *  3:  Farbe des Over Effeks bei der Marked Row - string
 *  4:  Pfad des Bildes das bei .over() gewechselt wird - string
 *  5:  Pfad des Bildes das bei .out() gewechselt wird - string
 *  6:  Function die bei onClick aufgerufen wird - string
 *
 *   <tr class="grau" onMouseOver="myRow.over(this, 0)" onMouseOut="myRow.out(this, 0)" onClick="myRow.click(this)">
 *       <td>eine Zeile</td>
 *       <td><img src="einbild.gif"></td>
 *   </tr>
 *
 * @param {String} overColor - Over-Color
 * @param {String} markedColor - Marked-Color
 * @param {String} overMarked - Over-Marked-Color
 * @param {String} imgOutSrc - Out-Image-Src
 * @param {String} imgOverSrc - Over-Image-Src
 * @param {String} onClick - Function to evaluate on onclick event
 */
function imgMark(overColor, markedColor, overMarked, imgOutSrc, imgOverSrc, onClick) {

    /**
     * Call parent class constructor
     * @access private
     */
    this.base = rowMark;
    this.base(overColor, markedColor, overMarked, onClick, 'imgMark');

    /**
     * Set image path properties
     * @access private
     */
    this.imgOutSrc = imgOutSrc;
    this.imgOverSrc = imgOverSrc;

    /**
     * Modify inherited .over() method
     * @access private
     */
    var str = this.over + '';
    var astr = str.split('\n');
    var fstr = 'var img = oRow.getElementsByTagName("IMG"); img[imgId].src = this.imgOverSrc;';
    for (i = 2; i < astr.length - 2; i++) {
        fstr += astr[i];
    }
    this.over = new Function ('oRow', 'imgId', fstr);

    /**
     * Modify inherited .out() method
     * @access private
     */
    var str = this.out + '';
    var astr = str.split('\n');
    var fstr = 'var img = oRow.getElementsByTagName("IMG");img[imgId].src = this.imgOutSrc;';

    for (i = 2; i < astr.length - 2; i++) {
        fstr += astr[i];
    }
    this.out = new Function ('oRow', 'imgId', fstr);

}
imgMark.prototype = new rowMark;

/**
 * Function for showing and hiding synchronisation options
 *
 * @param boolean permSyncCat true shows options / false hides options
 */
function rowMarkRefreshSyncScreen(permSyncCat) {
    //curLanguageSync = syncFrom;
    var frame = Con.getFrame('left_top'),
        $syncElem = Con.$('#sync_cat_single', frame.document),
        $syncElemMultiple = Con.$('#sync_cat_multiple', frame.document);
    if ($syncElem[0] && $syncElemMultiple[0]) {
        if (permSyncCat == 0) {
            $syncElem.css('display', 'none');
            $syncElemMultiple.css('display', 'none');
        } else {
            $syncElem.css('display', 'block');
            $syncElemMultiple.css('display', 'block');
        }
        Con.FrameLeftTop.resize({resizegap: 1});
    }
}

/**
 * Interface function for transferring data from left-bottom frame to the
 * configuration object in the left-top frame.
 *
 * @param {HTMLTableRowElement} obj HTML Table Row Object
 */
function rowMarkConClick(obj) {
    var frame = Con.getFrame('left_top'),
        cfgObj, data, str;

    // Configuration object reference
    cfgObj = frame.cfg;

    /* Split the data string.
       0 -> category id
       1 -> category template id
       2 -> category online
       3 -> category public
       4 -> has right for: template
       5 -> has right for: online
       6 -> has right for: public
       7 -> has right for: template_edit
       8 -> cat is syncable
       9 -> idstring not splitted */
    data = obj.id.split('-');

    if (data.length == 9) {
        // Transfer data to the cfg object through the .load() method
        //cfgObj.load(data[0], data[1], data[2], data[3], data[4], data[5], data[6], data[7]);
        cfgObj.load(data[0], data[1], data[2], data[3], data[4], data[5], data[6], data[7], data[8], obj.id);
        rowMarkRefreshSyncScreen(data[8]);
    } else {
        cfgObj.reset();
        rowMarkRefreshSyncScreen(0);
    }

    // String for debugging
    str  = "";
    str += "Category ID is: "     + data[0] + "\n";
    str += "Template ID is: "     + data[1] + "\n";
    str += "Online status is: "   + data[2] + "\n";
    str += "Public status is: "   + data[3] + "\n";
    str += "Right for Template: " + data[4] + "\n";
    str += "Right for Online: "   + data[5] + "\n";
    str += "Right for Public: "   + data[6] + "\n";
    str += "Right for Template Config: "   + data[7] + "\n";
    str += "data7: "   + data[7] + "\n";

    if (Con.isNs) {
        if (!frame.cfg.scrollX) {
            frame.cfg.scrollX = 0;
        }
        if (!frame.cfg.scrollY) {
            frame.cfg.scrollY = 0;
        }

        frame.cfg.scrollX = window.scrollX;
        frame.cfg.scrollY = window.scrollY;
    }
}

/**
 * @param {HTMLTableRowElement} oRow
 */
function rowMarkLayClick(oRow) {
    Con.getFrame('left_top').obj = oRow.id;
}

/**
 * Sets the path value in the area 'upl'
 * @param {HTMLTableRowElement} oRow
 */
function rowMarkUplClick(oRow) {
    var newPath = $(oRow).data('id'),
        maxAttempts = 5,
        attempts = 0;

    function process() {
        var frame = Con.getFrame('left_top');
        if (frame && $('#caption2', frame.document).length && $('input[name=path]', frame.document).length) {
            // Update caption
            $('#caption2', frame.document).html(newPath);
            // Update 'path' field
            $('input[name=path]', frame.document).val(newPath);
        } else {
            // Try it several times, the dram content may be available later...
            if (++attempts < maxAttempts) {
                window.setTimeout(function() {
                    process();
                }, 100);
            }
        }
    }

    process();
}

/**
 * Generic function to reMark a row in layout
 * @param {String|HTMLElement} sObjId
 */
function rowMarkLayReMark(sObjId) {
    var elm = document.getElementById(sObjId);

    if (typeof elm == 'object') {
        lay.over(elm);
        lay.click(elm);

        if (elm && elm != null) {
            elm.scrollIntoView(false);
        }
    }
}

/**
 * Function gets currently selected category row an set it as default in select
 * box for base category (select box in category new layer) Function is also
 * called by row instance 'str', when selected row changes
 */
function rowMarkStrClick(elemId) {
    var select = document.getElementById(elemId),
        iCatId = 0;

    if (str.markedRow) {
        if (str.markedRow.id.match(/^cat_(\d+)_row$/g)) {
            iCatId = parseInt(RegExp.$1);
        }
    }

    if (select && iCatId > 0) {
        var aOptions = select.getElementsByTagName('option');
        for (var i = 0; i < aOptions.length; i++) {
            aOptions[i].selected = false;
            if (!aOptions[i].disabled) {
                var iValueOption = parseInt(aOptions[i].value);
                if (iValueOption > 0 && iValueOption == iCatId) {
                    aOptions[i].selected = true;
                }
            }
        }
    }
}

function rowMarkArtRowClick(oRow) {
    if (conArtOverviewExtractData(oRow) == false) {
            window.setTimeout(function() {
                conArtOverviewExtractData(oRow);
            }, 250);
    }
}

/**
 * rowMark instances
 */

// rowMark instance for the general use
row = new rowMark('#f9fbdd', '#ecf1b2', '#cccccc', 'void(0)', 'row');

// rowMark instance for the Sub Navigation
sub = new rowMark('red', '#FFF', 'blue', 'void(0)', 'sub');

// rowMark instance for the Content area
con = new rowMark('#f9fbdd', '#ecf1b2', '#a9aec2', 'rowMarkConClick(oRow)', 'con');

// rowMark instance for the Content Category area
str = new rowMark('#f9fbdd', '#ecf1b2', '#a9aec2', 'rowMarkStrClick("new_idcat")', 'str');

// rowMark instance for the Upload area
upl = new rowMark('#f9fbdd', '#ecf1b2', '#a9aec2', 'rowMarkUplClick(oRow)', 'upl');

// Create a new rowMark Instance for the Content-Article overview area
artRow = new rowMark('#f9fbdd', '#ecf1b2', '#ecf1b2', 'rowMarkArtRowClick(oRow)', 'artRow');

// rowMark instance for area 'lay'
lay = new rowMark('#f9fbdd', '#ecf1b2', '#a9aec2', 'rowMarkLayClick(oRow)', 'lay');


(function(Con, $) {

    var NAME = 'rowmark';

    /**
     * RowMark class.
     *
     * @since CONTENIDO 4.10.2
     * @class  RowMark
     * @static
     */
    var RowMark = {

        /**
         * Registers desired row mark type (row, sub, con, str, etc) instance to to mouse event handler
         * of a list of elements.
         * Marks found element, if parameter markedSelector is passed.
         *
         * @param {String|jQuery} selectorOrJquery - Selector to retrieve the elements or the jQuery instance
         * @param {String} rowMarkType - The rom mark type
         * @param {String} [markedSelector] - The selector of the element to mark (highlight)
         */
        initialize: function(selectorOrJquery, rowMarkType, markedSelector) {
            var rowMarkInstance = window[rowMarkType],
                $elements = typeof selectorOrJquery === 'string' ? $(selectorOrJquery) : selectorOrJquery;

            // Register event handler
            $elements.mouseover(function () {
                rowMarkInstance.over(this);
            }).mouseout(function () {
                rowMarkInstance.out(this);
            }).click(function (e) {
                // Don't mark the row when the click was on one of the actions!
                if ($(e.target).closest('[data-type="actions"]').length === 0) {
                    rowMarkInstance.click(this);
                }
            });

            // Mark element matching the marked selector
            if (markedSelector && $(markedSelector)) {
                rowMarkInstance.click($(markedSelector).get(0));
            }
        }

    };

    Con.RowMark = RowMark;

})(Con, Con.$);
