<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Converts XML data to PHP array
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0
 * @author     Marco Jahn
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 
 *   
 *   $Id: class.xml2array.php 738 2008-08-27 10:21:19Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if (version_compare(PHP_VERSION, '5', '>='))
{
	cInclude("includes", "functions.domxml-php4-to-php5.php"); // Enable PHP4 domxml under PHP 5 ff
}
 
class cApiXml2Array
{
	/**
	 * Result array
	 * @var array
	 * @access private
	 */
	var $_aResult = array();
	
	/**
	 * XML data
	 * @var string
	 * @access private
	 */
	var $_sXML = '';
	
	/**
	 * Constructor
	 */
	function cApiXml2Array ()
	{
	 // empty
	}
	
	/**
     * load XML Data
     *
     * @param string XML data
     *
     * @return boolean
     *
     * @access public
     */	
	function loadData ($sXML)
	{
		if (substr(trim($sXML), 0, 5) != "<?xml")
		{ 
			// check for file
			if (@file_exists($sXML))
			{
				$sXML = file_get_contents($sXML);
			}
		}
		
		$this->xml_string = $sXML;
		
		// check for string, open in dom
		if (is_string($sXML))
		{
			$sXML = @domxml_open_mem($sXML);
			
			if (!$sXML)
			{
				return false;
			}
			$this->root_element = $sXML->document_element();
		}		

		// check for dom-creation, 
		if (is_object($sXML) && $sXML->node_type() == XML_DOCUMENT_NODE)
		{
			$this->root_element = $sXML->document_element();
			//$this->xml_string = $xml->dump_mem(true);
			return true;
		}

		if (is_object($sXML) && $sXML->node_type() == XML_ELEMENT_NODE)
		{
			$this->root_element = $sXML;
			return true;
		}

		return false;
	}
	
	/**
     * Get result array
     *
     * @param array aMergeTags  Defines the tag names to merge
     * @return array containing data as array or false
     *
     * @access public
     */	
	function getResult ($aMergeTags = array())
	{
		if ($resultDomNode = $this->root_element)
		{
			$array_result[$resultDomNode->tagname()] = $this->_recNode2Array( $resultDomNode, $aMergeTags );
			return $array_result;
		} else
		{
			return false;
		}
	}
	
	
	/**
     * Recursive function to walk through dom and create array
     *
     * @param string xml
     *
     * @return array result
     *
     * @access private
     */	
	function _recNode2Array($domnode, $aMergeTags)
	{

		if ($domnode->node_type() == XML_ELEMENT_NODE)
		{
			$childs = $domnode->child_nodes();

			/* fetch attributes on all levels */
			if ($domnode->has_attributes())
			{
				if (is_array($domnode->attributes()))
				{
					foreach ($domnode->attributes() as $attrib)
					{
						$prefix = ($attrib->prefix()) ? $attrib->prefix().':' : '';
						$result["@".$attrib->name()] = $attrib->value();
					}
				}
			}
			
			$result["type"] = $domnode->node_name();
			
			if (!is_array($childs))
			{
				$childs = array();	
			}
			
			foreach($childs as $child)
			{				
				switch ($child->node_type())
				{	
					case XML_ELEMENT_NODE:
						if (is_array($aMergeTags))
						{
							if (in_array($child->node_name(), $aMergeTags))
							{
								$sTagName = "merged";
							} else {
								$sTagName = $prefix.$child->node_name();
							}
						} else {
							$sTagName = $prefix.$child->node_name();
						}
					
						// TODO: Check the following subnode code (see below)
						#$subnode = false;
						// TODO: Check this line, as it should be too late, to specify it here (see above)
						$prefix = ($child->prefix()) ? $child->prefix().':' : '';
						
						$result[$sTagName][] = $this->_recNode2Array($child, $aMergeTags);
						break;
					case XML_CDATA_SECTION_NODE:
						$result["content"] = $child->get_content();
						break;
					case XML_TEXT_NODE:
						$result["content"] = $child->get_content();
						break;
				}
			}
	
			if (!is_array($result)){
				// TODO
				// correct encoding from utf-8 to locale
				// NEEDS to be updated to correct in both ways!
				#$result['#text'] = html_entity_decode(htmlentities($domnode->get_content(), ENT_COMPAT, 'UTF-8'), ENT_COMPAT,'ISO-8859-1');
				#$result = html_entity_decode(htmlentities($domnode->get_content(), ENT_COMPAT, 'UTF-8'), ENT_COMPAT,'ISO-8859-1');
				$result = $this->dummy_html_entity_decode(htmlentities($domnode->get_content(), ENT_COMPAT, 'UTF-8'));
			}
	
			return $result;
		}
	}
	
	/**
     * Get encoding
     *
     * @return string encoding
     *
     * @access private
     */	
	function _getEncoding()
	{
		preg_match("~\<\?xml.*encoding=[\"\'](.*)[\"\'].*\?\>~i",trim($this->xml_string),$matches);

		return ($matches[1])?$matches[1]:"";
	}
	
	function dummy_html_entity_decode ($string)
	{
	   $trans_tbl = get_html_translation_table(HTML_ENTITIES);
	   $trans_tbl = array_flip($trans_tbl);
	   return strtr($string, $trans_tbl);	
	}
	
	/**
     * Get namespace
     *
     * @return string namespace
     *
     * @access private
     */	
	function _getNamespaces()
	{
		preg_match_all("~[[:space:]]xmlns:([[:alnum:]]*)=[\"\'](.*?)[\"\']~i",$this->xml_string,$matches,PREG_SET_ORDER);
		foreach( $matches as $match )
			$result[ $match[1] ] = $match[2];
		return $result;
	}
    
    function setSourceEncoding ($sEncoding)
    {
        // TODO
    }
    
    function setTargetEncoding ($sEncoding)
    {
        // TODO
    }
}

?>