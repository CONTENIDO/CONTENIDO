<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Wrapper class for Integration of smarty
 *
 * Requirements:
 *
 *
 * @package    Contenido Template classes
 * @version    1.3.0
 * @author     Andreas Dieter
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since
 *
 * {@internal
 *     created     2010-07-22
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class Contenido_Backend_SmartyWrapper extends cSmartyBackend {}

class cSmartyBackend extends Contenido_SmartyWrapper {

    public function __construct(&$aCfg, &$aClientCfg, $bSanityCheck = false) {
        parent::__construct($aCfg, $aClientCfg, false);

        parent::$aDefaultPaths = array(
            'template_dir' => $aCfg['path']['contenido'] . 'plugins/smarty_templates/',
            'cache_dir' => $aCfg['path']['contenido_cache'],
            'compile_dir' => $aCfg['path']['contenido_cache'] . 'templates_c/'
        );

        parent::$bSmartyInstanciated = true;

        $this->resetPaths();
    }
}