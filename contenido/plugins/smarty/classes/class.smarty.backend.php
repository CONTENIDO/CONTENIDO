<?php

/**
 * This file contains the backend class for smarty wrapper plugin.
 *
 * @package    Plugin
 * @subpackage SmartyWrapper
 * @author Andreas Dieter
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Wrapper class for Integration of smarty.
 *
 * @package    Plugin
 * @subpackage SmartyWrapper
 */
class cSmartyBackend extends cSmartyFrontend {
    /**
     * cSmartyBackend constructor.
     *
     * @param      $aCfg
     * @param      $aClientCfg
     * @param bool $bSanityCheck
     *
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function __construct(&$aCfg, &$aClientCfg, $bSanityCheck = false) {
        parent::__construct($aCfg, $aClientCfg, false);

        parent::$aDefaultPaths = [
            'template_dir' => $aCfg['path']['contenido'] . 'plugins/smarty_templates/',
            'cache_dir' => $aCfg['path']['contenido_cache'],
            'compile_dir' => $aCfg['path']['contenido_cache'] . 'templates_c/'
        ];

        parent::$bSmartyInstanciated = true;

        $this->resetPaths();
    }

}