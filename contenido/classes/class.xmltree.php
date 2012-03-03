<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * XmlTree and XmlNode Class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    4fb_XML
 * @version    1.0.1
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2010-07-03, Ortwin Pinke, removed '=&', causes deprecated runtime error with PHP >= 5.3
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
* XmlTree class
*
* Class to create XML tree structures from
* scratch without the need for a XML DOM
*
* Example:
 *
 * !! Attention, using '=&' is deprecated in PHP >= 5.3 and causes a deprecated runtime error
 * don't use it any more (Ortwin Pinke, 2010-07-03
*
* $tree  = new XmlTree('1.0', 'ISO-8859-1');
* $root =& $tree->addRoot('rootname', 'some content', array('foo'=>'bar')); 
* 
* This genererates following XML:
* 
* <?xml version="1.0" encoding="ISO-8859-1"?>
* <rootname foo="bar">some content</rootname>
*
* $root now references the 'rootname' node object.
* To append childNodes use the appendChild method.
*
* $foo =& $root->appendChild('foo', 'bar');
*
* Note: From version 1.1 you can use the $obj->add() method
* as shortcut to appendchild
*
* <?xml version="1.0" encoding="ISO-8859-1"?>
* <rootname foo="bar">some content<foo>bar</foo></rootname>
*
* @deprecated 2012-03-03 Use ContenidoXmlWriter instead
*
* !! ALWAYS use '=&' with the addRoot and appendChild methods. !!
*
*/
class XmlTree
{	
	/**
	* XML Version string
	* @var string
	* @access private	
	*/
	var $_strXmlVersion;
	
	/**
	* XML Encoding string
	* @var string
	* @access private	
	*/
	var $_strXmlEncoding;
	
	/**
	* Root element name
	* @var string
	* @access private	
	*/
	var $_strRootName;
	
	/**
	* Root content
	* @var string
	* @access private	
	*/
	var $_strRootContent;
	
	/**
	* Root attributes
	* @var array
	* @access private	
	*/
	var $_strRootAttribs;
	
	/**
	* Root node 
	* @var object
	* @access private	
	*/
	var $_objRoot;	
	
	/**
	* Tree XML string
	* @var string
	* @access private	
	*/
	var $_strXml;
	
	/**
	* Indent character
	* @var string
	* @access private	
	*/
	var $_indentChar = "";
	
	/**
	* Constructor
	*
	* @param string XML Version i.e. "1.0"
	* @param string XML Encoding i.e. "UTF-8" 
	*
	* @return void	
	*/	
	function XmlTree($strXmlVersion = '1.0', $strXmlEncoding = 'UTF-8')
	{
		cDeprecated("Use ContenidoXmlWriter instead.");
	
		$this->_strXmlVersion = 'version="'.$strXmlVersion.'"';
		$this->_strXmlEncoding = 'encoding="'.$strXmlEncoding.'"';
	}
	
	/**
	* Add a Root element to the XML Tree
	*
	* @param string XML Node Name 
	* @param string XML Node Content 
	* @param array Attributes array('name'=>'value')
	*
	* @return object Reference to the root node object
	*/		
	function &addRoot($strNodeName, $strNodeContent = '', $arrNodeAttribs = array())
	{		
		if (!$strNodeName)
		{
			return 'XmlTree::addRoot() -> No node name specified';
		}
		
		$this->_objRoot = new XmlNode($strNodeName, $strNodeContent, $arrNodeAttribs);
		return $this->_objRoot;				
	}	
	
	/**
	* Print or Return Tree XML
	*
	* @param boolean Return content 
	*
	* @return string Tree XML
	*/				
	function dump($bolReturn = false)
	{	
		if (!is_object($this->_objRoot))
		{
			return 'XmlTree::dump() -> There is no root node';
		}
		
		$this->_objRoot->setIndent($this->_indentChar);
		$this->_strXml  = sprintf("<?xml %s %s?>\n", $this->_strXmlVersion, $this->_strXmlEncoding);
		$this->_strXml .= $this->_objRoot->toXml();
		
		if ($bolReturn)
		{
			return $this->_strXml;			
		}
		
		echo $this->_strXml;
	}
	
	/**
	 * Set the indent string
	 * @param int level
	 * @return void
	 */
	function setIndent($string)
	{
		$this->_indentChar = $string;
	}
		
} // XmlTree

/**
* XmlNode Object
*
* Object of a XML Tree Node
*
* @see XmlTree
* @deprecated 2012-03-03 Use ContenidoXmlWriter instead
*
* !! ALWAYS use '=&' with the addRoot and appendChild methods. !!
*
*/
class XmlNode 
{	
	/**
	 * Indenting character
	 * @var string
	 */
	var $_indentChar;
	
	/**
	* Node name 
	* @var string
	* @access private
	*/
	var $_strNodeName;
		
	/**
	* Node content 
	* @var string
	* @access private
	*/
	var $_strNodeContent;
	
	/**
	* Added content 
	* @var string
	* @access private
	*/
	var $_strNodeContentAdded;
	
	/**
	* Node attributes 
	* @var array
	* @access private
	*/
	var $_arrNodeAttribs;
	
	/**
	* Enclose node content in a cdata section 
	* @var boolean
	* @access private
	*/
	var $_cdata;
	
	/**
	* XML for this node 
	* @var string
	* @access private
	*/
	var $_strXml;
	
	/**
	* Child count  
	* @var int
	* @access private
	*/
	var $_intChildCount = 0;
	
	/**
	* Parent Node 
	* @var object	
	*/
	var $parentNode = 0;
	
	/**
	* Childnodes 
	* @var array
	* @access private
	*/
	var $childNodes = array();
	
	/**
	* Class Constructor
	*
	* @param string XML Node Name 
	* @param string XML Node Content 
	* @param array Attributes array('name'=>'value')
	*
	* @return void	
	*/			
	function XmlNode($strNodeName, $strNodeContent = '', $arrNodeAttribs = array(), $cdata = false)
	{
		cDeprecated("Use ContenidoXmlWriter instead.");
		
		if (!$strNodeName)
		{
			return $this->_throwError($this, '%s::Construtctor() : No node name specified.');
		}
		
		$this->_cdata = $cdata;
		
		$this->setNodeName($strNodeName);
		$this->setNodeContent($strNodeContent);
		$this->setNodeAttribs($arrNodeAttribs);
	}
	
	/**
	* Set a node name
	*
	* @param string Node name 
	*
	* @return void	
	*/				
	function setNodeName($strNodeName)
	{
		$this->_strNodeName = $strNodeName;		
	}
	
	/**
	* Set node content
	*
	* @param string Node content 
	*
	* @return void	
	*/				
	function setNodeContent($strNodeContent)
	{
		$this->_strNodeContent = $strNodeContent;	
	}
	
	/**
	* Set the node attributes
	*
	* @param array Node attributes array('name'=>'value') 
	*
	* @return void	
	*/				
	function setNodeAttribs($arrNodeAttribs)
	{
		$this->_arrNodeAttribs = $arrNodeAttribs;			
	}
	
	/**
	* Set the node parent
	*
	* @param object Reference to the parent object 
	*
	* @return void	
	*/					
	function setNodeParent(&$objParent)
	{
		$this->parentNode = $objParent; 
	}	
	
	/**
	* Add content to the node
	*
	* @param string Content 
	*
	* @return void	
	*/					
	function addNodeContent($strNodeContent)
	{
		$this->_strNodeContentAdded = $strNodeContent;		
	}
	
	/**
	* Check if the node has childs
	*
	* @return boolean 	
	*/					
	function hasChilds()
	{
		return ($this->_intChildCount > 0) ? true : false;
	}
	
	/**
	* Add a child child node 
	*
	* @param string XML Node Name 
	* @param string XML Node Content 
	* @param array Attributes array('name'=>'value')
	* @param bool CDATA Section
	*
	* @return object Reference to the new node object
	*/		
	function &appendChild($strNodeName, $strNodeContent = '', $arrNodeAttribs = array(), $cdata = false)
	{	
		if (!$strNodeName)
		{
			return $this->_throwError($this, '%s::appendChild() : No node name specified');
		}
		
		$pos = $this->_intChildCount ++;
		
		$this->childNodes[$pos] = new XmlNode($strNodeName, $strNodeContent, $arrNodeAttribs, $cdata);
		$this->childNodes[$pos]->setNodeParent($this);
		
		return $this->childNodes[$pos];						
	}
	
	/**
	* Short for appendChild method 
	*
	* @see appendChild
	*/		
	function &add($strNodeName, $strNodeContent = '', $arrNodeAttribs = array(), $cdata = false)
	{
		return $this->appendChild($strNodeName, $strNodeContent , $arrNodeAttribs , $cdata);
	}
	
	/**
	* Builds the XML string for the node using the
	* node properties 
	*
	* @return string XML String
	* @access private
	*/		
	function toXml($indent = 0)
	{			
		// Indent for nodes markub
		$sp  = $this->_getIndent($indent);
		
		// Indent for content
		$csp = $this->_getIndent($indent ++);
		
		// Increment indent
		//$indent ++;
		
		$this->_strXml = "$sp<".$this->_strNodeName.$this->_parseAttributes($this->_arrNodeAttribs);
		
		$maxNodes = count($this->childNodes);
		
		if ($this->_strNodeContent != '' || $this->_strNodeContentAdded != '' || $maxNodes > 0)
		{		
			$this->_strXml .= ">";			
				
			if ($this->_cdata)
			{
				$this->_strXml .= "$csp<![CDATA[ ";
				
				$content = $this->_strNodeContent . $this->_strNodeContentAdded;
				$this->_strXml .= $content; 
				
			} else {
				$content = $this->_strNodeContent . $this->_strNodeContentAdded;
				$this->_strXml .= ($content != "") ? "$content" : $content; 	
			}
			
			if ($this->_cdata)
			{
				$this->_strXml .= " ]]>";
			}
						
			for ($i=0; $i<$maxNodes; $i++)
			{			
				$this->childNodes[$i]->setIndent($this->_indentChar);
				$this->_strXml .= $this->childNodes[$i]->toXml($indent);
			}	
											
			$this->_strXml .= "$sp</" . $this->_strNodeName . ">\n";			
			
		} else
		{			
			$this->_strXml .= "/>\n";			
		}
		
		return $this->_strXml;
	}
	
	/**
	* Builds a string from the attributes array 
	*
	* @param array Attributes array('name'=>'value')
	*
	* @return string Attribute string
	* @access private
	*/		
	function _parseAttributes($arrAttributes = array())
	{		
		$strAttributes = '';
		
		if (is_array($arrAttributes))
		{
			foreach ($arrAttributes as $name => $value)
    		{			
    			$strAttributes .= ' '.$name.'="'.$value.'"';	
    		}
		}
				
		return $strAttributes;		
	}
	
	/**
	 * Get indent string
	 * @param int level
	 * @return string indent string
	 */
	function _getIndent($level)
	{	
		return $this->_strXml .= str_repeat($this->_indentChar, $level);		
	}
	
	/**
	 * Set the indent string
	 * @param int level
	 * @return void
	 */
	function setIndent($string = "")
	{
		$this->_indentChar = $string;
	}
		
} // XmlNode

?>