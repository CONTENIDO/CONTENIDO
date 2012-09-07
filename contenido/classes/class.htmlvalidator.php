<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Validates the HTML
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.6.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *
 *   $Id: class.htmlvalidator.php 469 2008-07-02 09:44:45Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.htmlparser.php");

class cHTMLValidator
{

	var $doubleTags;
	var $htmlParser;
	var $html;
	var $nestingLevel;
	var $iNodeName;
	var $nestingNodes;
	var $_existingTags;

	function cHTMLValidator()
	{
		$this->doubleTags = array ("form", "head", "body", "html", "td", "tr", "table", "a", "tbody", "title", "container", "span", "div");

		$this->nestingLevel = array ();
		$this->missingNodes = array ();
		$this->nestingNodes = array ();
		$this->_existingTags = array ();

	}

	function cleanHTML($html)
	{

		// remove all php code from layout
		$resultingHTML = preg_replace('/<\?(php)?((.)|(\s))*?\?>/i', '', $html);

		/* We respect only \n, but we need to take care of windows (\n\r) and other systems (\r) */
		$resultingHTML = str_replace("\r\n", "\n", $resultingHTML);
		$resultingHTML = str_replace("\r", "\n", $resultingHTML);

		return $resultingHTML;

	}

	function validate($html)
	{
		$nestingLevel = 0;

		/* Clean up HTML first from any PHP scripts, and clean up line breaks */
		$this->html = $this->cleanHTML($html);

		$htmlParser = new HtmlParser($this->html);

		$nesting = 0;

		while ($htmlParser->parse())
		{
			$this->_existingTags[] = $htmlParser->iNodeName;
			/* Check if we found a double tag */
			if (in_array($htmlParser->iNodeName, $this->doubleTags))
			{
				if (!array_key_exists($htmlParser->iNodeName, $this->nestingLevel))
				{
					$this->nestingLevel[$htmlParser->iNodeName] = 0;
				}

				if (!array_key_exists($htmlParser->iNodeName, $this->nestingNodes))
				{
					$this->nestingNodes[$htmlParser->iNodeName][intval($this->nestingLevel[$htmlParser->iNodeName])] = array ();
				}

				/* Check if it's a start tag */
				if ($htmlParser->iNodeType == NODE_TYPE_ELEMENT)
				{

					/* Push the current element to the stack, remember ID and Name, if possible */
					$nestingLevel ++;

					$this->nestingNodes[$htmlParser->iNodeName][intval($this->nestingLevel[$htmlParser->iNodeName])]["name"] = $htmlParser->iNodeAttributes["name"];
					$this->nestingNodes[$htmlParser->iNodeName][intval($this->nestingLevel[$htmlParser->iNodeName])]["id"] = $htmlParser->iNodeAttributes["id"];
					$this->nestingNodes[$htmlParser->iNodeName][intval($this->nestingLevel[$htmlParser->iNodeName])]["level"] = $nestingLevel;
					$this->nestingNodes[$htmlParser->iNodeName][intval($this->nestingLevel[$htmlParser->iNodeName])]["char"] = $htmlParser->iHtmlTextIndex;
					$this->nestingLevel[$htmlParser->iNodeName]++;
				}

				if ($htmlParser->iNodeType == NODE_TYPE_ENDELEMENT)
				{
					/* Check if we've an element of this type on the stack */
					if ($this->nestingLevel[$htmlParser->iNodeName] > 0)
					{
						unset ($this->nestingNodes[$htmlParser->iNodeName][$this->nestingLevel[$htmlParser->iNodeName]]);
						$this->nestingLevel[$htmlParser->iNodeName]--;

						if ($this->nestingNodes[$htmlParser->iNodeName][intval($this->nestingLevel[$htmlParser->iNodeName])]["level"] != $nestingLevel)
						{
							/* Todo: Check for the wrong nesting level */
						}

						$nestingLevel --;

					}
				}
			}
		}

		/* missingNodes should be an empty array by default */
		$this->missingNodes = array ();

		/* Collect all missing nodes */
		foreach ($this->nestingLevel as $key => $value)
		{
			/* One or more missing tags found */
			if ($value > 0)
			{
				/* Step trough all missing tags */
				for ($i = 0; $i < $value; $i ++)
				{
					$node = $this->nestingNodes[$key][$i];

					list ($line, $char) = $this->getLineAndCharPos($node["char"]);
					$this->missingNodes[] = array ("tag" => $key, "id" => $node["id"], "name" => $node["name"], "line" => $line, "char" => $char);

					$this->missingTags[$line][$char] = true;
				}
			}
		}

	}

	function tagExists($tag)
	{
		if (in_array($tag, $this->_existingTags))
		{
			return true;
		} else
		{
			return false;
		}
	}

	function returnErrorMap()
	{
		$html .= "<pre>";

		$chunks = explode("\n", $this->html);

		foreach ($chunks as $key => $value)
		{
			$html .= ($key +1)." ";

			for ($i = 0; $i < strlen($value); $i ++)
			{
				$char = substr($value, $i, 1);

				if (is_array($this->missingTags[$key +1]))
				{
					//echo ($key+1) . " ". $i."<br>";
					if (array_key_exists($i +2, $this->missingTags[$key +1]))
					{
						$html .= "<u><b>".htmlspecialchars($char)."</b></u>";
					} else
					{
						$html .= htmlspecialchars($char);
					}
				} else
				{
					$html .= htmlspecialchars($char);
				}

			}

			$html .= "<br>";

		}

		return $html;
	}

	function getLineAndCharPos($charpos)
	{
		$mangled = substr($this->html, 0, $charpos);

		$line = substr_count($mangled, "\n") + 1;
		$char = $charpos -strrpos($mangled, "\n");

		return array ($line, $char);
	}

}
?>