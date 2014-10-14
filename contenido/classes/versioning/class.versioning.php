<?php
/**
 * 
 *
 * @package          
 * @subpackage       
 * @version          
 *
 * @author           Jann Dieckmann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Versioning
 *
 * @package 
 * @subpackage 
 */
class cVersioning {

	/**
	 */
	public static function getEnabled() {
	
		static $versioningEnabled;
		
		if (!isset($versioningEnabled)) {
	
			// versioning enabled is a tri-state => false (default), simple, advanced
			$systemPropColl = new cApiSystemPropertyCollection();
			$prop = $systemPropColl->fetchByTypeName('versioning', 'enabled');
			$versioningEnabled = $prop ? $prop->get('value') : false;
			
			if (false === $versioningEnabled || NULL === $versioningEnabled) {
				$versioningEnabled = 'false';
			} else if ('' === $versioningEnabled) {
				// NOTE: An non empty default value overrides an empty value
				$versioningEnabled = 'false';
			}

		}
		
		return $versioningEnabled;
	
	}

}