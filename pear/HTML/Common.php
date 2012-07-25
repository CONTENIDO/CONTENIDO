<?php
/**
 * @deprecated 2012-03-04 This class was ported directly into CONTENIDO.
 *
 * You can find its functionality in the class cHTML
 * in contenido/classes/class.htmlelements.php
 *
 * Note:
 * This class and this file will be removed
 * in a further version of CONTENIDO. Please change
 * your scripts accordingly to the new class name.
 * If your class extends cHTML which originally extended
 * HTML_Common, you must not change anything.
 */
class HTML_Common extends cHTML {
    /** @deprecated 2012-03-04 This class was ported directly into CONTENIDO. */
    public function HTML_Common($x, $y) {
        cDeprecated("PEAR HTML_Common: This class was ported to CONTENIDO class cHTML");
        parent::__construct();
    }

    /** @deprecated 2012-03-04 This class was ported directly into CONTENIDO. */
    public function __construct($x, $y) {
        cDeprecated("PEAR HTML_Common: This class was ported to CONTENIDO class cHTML");
        parent::__construct();
    }

    /** @deprecated 2012-03-04 This function is not longer supported. */
    protected function _updateAttrArray(&$x, $y) {
        cDeprecated("Portage from PEAR HTML_Common: Use updateAttributes instead");
        $this->updateAttributes($y);
    }

    /** @deprecated 2012-03-04 This function is not longer supported. */
    protected function _removeAttr($x, &$y) {
        cDeprecated("Portage from PEAR HTML_Common: Use removeAttribute instead");
        $this->removeAttribute($x);
    }

    /** @deprecated 2012-03-04 This function is not longer supported. */
    protected function _getAttrKey($x, $y) {
        cDeprecated("Portage from PEAR HTML_Common: This function is not longer used.");
        return '';
    }

    /** @deprecated 2012-03-04 This function is not longer supported. */
    public function setComment($x) {
        cDeprecated("Portage from PEAR HTML_Common: This function is not longer used.");
    }

    /** @deprecated 2012-03-04 This function is not longer supported. */
    public function getComment() {
        cDeprecated("Portage from PEAR HTML_Common: This function is not longer used.");
        return '';
    }

    /** @deprecated 2012-03-04 This function is not longer supported. */
    public function setLineEnd($x) {
        cDeprecated("Portage from PEAR HTML_Common: This function is not longer used.");
    }

    /** @deprecated 2012-03-04 This function is not longer supported. */
    protected function _getLineEnd() {
        cDeprecated("Portage from PEAR HTML_Common: This function is not longer used.");
        return '';
    }

    /** @deprecated 2012-03-04 This function is not longer supported. */
    protected function _getTab() {
        cDeprecated("Portage from PEAR HTML_Common: This function is not longer used.");
        return '';
    }

    /** @deprecated 2012-03-04 This function is not longer supported. */
    protected function _getTabs() {
        cDeprecated("Portage from PEAR HTML_Common: This function is not longer used.");
        return '';
    }

    /** @deprecated 2012-03-04 This function is not longer supported. */
    public function getTabOffset() {
        cDeprecated("Portage from PEAR HTML_Common: This function is not longer used.");
        return '';
    }

    /** @deprecated 2012-03-04 This function is not longer supported. */
    public function setTabOffset($x) {
        cDeprecated("Portage from PEAR HTML_Common: This function is not longer used.");
    }

    /** @deprecated 2012-03-04 This function is not longer supported. */
    public function setTab($x) {
        cDeprecated("Portage from PEAR HTML_Common: This function is not longer used.");
    }
}
?>
