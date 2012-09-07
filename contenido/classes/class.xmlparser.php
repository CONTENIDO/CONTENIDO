<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido XML Parser
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.9
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.xmlparser.php 387 2008-06-30 10:01:05Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * Class for parsing XML documents using SAX
 *
 * This class is a abstraction class for the PHP Expat XML functions. 
 * 
 * You can define handler functions/objects for start, end, PI and data sections (1.) or
 * your can define path which will trigger the defined event when encountered (2.)
 *
 * Example:
 *
 * 1.) $parser->setEvents(array("startElement"=> "myFunction", 
 *                              "endElement"=> "myFunction", 
 *                              "characterData"=> "myFunction", 
 *                              "processingInstruction" => "myFunction");
 *
 * The value can also be an array with the object reference and the method to call.
 * i.e. "startElement"=>array(&$myObj, "myMethod") instead of "startelement"=>"myFunction"
 *
 * 2.) $parser->setEvents(array("/root/foo/bar"=>"myFunction"));
 * 
 * Valid array keys are: 'startElement', 'endElement', 'characterData', 'processingInstruction' and paths
 * folowing the scheme '/root/element'. The path MUST begin from the root element and MUST start with '/'.
 *
 * The value can also be an array with the object reference and the method to call.
 * i.e. "/foo/bar"=>array(&$myObj, "myMethod") instead of "/foo/bar"=>"myFunction" 
 * 
 * It has 3 public methods:
 *
 * setEventHandlers - Set specific handlers for the xml parser
 * parseFile        - Used to parse a XML file
 * parse 	        - Used to parse a XML string
 *
 * A small example:
 * 
 * include ("class.xmlparser.php");
 * 
 * // The XML String
 * $xml = '
 * <?xml version="1.0"?>
 * <foo>
 *     <bar>some text</bar>
 *     <bar>another text</bar>	
 * </foo>';	
 * 
 * function myHandler($name, $attribs, $content)
 * {
 *     echo "<b style='color:red'>HIT</b>: [ <b>$name</b> ] [ $content ]<br/>";
 * }
 *
 * $parser = new XmlParser; // Parser instance
 * $parser->setEventHandlers(array("/foo/bar"=>"myHandler")); // Define our handler
 * $parser->parse($xml); // Parse the XML string
 *
 * Report bugs to: jan.lengowski@4fb.de
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 * @version 1.0
 * @package 4fb_XML
 */
class XmlParser
{
	/**
     * XML Parser autofree
	 *
     * @var bool
     */
	var $autofree = true;
	
    /**
     * XML Parser object
	 *
     * @var object
	 * @access private
     */
	var $parser;

    /**
     * Error message
	 *
     * @var string
	 * @access private
     */
 	var $error = '';

    /**
     * Element depth
     * @var int
	 * @access private
     */
 	var $depth = -1;
	
	/**
 	 * Element counter
	 * @var int
	 * @access private
	 */
	var $count = 0;
	
	/**
	 * Path counter
	 * @var int
	 * @access private
	 */
	var $pcount = 0;
	
	/**
	 * Array for creating the path
	 * @var array
	 * @access private
	 */
	var $paths = array();
	
	/**
	 * Data storage container for the path data
	 * @var array
	 * @access private
	 */
	var $pathdata = array();
	
	/**
	 * String storing the active path
	 * @var string
	 * @access private
	 */
	var $activepath = '';
	
	/**
	 * The active node
	 * @var string
	 * @access private
	 */
	var $activenode = '';
	
	/**
	 * The defined events
	 * @var array
	 * @access private
	 */
	var $events = array();
	
	/**
	 * Constructor function
	 *
	 * @access private
	 * @param string	$sEncoding	Encoding used when parsing files (default: UTF-8, as in PHP5) 
	 * @return void
	 */
	function XmlParser($sEncoding = false)
	{
		if (!$sEncoding)
		{
			$sEncoding = "UTF-8";
		}
		
		$this->_init($sEncoding);
	}

    /**
     * Initialize the XML Parser object and sets all options
     *
     * @access private
	 * @param string	$sEncoding	Encoding used when parsing files (default: UTF-8, as in PHP5)
     * @return void
     */
	function _init($sEncoding = false)
	{
		if (!$sEncoding)
		{
			$sEncoding = "UTF-8";
		}
		// Create parser instance
        $this->parser = xml_parser_create($sEncoding);
        
        // Set parser options
        xml_set_object($this->parser, $this);
    	xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
    	xml_parser_set_option($this->parser, XML_OPTION_TARGET_ENCODING, $sEncoding);
    	
    	xml_set_element_handler($this->parser, '_startElement', '_endElement');
        xml_set_character_data_handler($this->parser, '_characterData');
        xml_set_processing_instruction_handler($this->parser, '_processingInstruction');
        
        // Misc stuff
		$this->events['paths'] = array(NULL);        
	}

	/**
	 * Returns the XML error message
	 *
	 * @return string XML Error message
	 * @access private
	 */
	function _error()
	{
	    $this->error = "XML error: ".xml_error_string(xml_get_error_code($this->parser))." at line ".xml_get_current_line_number($this->parser);
	    return $this->error;	    
	}	
	
	/**
	 * Define events for the XML parser
	 *
	 * You can define handler functions/objects for start, end, PI and data sections (1.) or
	 * your can define path which will trigger the defined event when encountered (2.)
	 *
	 * Example:
	 *
	 * 1.) $parser->setEvents(array("startElement"  		=> "myFunction", 
	 *								"endElement"    		=> "myFunction", 
	 * 								"characterData" 		=> "myFunction", 
	 *								"processingInstruction" => "myFunction");
	 *
	 * The value can also be an array with the object reference and the method to call.
	 * i.e. "startElement"=>array(&$myObj, "myMethod") instead of "startelement"=>"myFunction"
	 *
	 * 2.) $parser->setEvents(array("/root/foo/bar"=>"myFunction"));
 	 * 
	 * Valid array keys are: 'startElement', 'endElement', 'characterData', 'processingInstruction' and paths
	 * folowing the scheme '/root/element'. The path MUST begin from the root element and MUST start with '/'.
	 *
	 * The value can also be an array with the object reference and the method to call.
	 * i.e. "/foo/bar"=>array(&$myObj, "myMethod") instead of "/foo/bar"=>"myFunction"
	 *
	 * @param array Options array, valid keys are 'startElement', 'endElement', 'characterData', 'processingInstruction', or a path
	 *
	 * @access public
	 * @return void
	 */
	function setEventHandlers($options = array(NULL))
	{			
		$options = $this->_changeKeyCase($options);
		
		if (array_key_exists('startelement', $options))
		{			
			$this->events['startelement'] = $options['startelement'];
		}
		
		if (array_key_exists('endelement', $options))
		{
			$this->events['endelement'] = $options['endelement'];
		}
		
		if (array_key_exists('characterdata', $options))
		{
			$this->events['characterdata'] = $options['characterdata'];
		}
		
		if (array_key_exists('processinginstruction', $options))
		{
			$this->events['processinginstruction'] = $options['processinginstruction'];
		}
		
		$paths = $this->_getDefinedPaths($options);
		
		$this->events['paths'] = $paths;		
	}
	
	/**
	 * Set the processing instruction handler
	 *
	 * @param string Processing instruction handler
	 * 
	 * @return void
	 * @access private
	 */
	function _processingInstruction($parser, $target, $data)
	{
		$handler = $this->_getEventHandler('processinginstruction');
		
		if ($handler)
		{
			if (is_array($handler))
			{
				$handler[0]->$handler[1]($target, $data);
			} else
			{
				$handler($target, $data);
			}		
		}						
	}
	
	/**
	 * Change all array keys to lowercase 
	 * (PHP function change_key_case is available at PHP 4.2 +)
	 * 
	 * @param array Source array
	 * 
	 * @return array Array with lowercased keys
	 * @access private
	 */
	function _changeKeyCase($options = array())
	{
		$tmp = array();
		
		foreach ($options as $key => $value)
		{
			$tmp[strtolower($key)] = $value;
		}
		
		return $tmp;		
	}
	
	/**
	 * Returns events handlers if set
	 *
	 * @param string Event type
	 *
	 * @return sring Event handler name
	 * @access private
	 */
	function _getEventHandler($event)
	{
		// Standard events
		if (array_key_exists($event, $this->events))
		{			
			return $this->events[$event];
		}
		
		// Paths events
		if (array_key_exists($event, $this->events['paths']))
		{			
			return $this->events['paths'][$event];
		}
		
		// No events found
		return false;
	}
	
	/**
 	 * Return all defined paths from the options
	 * array and returns a new array containing
	 * only the paths to function bindings
	 *	
	 * @param array Options array
	 *
	 * @return array Paths array
	 * @access private
	 */
	function _getDefinedPaths($options)
	{
		$tmp = array();
		
		foreach ($options as $key => $value)
		{
			if (strstr($key, '/'))
			{
				$tmp[$key] = $value;
			}
		}
		
		return $tmp;
	}
	
	/**
 	 * Add a path to the stack
	 * 	
	 * @param string Element node name
	 * @access private
	 * @return void
	 */
	function _addPath($depth, $name)
	{
		$this->paths[$depth] = $name;			
	}
	
	/**
 	 * Returns the current active path
	 *		 
	 * @access private
	 */
	function _getActivePath()
	{			
		$tmp = array();
		
		for ($i=0; $i<=$this->depth; $i++)
		{				
			$tmp[] = $this->paths[$i];			
		}
																
		$path =  '/' . join('/', $tmp);						
		return $path;
	}
	
	/**
     * XML start element handler 
     *
     * @param resource XML Parser resource
     * @param string XML Element node name
     * @param array XML Element node attributes
	 *
     * @return void
	 * @access private
     */
    function _startElement($parser, $name, $attribs)
	{			
		// Increase depth
		$this->depth ++;
		
		// Set active node
		$this->activenode = $name;
		
		// Increase element counter
		if ($this->activenode == $this->pathdata[$this->activepath][$this->count]['name'])
		{
			$this->count ++;						
		} else 
		{
			$this->count = 0;		
		}
		
		// Entering element context, add subpath
		$this->_addPath($this->depth, $name);
		
		// Get the handler for this event
		$handler = $this->_getEventHandler('startelement');
		
		// If a handler is defined call it
		if ($handler)
		{
			if (is_array($handler))
			{								
				$handler[0]->$handler[1]($name, $attribs);	
			} else
			{
				$handler($name, $attribs);
			}	
		}
		
		// Check for defined path handlers
		$this->activepath = $this->_getActivePath();
		
		// Save path data
		$this->pathdata[$this->activepath][$this->count] = array('name'=>$name, 'attribs'=>$attribs);				
	}
	
	/**
	 * XML character data handler 
	 *
	 * @param resource XML Parser resource
	 * @param string XML node data
	 *
	 * @return void
	 * @access private
	 */
	function _characterData($parser, $data)
	{	
		// Reset node count 
		if ($this->activenode != $this->pathdata[$this->activepath][$this->count]['name'])
		{			
			$this->count = 0;		
		}
		
		// Save path data
		$this->pathdata[$this->activepath][$this->count]['content'] .= $data;
					
		// Get the handler for this event
		$handler = $this->_getEventHandler('characterdata');
		
		// If a handler is defined call it
		if ($handler)
		{
			if (is_array($handler))
			{
				$handler[0]->$handler[1]($data);
			} else
			{
				$handler($data);
			}			
		}	
	}	
	
	/**
	 * XML end element handler 
	 *
	 * @param resource XML Parser resource
	 * @param string XML Element node name
	 *
	 * @return void
	 * @access private
	 */
	function _endElement($parser, $name)
	{	
		// Get the handler for this event
		$handler = $this->_getEventHandler('endelement');
						
		// Call Element handler
		if ($handler)
		{
			if (is_array($handler))
			{
				$handler[0]->$handler[1]($name);
			} else
			{
				$handler($name);
			}			
		}		
		
		// Reset the active path
		$this->activepath = $this->_getActivePath();		
				
		// Get handler for the active path
		$handler = $this->_getEventHandler($this->activepath);
		
		// Call path handler
		if ($handler)
		{			
			if (is_array($handler))
			{ // Handler is an object method
					$handler[0]->$handler[1]($this->pathdata[$this->activepath][$this->count]['name'],
					 		 				 $this->pathdata[$this->activepath][$this->count]['attribs'],
							 				 $this->pathdata[$this->activepath][$this->count]['content']);						
			} else
			{ // Handler is a function
					$handler($this->pathdata[$this->activepath][$this->count]['name'],
					 		 $this->pathdata[$this->activepath][$this->count]['attribs'],
							 $this->pathdata[$this->activepath][$this->count]['content']);		
			}
		}
		
		// Decrease depth
		$this->depth --;
	}
	
	/**
	 * Parse a XML string
	 *
	 * @param string XML data	 
	 *
	 * @return bool
	 * @access public
	 */
	function parse($data, $final = false)
	{
	    $success = xml_parse($this->parser, trim($data), $final);
	
		if ($final && $this->autofree)
		{
			xml_parser_free($this->parser);
	    }
	
	    if (!$success)
		{
			return $this->_error();
	    }
	
		return $success;
	}

	/**
	 * Parse a XML file
	 *
	 * @param string File location	 
	 *
	 * @return bool
	 * @access public
	 */
	function parseFile($file) 
	{
		if (!($fp = fopen($file, "rb")))
		{
		  	
		}
		
		while ($sData = fread($fp, 4096)) 
		{
		  	if (!xml_parse($this->parser, $sData, feof($fp)))
			{
				$this->_error();
		    	return false;
			}
		}
		
		if ($this->autofree)
		{
			xml_parser_free($this->parser);
		}	
			
		return true;
	}	

} // XML_Parser

?>