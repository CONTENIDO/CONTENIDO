<?php

/**
 * class cDataTextWidget
 * cDataTextWidget generates a textbox widget
 * for use with the data objects.
 */
class cDataTextWidget extends cHTMLTextbox
{
	/**
	 * cDataTextWidget: Creates a text box widget
	 *
	 * @param $name Name of the widget
	 * @param $parameters Parameters (see below)
	 *
	 * valid parameters for this control are:
	 * default		Default value for this box
	 *
	 * @return void
	 * @access public
	 */
	function cDataTextWidget ($name, $parameters)
	{
		cHTMLTextbox::cHTMLTextbox($name);
		
		if (array_key_exists("default", $parameters))
		{
			$this->setValue($parameters["default"]);	
		}
	}
}

/**
 * class cDataTextareaWidget
 * cDataTextareaWidget generates a textarea widget for use with the data objects.
 *
 */
class cDataTextareaWidget extends cHTMLTextarea
{
	/**
	 * cDataTextareaWidget: Creates a text area widget
	 *
	 * @param $name Name of the widget
	 * @param $parameters Parameters (see below)
	 *
	 * valid parameters for this control are:
	 * default		Default value for this area
	 *
	 * @return void
	 * @access public
	 */	
	function cDataTextareaWidget ($name, $parameters)
	{
		cHTMLTextarea::cHTMLTextarea($name);
		
		if (array_key_exists("default", $parameters))
		{
			$this->setValue($parameters["default"]);	
		}

	}
}

/**
 * class cDataCodeTextareaWidget
 * cDataCodeTextareaWidget generates a textarea widget for use with the data objects.
 */
class cDataCodeTextareaWidget extends cHTMLTextarea
{
	/**
	 * cDataTextareaWidget: Creates a text area widget
	 * which can be used for entering code
	 *
	 * @param $name Name of the widget
	 * @param $parameters Parameters (see below)
	 *
	 * valid parameters for this control are:
	 * default		Default value for this area
	 * notes		Notes for this area
	 *
	 * @return void
	 * @access public
	 */		
	function cDataCodeTextareaWidget ($name, $parameters)
	{
		cHTMLTextarea::cHTMLTextarea($name);
		
		if (array_key_exists("default", $parameters))
		{
			$this->setValue($parameters["default"]);	
		}
		
		$this->updateAttributes(array("wrap" => "off"));
		$this->setStyle("width: 100%; font-family: monospace;");
		$this->setWidth(100);
		$this->setHeight(20);
		
		if (array_key_exists("notes", $parameters))
		{
			$this->_notes = $parameters["notes"];	
		}
	}
	
	function render ()
	{
		$out = parent::render();
		$out .= $this->_notes;
		
		return ($out);
	}
}

/**
 * class cDataDropdownWidget
 * cDataDropdownWidget generates a dropdown widget for use with the data objects.
 */
class cDataDropdownWidget extends cHTMLSelectElement
{
	/**
	 * cDataDropdownWidget: Creates a dropdown widget
	 * with specific entries
	 *
	 * @param $name Name of the widget
	 * @param $parameters Parameters (see below)
	 *
	 * valid parameters for this control are:
	 * default		string 	Default value which will be selected
	 * choices		array	Values for filling the dropdown
	 *
	 * @return void
	 * @access public
	 */		
	function cDataDropdownWidget ($name, $parameters)
	{
		cHTMLSelectElement::cHTMLSelectElement($name);
		
		$this->autoFill($parameters["choices"]);
		
		if (array_key_exists("default", $parameters))
		{
			$this->setDefault($parameters["default"]);	
		}
	}
}

/**
 * class cDataForeignTableDropdownWidget
 * cDataForeignTableDropdownWidget generates a dropdown widget out of a foreign table.
 */
class cDataForeignTableDropdownWidget extends cHTMLSelectElement
{
	/**
	 * cDataForeignTableDropdownWidget: Creates a dropdown widget
	 * which fetches its entries from a foreign, linked table
	 *
	 * @param $name Name of the widget
	 * @param $parameters Parameters (see below)
	 *
	 * valid parameters for this control are:
	 * foreignClass	string 	Class name of the foreign class
	 * default		string	Default value which will be selected
	 *
	 * @return void
	 * @access public
	 */		
	function cDataForeignTableDropdownWidget ($name, $parameters)
	{
		cHTMLSelectElement::cHTMLSelectElement($name);
		
		$c = new $parameters["foreignClass"];
		$c->query();
		
		while ($i = $c->next())
		{
			$meta = $i->getMetaObject();
			
			if (is_object($meta))
			{
				$data[$i->get($i->primaryKey)] = $meta->getName();
			}	
		}
		
		$this->autoFill($data);
		
		if (array_key_exists("default", $parameters))
		{
			$this->setDefault($parameters["default"]);	
		}

	}
}

/**
 * class cDataCheckboxWidget
 * cDataCheckboxWidget generates a checkbox for use with the dataobjects
 */
class cDataCheckboxWidget extends cHTMLCheckbox
{
	/**
	 * cDataCheckboxWidget: Creates a checkbox widget
	 *
	 * @param $name Name of the widget
	 * @param $parameters Parameters (see below)
	 *
	 * valid parameters for this control are:
	 * title		string 	Title of the checkbox label
	 * default		string	Checked or not checked
	 *
	 * @return void
	 * @access public
	 */	
	function cDataCheckboxWidget ($name, $parameters)
	{
		cHTMLCheckbox::cHTMLCheckbox($name."_stub", "1");
		
		if ($parameters["title"] != "")
		{
			$this->setLabelText($parameters["title"]);
		} else {
			$this->setLabelText(" ");	
		}
		
		$this->setChecked($parameters["default"]);
		
		$this->_hfield = new cHTMLHiddenField($name, $parameters["default"]);
		$this->setEvent("click", "if (this.checked == true) { document.getElementById('".$this->_hfield->getId()."').value = '1'; } else { document.getElementById('".$this->_hfield->getId()."').value = '0'; }");
	}

	function render ()
	{
		$out = $this->_hfield->render();
		$out .= parent::render();
		
		return ($out);
	}
}

/**
 * class cDataMultiTextboxWidget
 * cDataMultiTextboxWidget generates a multi-line textbox widget 

 */
class cDataMultiTextboxWidget extends cHTMLTable
{
	/**
	 * cDataMultiTextboxWidget: Creates a multi-line textbox widget
	 *
	 * @param $name Name of the widget
	 * @param $parameters Parameters (see below)
	 *
	 * valid parameters for this control are:
	 * title		string 	Title of the multi-line textbox widget
	 * default		array	Values (=lines) to fill
	 *
	 * @return void
	 * @access public
	 */		
	function cDataMultiTextboxWidget ($name, $parameters)
	{
		cHTMLTable::cHTMLTable();

		$this->name = $name;
		
		if (array_key_exists("title", $parameters))
		{
			$rows[] = $this->addTitle($parameters["title"]);
		}
				
		if (is_array($parameters["default"]))
		{
			foreach ($parameters["default"] as $i)
			{
				$rows[] = $this->addRow($i);
			}
		}
		

		
		$rows[] = $this->addRow("");
		$this->setPadding(1);
		
		$this->setContent($rows);
		
	}
	
	function addTitle ($title)
	{
		$row = new cHTMLTableRow;
		$data = new cHTMLTableData;
		$data->setColSpan(2);
		$data->setContent($title);
		$row->setContent($data);
		
		return ($row);	
	}
	
	function addRow ($data)
	{
		$row = new cHTMLTableRow;
		$l = new cHTMLTableData;
		$r = new cHTMLTableData;
		
		$l->setVerticalAlignment("middle");
		$r->setVerticalAlignment("middle");
		
		$textbox = new cHTMLTextbox($this->name."[]", $data);
		$r->setContent($textbox);
		
		$clearlink = new cHTMLLink;
		
		$clearimage = new cHTMLImage;
		$clearimage->setSrc("images/actions/clear_right.gif");
		
		$clearlink->setAlt(i18n("Clear contents"));
		$clearimage->setAlt(i18n("Clear contents"));
		
		$clearlink->setContent($clearimage);
		
		$i = $textbox->getId();
		
		$clearlink->setEvent("click", "document.getElementById('$i').value = ''; return false;");
		$clearlink->setLink("#");
		
		$l->setContent($clearlink);
		
		$row->setContent(array($l, $r));
		
		return ($row);
	}
}


?>