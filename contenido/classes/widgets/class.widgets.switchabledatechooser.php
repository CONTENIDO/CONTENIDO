<?php
cInclude("classes", "widgets/class.widgets.datechooser.php");

class cSwitchableDateChooser extends cDateChooser
{
	var $_oCheckBox;
	var $_bReadOnly;
	var $_bDisabled;
	
	function cSwitchableDateChooser ($name, $initValue = false)
	{
		parent::cDateChooser($name, $initValue);
		
		$this->_oCheckBox = new cHTMLCheckbox($this->getID()."_check", "true");
		$this->_oCheckBox->setLabelText("");
		
		$this->_oCheckBox->setEvent("click", 'document.getElementById("'.$this->getId().'").disabled = !this.checked; var jstyle = document.getElementById("'.$this->getId().'"); if (this.checked) { jstyle.className = "textbox"; if (x_oldvalue_'.$this->getID().') { jstyle.value = x_oldvalue_'.$this->getID().';} document.getElementById("'.$this->_oImage->getId().'").style.visibility = "";} else { jstyle.className = "textbox_readonly"; x_oldvalue_'.$this->getID().' = jstyle.value; jstyle.value = ""; document.getElementById("'.$this->_oImage->getId().'").style.visibility = "hidden";}');
		
		$this->enable();
	}
	
	function disable ()
	{
		$this->_bDisabled = true;
		$this->setDisabled(true);
		$this->_oCheckBox->setChecked(false);
		$this->setClass("textbox_readonly");
		$this->_oImage->setStyle("margin-left: 2px; cursor: pointer; visibility: hidden;");
	}
	
	function enable ()
	{
		$this->_bDisabled = false;
		$this->setDisabled(false);
		$this->_oCheckBox->setChecked(true);
		$this->setClass("textbox");
	}
	
	function render ()
	{
		$sRender = parent::render();
		
		$oAlignmentTable = new cHTMLAlignmentTable($this->_oCheckBox->toHtml(false), $sRender);
		
		if ($this->_bDisabled)
		{
			$addscript ='document.getElementById("'.$this->getId().'").value = "";';
		}
		return  $oAlignmentTable->render() . '<script language="JavaScript">'.$addscript.'x_oldvalue_'.$this->getID().' = "'.$this->_oDate->render().'";</script>';
	}
}