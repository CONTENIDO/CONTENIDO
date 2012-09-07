<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Debug object to show info on screen in a box / HTML Block at the top of page.
 * Instead of doing the output immediately using method show, values can be collected and printed to screen in one go.
 * Therefore there's a box positioned at the left top of the page that can be toggled and hidden.
 * 
 * Please note: 
 * When using method Debug_VisibleAdv::showAll() you'll produce invalid HTML when having an XHTML doctype.
 *  
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version 1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2008-05-20
 *
 *   $Id: Debug_VisibleAdv.class.php 830 2008-09-18 10:51:34Z OliverL $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

include_once('IDebug.php');

class Debug_VisibleAdv implements IDebug, Countable {
	
	static private $_instance;
	private $_aItems;
	
	/** 
	* Constructor
	* @access private
	*/
	private function __construct() {
		$this->_aItems = array();
	}
	
	/** 
	* static
	* @access public
	*/
	static public function getInstance() {
		if (self::$_instance == null) {
			self::$_instance = new Debug_VisibleAdv();
		}
		return self::$_instance;
	}
	
	/**
	 * Add a Debug item to internal collection.
	 * @access public
	 * @param mixed $mVariable
	 * @param string $sVariableDescription
	 * @return void;
	 */
	public function add($mVariable, $sVariableDescription = '') {
		$oItem = new Debug_VisibleAdv_Item();
		$oItem->setValue($mVariable);
		$oItem->setDescription($sVariableDescription);
		$this->_aItems[] = $oItem;
	}
	
	/**
	 * Reset internal collection with Debug items.
	 * @access public
	 * @return void
	 */
	public function reset() {
        $this->_aItems = array();
	}
	
	/**
	 * Outputs all Debug items in collection to screen in a HTML Box at left top of page.
	 * @access public
	 * @return void
	 */
	public function showAll() {
        if ($this->count() > 0) {
            $sHtml = '<script type="text/javascript">
			function con_dbg_toggle(myItem) {
				var myItemObj = document.getElementById(myItem);
				if (myItemObj) {
					if (myItemObj.style.display == \'\') {
						myItemObj.style.display = \'none\';
					} else {
						myItemObj.style.display = \'\';
					}
				}
			}
			function con_dbg_hide(myItem) {
				var myItemObj = document.getElementById(myItem);
				if (myItemObj) {
					myItemObj.style.display = \'none\';
				}
			}
		</script>
		
		<style type="text/css">
			#conDbgBox { position:absolute; z-index:100000; left:5px; top:5px; margin:0; border:1px solid #ccc;padding:5px; background-color:#f6f6f6; text-align: left;color: #000000; }
			#conDbgBox a { text-decoration:none; color: #000000; margin-top: 6px; }
			#dbg_item_block { margin: 0px 0px 0px 20px; color: #000000; }
			#dbg_item_block textarea, #dbg_item_block pre { font-size:11px; margin: 0px; padding: 5px; color: #000000; border: 1px solid #6c6c6c; background-color: #ffffff; }
			#dbg_item_block a { display:block; color: #000000; }
			#conDbgClose { padding-left:10px; }
		</style>';
            $sHtml .= '<div id="conDbgBox">
			<a href="javascript:void(0);" title="Toggle Debug Output" onclick="con_dbg_toggle(\'dbg_item_block\');">con dbg</a> 
			<a id="conDbgClose" href="javascript:void(0);" title="Hide Debug Output" onclick="con_dbg_hide(\'conDbgBox\');">(x)</a>
			<div id="dbg_item_block" style="display:none;">';
            $sBlocks = '';
            $i = 1;
            foreach ($this->_aItems as $oItem) {
                $sItemName = strlen($oItem->getDescription()) > 0 ? $oItem->getDescription() : ('debug item #'.$i);
                $sItemValue = $this->_prepareValue($oItem->getValue());
                $sHtml .=  "\n".'<a href="javascript:void(0);" title="Toggle Item" onclick="con_dbg_toggle(\'dbg_item_'.$i.'\');">'.$sItemName.'</a>
				<div id="dbg_item_'.$i.'" style="display:none;margin-left:20px;">'.$sItemValue.'</div>'."\n";
                ++$i;
            }
            $sHtml .= '</div>
		</div>';
            echo $sHtml;
        }
	}
	
	/**
	 * Prepares Debug item value for output as string representation.
	 * @access private
	 * @param mixed $mValue
	 * @return string
	 */
	private function _prepareValue($mValue) {
		$bTextarea = false;
		$bPlainText = false;
		$sReturn = '';
		if (is_array($mValue)) {
			if (sizeof($mValue) > 10) {
				$bTextarea = true;
			} else {
				$bPlainText = true;
			}
		}
		if (is_object($mValue)) {
			$bTextarea = true;
		}
		if (is_string($mValue)) {
			if (preg_match('/<(.*)>/', $mValue)) {
				if (strlen($mValue) > 40) {
					$bTextarea = true;
				} else {
					$bPlainText = true;
					$mValue = htmlspecialchars($mValue);
				}
			} else {
				$bPlainText = true;
			}
		}
		
		if ($bTextarea === true) {
			$sReturn .= '<textarea rows="14" cols="100">';
		} elseif ($bPlainText === true) {
			$sReturn .= '<pre>';
		} else {
			$sReturn .= '<pre>';
		}
		
	    if (is_array($mValue)) {
			$sReturn .= print_r($mValue, true);
		} else {
		    ob_start();
			var_dump($mValue);
		    $sReturn .= ob_get_contents();
		    ob_end_clean();
		}
		
		if ($bTextarea === true) {
			$sReturn .= '</textarea>';
		} elseif ($bPlainText === true) {
			$sReturn .= '</pre>';
		} else {
			$sReturn .= '</pre>';
		}
        return $sReturn;
	}
	
	/**
	 * Implemenation of Countable interface
	 * @access public
	 * @return int
	 */
	public function count() {
        return sizeof($this->_aItems);
	}
	
	/**
	 * Outputs contents of passed variable in a preformatted, readable way
	 *
	 * @access public
	 * @param mixed $mVariable The variable to be displayed
	 * @param string $sVariableDescription The variable's name or description
	 * @param boolean $bExit If set to true, your app will die() after output of current var
	 * @return void
	 */
	public function show($mVariable, $sVariableDescription='', $bExit = false)
	{
		try {
            $oDbgVisible = DebuggerFactory::getDebugger('visible');
		} catch (Exception $e) {
			//throw $e;
			echo $e->getMessage();
		}
		$oDbgVisible->show($mVariable, $sVariableDescription, $bExit);
	}
}

/**
 * An object representing one Debug item of a Debug_VisibleBlock.
 */
class Debug_VisibleAdv_Item {
	private $_mValue;
	private $_sDescription;
	
	/**
	 * Set value of item
	 * @access public
	 * @return void
	 */
	public function setValue($mValue) {
        $this->_mValue = $mValue;
	}
	
	/**
	 * Set name/description of item
	 * @access public
	 * @return void
	 */
	public function setDescription($sDescription) {
        $this->_sDescription = $sDescription;
	}
	
	/**
	 * Get value of item
	 * @access public
	 * @return mixed
	 */
	public function getValue() {
        return $this->_mValue;
	}
	
	/**
	 * Get name/description of item
	 * @access public
	 * @return string
	 */
	public function getDescription() {
        return $this->_sDescription;
	}
}
?>