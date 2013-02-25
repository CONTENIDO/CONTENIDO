<?php

/**
 * description: contact (PIFA) form
 *
 * @package Module
 * @subpackage form_contact
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (cRegistry::isBackendEditMode()) {
    echo '<label class="content-type-label">' . mi18n("LABEL_FORM_CONTACT") . '</label>';
}

echo "CMS_PIFAFORM[1]";

?>