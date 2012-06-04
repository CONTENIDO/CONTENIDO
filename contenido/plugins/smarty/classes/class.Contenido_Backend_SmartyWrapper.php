<?php
class Contenido_Backend_SmartyWrapper extends Contenido_SmartyWrapper {

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