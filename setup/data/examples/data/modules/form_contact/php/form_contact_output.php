<?php

/**
 * description: contact (PIFA) form
 *
 * @package Module
 * @subpackage FormContact
 * @version SVN Revision $Rev:$
 *
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

mi18n("REPLY_HEADLINE");
mi18n("REPLY_TEXT");

if (cRegistry::isBackendEditMode()) {
    echo '<label class="content_type_label">' . conHtmlSpecialChars(mi18n("LABEL_FORM_CONTACT")) . '</label>';
}
echo "CMS_PIFAFORM[1]";

?>