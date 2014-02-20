<?php
/**
 * This file contains the backend class for smarty wrapper plugin.
 *
 * @package Plugin
 * @subpackage SmartyWrapper
 * @version SVN Revision $Rev:$
 *
 * @author Andreas Dieter
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Wrapper class for Integration of smarty.
 *
 * @package Plugin
 * @subpackage SmartyWrapper
 */
class cSmartyBackend extends cSmartyFrontend {

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

/**
 * Wrapper class for Integration of smarty.
 *
 * @deprecated 2013-11-12 - Use the class cSmartyBackend instead.
 * @package Plugin
 * @subpackage SmartyWrapper
 */
class Contenido_Backend_SmartyWrapper extends cSmartyBackend {

	public function __construct(&$aCfg, &$aClientCfg, $bSanityCheck = false) {
		cDeprecated("Use the class cSmartyBackend instead.");
		parent::__construct($aCfg, $aClientCfg, $bSanityCheck);
	}

}