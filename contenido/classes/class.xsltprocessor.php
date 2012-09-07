<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * XSLT_Processor class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    4fb_XML
 * @version    1.0.0
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
 *   $Id: class.xsltprocessor.php 387 2008-06-30 10:01:05Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * XSLT_Processor 
 * 
 * Wrapper class for the Sablotron XSLT extension
 *
 * !!! _REQUIRES_ Installed Sablotron to run !!!
 *
 * Example:
 * 
 * $xslt = new XSLT_Processor;
 * 
 * $xslt->setXmlFile("foo.xml");
 * $xslt->setXslFile("bar.xslt");
 *  
 * $html = $xslt->process();
 *
 */
class XsltProcessor
{
    /**
     * XSML Processor auto-free
     * @var bool
     * @access private
     */
    var $autofree = true;

    /**
     * Error message string
     * @var string
     * @access private
     */
    var $error = "";
    
    /**
     * Error number
     * @var int
     * @access private
     */
    var $errno = 0;

    /**
     * The result of the XSLT Transformation
     * @var string
     * @access private
     */
    var $result = "";
    
    /**
     * The XML String for the Transformation
     * @var string
     * @access private
     */
    var $xml = "";
    
    /**
     * The XSLT String for the Transformation
     * @var string
     * @access private
     */
    var $xslt = "";

    /**
     * XSLT Processor
     * @var object
     * @access private
     */
    var $processor;
    
    /**
     * XSLT Process arguments array
     * @var array
     * @access private
     */
    var $arguments = array();
    
    /**
     * XSLT Process parameters array
     * @var array
     * @access private
     */
    var $parameters = array();
    
    /**
     * Constructor
     * @access private
     */
    function XsltProcessor()
    {
        $this->_init();
    }
    
    /**
     * Initialize the class
     * @access private
     * @return void
     */
    function _init()
    {
        if (!function_exists("xslt_create"))
        {
            die ("Cannot instantiate XSLT Class \n XSLT not supported");
        }
        
        $this->processor = xslt_create();
    }
    
    /**
     * Translate literal to numeric entities to avoid
     * the 'undefined entity error' that a literal
     * entity would cause.
	 * 
     * @param string XML String with literal entities
     * @return string XML string with numeric entites
	 * @access private
     */
    function literal2NumericEntities($stringXml) {

        $literal2NumericEntity = array();

        if (empty($literal2NumericEntity))
        {
            $transTbl = get_html_translation_table(HTML_ENTITIES);

            foreach ($transTbl as $char => $entity)
            {
                if (strpos('&"<>', $char) !== FALSE) continue;
                $literal2NumericEntity[$entity] = '&#'.ord($char).';';
            }
        }

        return strtr($stringXml, $literal2NumericEntity);
    }
    
    
    /**
     * Set the XML to be Transformed
     * @param string The XML String
     * @return void
	 * @access public
     */
    function setXml($xml)
    {
        $this->arguments["/_xml"] = $this->literal2NumericEntities($xml);
    }
    
    /**
     * Set the XSLT for the Transformation
     * @param string The XML String
     * @return void
	 * @access public
     */
    function setXsl($xsl)
    {
        $this->arguments["/_xsl"] = $this->literal2NumericEntities($xsl);
    }
    
     /**
     * Set the XML-File to be Transformed
     * @param string Location of the XML file
     * @return void
	 * @access public
     */
    function setXmlFile($file)
    {
        $xml = $this->readFromFile($file);
        $this->arguments["/_xml"] = $this->literal2NumericEntities($xml);
    }

    /**
     * Set the XSL-File for the Transformation
     * @param string Location of the XSL file
     * @return void
	 * @access public
     */
    function setXslFile($file)
    {
        $xsl = $this->readFromFile($file);
        $this->arguments["/_xsl"] = $this->literal2NumericEntities($xsl);
    }
    
    /**
     * Return the contents of a file if
     * the passed parameter is a file.
	 *
     * @param string File location
     * @return string File contents	
 	 * @access private
     */
    function readFromFile($file)
    {
        if (file_exists($file))
        {
            $data = file($file);
            $data = join($data, "");
            return $data;
        }

        die ("<span style=\"color: red\"><b>ERROR:</b></span> File not found: <b>$file</b>");
    }
    
    /**
     * Pass top level parameters to the XSLT processor.
	 * The parameters can be accessed in XSL 
	 * with <xsl:param name="paramname"/>
	 * 
     * @param string Name
     * @param string Value
     * @return void
     */
    function setParam($name, $value)
    {
        $this->parameters[$name] = utf8_encode($value);
    }
    
    /**
	 * Define external scheme handlers for the XSLT Processor.
	 *  
	 * Example param array: 
	 * 	
	 * array("get_all", "mySchemeHandler")
	 * 
	 * Example scheme handler function:
	 * 
	 * function mySchemeHandler($processor, $scheme, $param) 
	 * {
	 *     // to remove the first slash added by Sablotron
	 *	   $param = substr($param, 1);    
	 *
	 * 	   if ($scheme == 'file_exists')
	 * 	   {   // result is returned as valid xml string 
	 *	       return '<?xml version="1.0" encoding="UTF-8"?><root>'.(file_exists($param) ? "true" : "false")."</root>";
	 *     }   	    
	 * }
	 *  
	 * To use the schema handler use:
	 * <xsl:if test="document('file_exists:somefile.xml’)/root = 'true'">
	 *     do something 
	 * </xsl:if>
	 *	
	 * To call the external function use the 'document()' XSLT-Function.
	 *
	 * <xsl:value-of select="document('schemename:parameter')/root"/>
	 *  
	 * Schemename and parameter will be passed to the handler function as second and third parameter.
	 * The return value of the function must be valid XML to access it using XPath.
 	 *
	 * @param array array("scheme"=>"schemeHandlerName");
	 * @return void
	 * @access public
	 */
	function setSchemeHandlers($aHandlers)
	{
		xslt_set_scheme_handlers($this->processor, $aHandlers);	
	}

    /**
     * Transform the XML data using the XSL and
	 * return the results of the transformation 
	 *
     * @return string Transformed data
	 * @access public
     */
    function process()
    {
        $this->result = xslt_process($this->processor, "arg:/_xml", "arg:/_xsl", NULL, $this->arguments, $this->parameters);
        $this->error = xslt_error($this->processor);
        $this->errno = xslt_errno($this->processor);
        
        if ($this->autofree)
        {
            xslt_free($this->processor);
        }
        
        return $this->result;
    }
	
    /**
     * Prints the Error message and number
     * if an error occured
	 *
     * @return void
	 * @access public
     */
    function printErrors()
    {
        if ($this->errno > 0)
        {
            echo "<b>Error Number: </b><span style=\"color:red\">".$this->errno."</span> ";
            echo "<b>Error Message: </b><span style=\"color:red\">".$this->error."</span>";
        }
    }
    
    /**
     * Manual free of the parser
     * @return void
     */
    function free()
    {
        xslt_free($this->processor);
    }

    
} // XSLT_Processor

?>
