<?php
/**
 * $RCSfile: include.right_top.php,v $
 *
 * Project:      tradefairs_3
 * Name:         include.right_top
 * Description:  blank (nothing to do here)
 *
 * Requirements: PHP
 *
 * @package
 * @version         1.0.0
 *
 * @author       Mario Diaz (4fb)
 * @copyright    four for business AG <www.4fb.de>
 * @license      http://www.contenido.org/license/LIZENZ.txt
 * @link         http://www.4fb.de
 * @see
 * @since
 * @deprecated
 *
 *
 * {@internal
 *   Created:      18.06.2007
 *   $Id: include.right_top.php,v 1.0.0 18.06.2007 16:08:02 mario.diaz$
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
if (isset($_REQUEST['cfg'])) {
    die('Invalid call!');
}


include(cRegistry::getBackendPath() . $cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);
?>