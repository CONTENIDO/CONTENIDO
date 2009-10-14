<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Class XML_doc
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    4fb_XML
 * @version    0.9.7
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified unknown, Martin Horwath <horwath@dayside.net>
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * Class XML_doc
 *
 * Simple class for extracting values
 * from a XML document. Uses a simplified
 * XPath syntax to access the elements.
 * For example: 'root/person/name' would
 * return the value of the 'name' node in
 * 'root/person' node. You have to specify
 * the root node name. Can't access node
 * attributes yet.
 *
 */
class XML_doc {

    /**
     * @var array $errors
     */
     var $errors = array();

    /**
     * @var string xml
     */
     var $xml;

    /**
     * @var parsed array
     */
    var $parsearray;

    /**
     * @var help array
     */
    var $itemname;

    /**
     * XML Parser Object
     */
    var $parser;

    /**
     * XML encoding
     */
    var $encoding;

    /**
     * Class Construcor
     */
    function XML_doc() {
      // do nothing
    } // end function


    /**
     * load()
     *
     * Load the XML file
     *
     * @param string XML document filename
     * @return boolean true if the load was successful
     */
    function load($filename) {

        if (file_exists($filename) && !is_dir($filename)) {
            $fp = fopen ($filename, "rb");

            if ($fp === false)
            {
                return (false);
            }

            unset($this->xml);
            $iFilesize = filesize ($filename);
            // check for 0 filesize
            if($iFilesize > 0) {
              $this->xml = fread ($fp, $iFilesize);
              fclose ($fp);
            } else {
              fclose ($fp);
              return (false);
            }
           

            // useful if entities are found in xml file
            $this->xml = $this->_translateLiteral2NumericEntities($this->xml);

            // get source encoding from file
            if (preg_match('/<\?xml.*encoding=[\'"](.*?)[\'"].*\?>/m', $this->xml, $m)) {
                $this->encoding = strtoupper($m[1]);
            } else {
                $this->encoding = "UTF-8";
            }

            //print_r($this->xml);
            unset($this->parsearray);
            return (true);

        } else {
            //die('no XML file ('.$filename.')');
            return (false);

        }

    } // end function


    /**
     * valueOf()
     *
     * Extract one node value from the XML document.
     * Use simplified XPath syntax to specify the node.
     * F.e. 'root/test/firstnode'
     *
     * @return String Value of XML node
     */
     function valueOf($xpath) {

        if (!is_array($this->parsearray)) { // build tree once
            $this->parse(false);
        }

        $aCombination = explode("/", $xpath);

        $keynode = "";

        foreach ($aCombination as $key => $value) {
            $keynode .= "['".$value."']";
        }

        eval('$value = $this->parsearray'.$keynode.';');


        if ($value == NULL)
        {
            $aLoosePath = $this->findLoosePath($aCombination);

            if (is_array($aLoosePath))
            {
                $keynode = "";
                foreach ($aLoosePath as $key => $value) {
                    $keynode .= "['".$value."']";
                }

                eval('$value = $this->parsearray'.$keynode.';');

            }

        }

        if ($value == NULL) { return "Not found: $xpath"; }

        if (is_array($value)) { return "Has children"; }

        return $value;

    } // end function

    /**
     * findLoosePath: Finds a path in the XML array which
     *                ends with the given keys
     *
     * @param aCombination  array of keys to test for
     * @return false if nothing was found, or the array with the found keys
     */
    function findLoosePath ($aCombination, $aScope = true)
    {
        if (count($aCombination) == 0)
        {
            return false;
        }

        $keynode = "";
        foreach ($aCombination as $key => $value) {
            $keynode .= "['".$value."']";
        }

        if ($aScope===true) {
            $aScope = $this->parsearray;
        }

        if (!is_array ($aScope)) {
            return false;
        }

		$found = false;
        foreach($aScope as $aScopeKey => $aScopeValue) {
           if (!is_array ($aScope[$aScopeKey])) {
               return false;
           }

           eval('if (isset ($aScope['.$aScopeKey.']'.$keynode.') ) { $found = true; }');
           if ($found) {
               $rCombination = array_merge(Array($aScopeKey), $aCombination);
               break;
           }

           $rCombination = $this->findLoosePath($aCombination, $aScopeValue);
           if (is_array($rCombination)) {
               $rCombination = array_merge(Array($aScopeKey), $rCombination);
               break;
           }
        }

        return $rCombination;
    }


    /**
     * parse()
     *
     * Parse the xml file in an array
     *
     *
     *
     * @return array parsearray
     */

    function parse($send=true) {
    	global $lang, $aLanguageEncodings;
        // set up a new XML parser to do all the work for us
        $this->parser = xml_parser_create($this->encoding);
        
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, "startElement", "endElement");
        xml_set_character_data_handler($this->parser, "characterData");

        // parse the data and free the parser...
        xml_parse($this->parser, $this->xml);
        xml_parser_free($this->parser);
        if ($send) return $this->parsearray;
    } // end function

    /**
     *
     *
     *
     */
    function startElement($parser, $name, $attrs) {
        // Start a new Element.  This means we push the new element onto
        // store the name of this element
        $this->itemname[]="$name";
    } // end function

    /**
     *
     *
     *
     */
    function endElement($parser, $name) {
        // End an element.  This is done by popping the last element from
        // the stack and adding it to the previous element on the stack.
        // delete the old element from itemname
        array_pop($this->itemname);
    } // end function

    /**
     *
     *
     *
     */
    function characterData($parser, $data) {
        // Collect the data onto the end of the current chars it dont collect whitespaces.

        $data = preg_replace ( "/[[:space:]]+/i", " ", $data );

        if(trim($data)){
           //search for the element path
           foreach($this->itemname as $value){
                    $value = str_replace("'", "\'", $value);
                   $pos.="['$value']";
           }

           // looks stupid but is useful to take care of entities, this corrects the line break issue
           eval("if (isset(\$this->parsearray$pos)) { \$data = \$this->parsearray$pos.trim(\$data); }");

           //set the new data in the parsearray
           eval("\$this->parsearray$pos=trim(\$data);");

        }

    } // end function

    /**
     * Translate literal entities to their numeric equivalents and vice versa.
     *
     * PHP's XML parser (in V 4.1.0) has problems with entities! The only one's that are recognized
     * are &amp;, &lt; &gt; and &quot;. *ALL* others (like &nbsp; &copy; a.s.o.) cause an
     * XML_ERROR_UNDEFINED_ENTITY error. I reported this as bug at http://bugs.php.net/bug.php?id=15092
     * The work around is to translate the entities found in the XML source to their numeric equivalent
     * E.g. &nbsp; to   / &copy; to © a.s.o.
     *
     * NOTE: Entities &amp;, &lt; &gt; and &quot; are left 'as is'
     *
     * @author Sam Blum bs_php@users.sourceforge.net
     * @param string $xmlSource The XML string
     * @param bool   $reverse (default=FALSE) Translate numeric entities to literal entities.
     * @return The XML string with translated entities.
     */
    function _translateLiteral2NumericEntities($xmlSource, $reverse = FALSE) {
        static $literal2NumericEntity;

        if (empty($literal2NumericEntity)) {
            $transTbl = get_html_translation_table(HTML_ENTITIES);
            foreach ($transTbl as $char => $entity) {
                if (strpos('&"<>', $char) !== FALSE) continue;
                    $literal2NumericEntity[$entity] = '&#'.ord($char).';';
            }
        }

        if ($reverse) {
            return strtr($xmlSource, array_flip($literal2NumericEntity));
        } else {
            return strtr($xmlSource, $literal2NumericEntity);
        }
    }


} // end class XML_doc

?>