<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido UI Classes
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.5.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-05-20
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.ui.php 710 2008-08-21 11:37:00Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.htmlelements.php");

class UI_Left_Top
{
	var $link;
	var $javascripts;

	function UI_Left_Top ()
	{
	}

	function setLink ($link)
	{
		$this->link = $link;
	}

	function setJS ($type, $script)
	{
		$this->javascripts[$type] = $script;
	}

	function render()
	{
		global $sess, $cfg;

		$tpl = new Template;

		$tpl->reset();
		$tpl->set('s', 'SESSID', $sess->id);

		$scripts = "";

		if (is_array($this->javascripts))
		{
			foreach ($this->javascripts as $script)
			{
				$scripts .= '<script language="javascript">'.$script.'</script>';
			}
		}

		if (is_object($this->link))
		{
			$tpl->set('s', 'LINK', $this->link->render() . $this->additional);
		} else {
			$tpl->set('s', 'LINK', '');
		}

		$tpl->set('s', 'JAVASCRIPTS', $scripts);
		$tpl->set('s', 'CAPTION', $this->caption);
		$tpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['generic_left_top']);


	}

	function setAdditionalContent ($content)
	{
		$this->additional = $content;
	}

}

class UI_Menu
{
	var $link;
	var $title;
	var $caption;
	var $javascripts;
	var $type;
	var $image;
	var $alt;
	var $actions;
	var $padding;
	var $imagewidth;
 	var $extra;
 	var $border;
 	var $show;
 	var $bgColor;

	function UI_Menu ()
	{
		$this->padding = 2;
		$this->border = 0;
		$this->rowmark = true;
	}

	function setTitle ($item, $title)
	{
		$this->title[$item] = $title;
	}

	function setRowmark ($rowmark = true)
	{
		$this->rowmark = $rowmark;
	}

	function setImage ($item, $image, $maxwidth = 0)
	{
		$this->image[$item] = $image;
		$this->imagewidth[$item] = $maxwidth;
		$this->show[$item] = $show;
	}

	function setExtra ($item, $extra)
	{
		$this->extra[$item] = $extra;
	}

	function setLink ($item, $link)
	{
		$this->link[$item] = $link;
	}

	function setActions ($item, $key, $action)
	{
		$this->actions[$item][$key] = $action;
	}

	function setPadding ($padding)
	{
		$this->padding = $padding;
	}

	function setBorder ($border)
	{
		$this->border = $border;
	}

	function setBgColor($item, $bgColor)
	{
		$this->bgColor[$item] = $bgColor;

	}

	function render($print = true)
	{
		global $sess, $cfg;

		$tpl = new Template;

		$tpl->reset();
		$tpl->set('s', 'SID', $sess->id);

		$scripts = "";

		if (is_array($this->javascripts))
		{

			foreach ($this->javascripts as $script)
			{
				$scripts .= '<script language="javascript">'.$script.'</script>';
			}
		}

		#echo '<br>Debug (B.A.): ' . $scripts;
		$tpl->set('s', 'JSACTIONS', $scripts);
		$tpl->set('s', 'CELLPADDING', $this->padding);
		$tpl->set('s', 'BORDER', $this->border);
		$tpl->set('s', 'BORDERCOLOR', $cfg['color']['table_border']);


		if (is_array($this->link))
		{
    		foreach ($this->link as $key => $value)
    		{
    			if ($value != NULL)
    			{
    				if ($this->imagewidth[$key] != 0)
    				{
    					$value->setContent('<img border="0" src="'.$this->image[$key].'" width="'.$this->imagewidth[$key].'">');
    					$img = $value->render();
    				}
    				else
    				{
    					$value->setContent('<img border="0" src="'.$this->image[$key].'">');
    					$img = $value->render();
    				}
    				$value->setContent($this->title[$key]);
    				$link = $value->render();
    			} else {
    				$link = $this->title[$key];

    				if ($this->image[$key] != "")
    				{
    					if ($this->imagewidth[$key] != 0)
    					{
    						$img = '<img border="0" src="'.$this->image[$key].'" width="'.$this->imagewidth[$key].'">';
    					} else {
    						$img = '<img border="0" src="'.$this->image[$key].'">';
    					}
    				} else {
    					$img = "&nbsp;";
    				}
    			}

    			if(isset($this->bgColor[$key])) {
    				$bgColor = $this->bgColor[$key];
    			} else {
	        	    $dark = !$dark;
	            	if ($dark) {
	                	$bgColor = $cfg["color"]["table_dark"];
	            	} else {
	    	            $bgColor = $cfg["color"]["table_light"];
	            	}

	                if ($_GET['idworkflow'] == $value) {
        	 		   //$mlist->setExtra($iMenu, 'id="marked" ');
        	 		   $bgColor = $cfg["color"]["table_light_active"];
       				 }

	                if ($this->extra[$key] == 'id="marked" ') {
	                    $bgColor = $cfg["color"]["table_light_active"];
	                }
    			}

        		$tpl->set('d', 'NAME', $link);

      			if ($this->image[$key] == "")
      			{
      			  $tpl->set('d', 'ICON', '');
      			}
            else
            {
              $tpl->set('d', 'ICON', $img);
            }

        		if ($this->extra[$key] != "" || $this->rowmark == true)
        		{
        			$extraadd = "";

        			if ($this->rowmark == true)
        			{
        				$extraadd = 'onmouseover="row.over(this)" onmouseout="row.out(this)" onclick="row.click(this)"';
        				#echo '<br> Debug(B.A): ' . $extraadd;
        			}
        			$tpl->set('d', 'EXTRA', $this->extra[$key] . $extraadd);
        		} else {
        			$tpl->set('d', 'EXTRA', '');
        		}

				$fullactions = "";
        		if (is_array($this->actions[$key]))
        		{

        			$fullactions = '<table border="0"><tr>';

        			foreach ($this->actions[$key] as $key => $singleaction)
        			{
        				$fullactions .= '<td nowrap="nowrap">'.$singleaction.'</td>';
        			}

        			$fullactions .= '</tr></table>';
        		}

        		$tpl->set('d', 'ACTIONS', $fullactions);
        		$tpl->set('d', 'BGCOLOR',  $bgColor);
        		$tpl->next();
    		}

		}
		$rendered = $tpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['generic_menu'],true);

		if ($print == true)
		{
			echo $rendered;
		} else {
			return $rendered;
		}
	}

}

class UI_Table_Form
{
	var $items;
	var $captions;
	var $id;
	var $rownames;
	var $itemType;

	var $formname;
	var $formmethod;
	var $formaction;
	var $formvars;

	var $tableid;
	var $tablebordercolor;

	var $header;
	var $cancelLink;
	var $submitjs;

	var $accesskey;
	var $width;


	function UI_Table_Form ($name, $action = "", $method = "post")
	{
		global $sess, $cfg;

		$this->formname = $name;

		if ($action == "")
		{
			$this->formaction = "main.php";
		} else {
			$this->formaction = $action;
		}

		$this->formmethod = $method;

		$this->tableid = "";
		$this->tablebordercolor = $cfg['color']['table_border'];
		$this->setAccessKey('s');
		$this->custom = array();

		$this->setActionButton("submit", $cfg['path']['contenido_fullhtml']."images/but_ok.gif", i18n("Save changes"), "s");

	}

	function setWidth ($width)
	{
		$this->width = $width;
	}

	function setVar ($name, $value)
	{
		$this->formvars[$name] = $value;
	}

	function add ($caption, $field, $rowname = "", $style = "")
	{
		$n = "";

		if (is_array($field))
		{

			foreach ($field as $value)
			{
        		if (is_object($value) && method_exists($value, "render"))
        		{
        			$n .= $value->render();
        		} else {
        			$n .= $value;
        		}
			}

			$field = $n;
		}
		if (is_object($field) && method_exists($field, "render"))
		{
			$n = $field->render();
			$field = $n;
		}
		if ($field == "")
		{
			$field = "&nbsp;";
		}

		if ($caption == "")
		{
			$caption = "&nbsp;";
		}

		$this->id++;
		$this->items[$this->id] = $field;
		$this->captions[$this->id] = $caption;

		if ($rowname == "")
		{
			$rowname = $this->id;
		}

		$this->rownames[$this->id] = $rowname;

		$this->styles[$this->id] = $style;
	}

	function addCancel ($link)
	{
		$this->cancelLink = $link;
	}

	function addHeader ($header)
	{
		$this->header = $header;
	}

	function addSubHeader ($header)
	{
		$this->id++;
		$this->items[$this->id] = '';
		$this->captions[$this->id] = $header;
		$this->itemType[$this->id] = 'subheader';
	}

	function setSubmitJS ($js)
	{
		$this->submitjs = $js;
	}

	function setAccessKey ($key)
	{
		$this->accessKey = $key;
	}

	function setActionEvent ($id, $event)
	{
		$this->custom[$id]["event"] = $event;
	}

	function setActionButton ($id, $image, $description = "", $accesskey = false, $action = false)
	{
		$this->custom[$id]["image"] = $image;
		$this->custom[$id]["type"] = "actionsetter";
		$this->custom[$id]["action"] = $action;
		$this->custom[$id]["description"] = $description;
		$this->custom[$id]["accesskey"] = $accesskey;
		$this->custom[$id]["event"] = "";
	}

	function setConfirm ($id, $title, $description)
	{
		$this->custom[$id]["confirmtitle"] = $title;
		$this->custom[$id]["confirmdescription"] = $description;
	}

	function unsetActionButton ($id)
	{
		unset($this->custom[$id]);
	}

	function render ($return = true)
	{
		global $sess, $cfg;

		$tpl = new Template;

		$extra = "";

		if ($this->submitjs != "")
		{
			$fextra = 'onsubmit="'.$this->submitjs.'"';
		} else {
			$fextra = "";
		}

		$form  = '<form '.$fextra.' enctype="multipart/form-data" style="margin:0px" name="'.$this->formname.'" method="'.$this->formmethod.'" action="'.$this->formaction.'">'."\n";
		$this->formvars[$sess->name] = $sess->id;

		if (is_array($this->formvars))
		{
			foreach ($this->formvars as $key => $value)
			{
                 $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'">'."\n";
			}
		}

		if (!array_key_exists("action", $this->formvars))
		{
			$form .= '<input type="hidden" name="action" value="">';
		}

		$tpl->set('s', 'FORM', $form);
		$tpl->set('s', 'ID', $this->tableid);
		$tpl->set('s', 'BORDERCOLOR', $this->tablebordercolor);

		if ($this->header != "")
		{
			$header  = '<tr class="text_medium" style="background-color: '.$cfg["color"]["table_header"].';">';
			$header .= '<td class="textg_medium" colspan="2" valign="top" style="border: 0px; border-bottom: 0px;border-top:1px; border-right:1px;border-color: '.$cfg["color"]["table_border"].'; border-style: solid;">'.$this->header.'</td></tr>';
		}

		$tpl->set('s', 'HEADER', $header);

		$dark = false;

		if (is_array($this->items))
		{
			foreach ($this->items as $key => $value)
			{
				if ($this->itemType[$key] == 'subheader')
				{
					$subheader  = '<tr class="text_medium" style="background-color: '.$cfg["color"]["table_header"].';">';
					$subheader .= '<td colspan="2" valign="top" style="border: 0px;border-top: 0px; border-bottom:0px; border-right:1px;border-color: '.$cfg["color"]["table_border"].'; border-style: solid;">'.$this->captions[$key].'</td></tr>';

					$tpl->set('d', 'SUBHEADER', $subheader);
				} else
				{
					$tpl->set('d', 'SUBHEADER', '');
					$tpl->set('d', 'CATNAME', $this->captions[$key]);
					$tpl->set('d', 'CATFIELD', $value);
					$tpl->set('d', 'ROWNAME', $this->rownames[$key]);
					$tpl->set('d', 'STYLES', $this->styles[$key]);
                    $tpl->set('d', 'PADDING_LEFT', '0');

					$dark = !$dark;

					if ($dark)
					{
						$bgColor = $cfg["color"]["table_dark"];
	        }
					else
					{
						$bgColor = $cfg["color"]["table_light"];
	        }

					$tpl->set('d', 'BGCOLOR', $bgColor);
					$tpl->set('d', 'BORDERCOLOR', $this->tablebordercolor);
					$tpl->next();
				}
			}
		}

		$tpl->set('s', 'CONTENIDOPATH',$cfg["path"]["contenido_fullhtml"]);

		if ($this->cancelLink != "")
		{
			$img = '<img src="'.$cfg["path"]["contenido_fullhtml"].'images/but_cancel.gif" border="0">';

			$tpl->set('s', 'CANCELLINK', '<a href="'.$this->cancelLink.'">'.$img.'</a>');
		} else {
			$tpl->set('s', 'CANCELLINK','');
		}

		if ($this->accesskey != "")
		{
			$tpl->set('s', 'KEY', $this->accesskey);
		} else {
			$tpl->set('s', 'KEY', '');
		}

		$tpl->set('s', 'EXTRA', $extra);

		$custombuttons = "";

		foreach ($this->custom as $key => $value)
		{
			if ($value["accesskey"] != "")
			{
				$accesskey = 'accesskey="'.$value["accesskey"].'"';
			} else {
				$accesskey = "";
			}

			$onclick = "";
			if ($value["action"] !== false)
			{

				if ($value["confirmtitle"] != "")
				{
					$action = '[\'';
					$action .= addslashes('document.forms[\''.$this->formname.'\'].elements[\'action\'].value = \''.$value["action"].'\'').'\',\'';
					$action .= addslashes('document.forms[\''.$this->formname.'\'].submit()');
					$action .= '\']';

					$onclick = 'box.confirm(\''.$value["confirmtitle"].'\', \''.$value["confirmdescription"].'\', '.$action.');return false;';
				} else {
					$onclick = 'document.forms[\''.$this->formname.'\'].elements[\'action\'].value = \''.$value["action"].'\';';
				}
			}

			if ($value["event"] != "")
			{
				$onclick .= $value["event"];
			}

			$custombuttons .= '<input style="margin-left: 5px;" title="'.$value["description"].'" alt="'.$value["description"].'" type="image" src="'.$value["image"].'" name="submit" onclick="'.$onclick.'" '.$accesskey.'>';
		}

		$tpl->set('s', 'EXTRABUTTONS', $custombuttons);

		$extra = "";

		if ($this->width != 0)
		{
			$extra .= 'width="'.$this->width.'"';
		}

		$tpl->set('s', 'EXTRAB', $extra);
        $tpl->set('s', 'PADDING_LEFT', '0');
        $tpl->set('s', 'ROWNAME', $this->id);

		$rendered = $tpl->generate($cfg["path"]["contenido"].$cfg['path']['templates'] . $cfg['templates']['generic_table_form'],true);

		if ($return == true)
		{
			return ($rendered);
		} else {
			echo $rendered;
		}
	}
}

class UI_Form
{
	var $items;
	var $content;
	var $id;
	var $rownames;

	var $formname;
	var $formmethod;
	var $formaction;
	var $formvars;
	var $formtarget;
    var $formevent;

	var $tableid;
	var $tablebordercolor;

	var $header;

	function UI_Form ($name, $action = "", $method = "post", $target = "")
	{
		global $sess, $cfg;

		$this->formname = $name;

		if ($action == "")
		{
			$this->formaction = "main.php";
		} else {
			$this->formaction = $action;
		}

		$this->formmethod = $method;

		$this->formtarget = $target;

	}

	function setVar ($name, $value)
	{
		$this->formvars[$name] = $value;
	}

    function setEvent ($event, $jsCall)
	{
		$this->formevent = " on$event=\"$jsCall\"";
	}

	function add ($field, $content = "")
	{
		$this->id++;
		$this->items[$this->id] = $field;
		$this->content[$this->id] = $content;
	}

	function render ($return = true)
	{
		global $sess, $cfg;

		$content = "";

		$tpl = new Template;

		$form  = '<form style="margin:0px" name="'.$this->formname.'" method="'.$this->formmethod.'" action="'.$this->formaction.'" target="'.$this->formtarget.'" '.$this->formevent.'>'."\n";
		$this->formvars[$sess->name] = $sess->id;

		if (is_array($this->formvars))
		{
			foreach ($this->formvars as $key => $value)
			{
                 $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'">'."\n";
			}
		}

		$tpl->set('s', 'FORM', $form);

		if (is_array($this->items))
		{
			foreach ($this->items as $key => $value)
			{
				$content .= $this->content[$key];
			}
		}

		$tpl->set('s', 'CONTENT', $content);

		$rendered = $tpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['generic_form'],true);

		if ($return == true)
		{
			return ($rendered);
		} else {
			echo $rendered;
		}
	}
}

class UI_Page
{
	var $scripts;
	var $content;
	var $margin;

	function UI_Page ()
	{
		$this->margin = 10;
	}

	function setMargin ($margin)
	{
		$this->margin = $margin;
	}

	function addScript ($name, $script)
	{
		$this->scripts[$name] = $script;
	}

	function setReload ()
	{
		$this->scripts["__reload"] =
			'<script type="text/javascript">'.
			"parent.parent.frames['left'].frames['left_bottom'].location.reload();"
			."</script>";
	}

	function setContent ($content)
	{
		$this->content = $content;
	}

	function setMessageBox ()
	{
		global $sess;
		$this->scripts["__msgbox"] =
		   '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>'.
		   '<script type="text/javascript">
            /* Session-ID */
            var sid = "'.$sess->id.'";

            /* Create messageBox
               instance */
            box = new messageBox("", "", "", 0, 0);

           </script>';
	}

	function render ($print = true)
	{
		global $sess, $cfg;

		$tpl = new Template;

		$scripts = "";


		if (is_array($this->scripts))
		{
			foreach ($this->scripts as $key => $value)
			{
				$scripts .= $value;
			}
		}

		$tpl->set('s', 'SCRIPTS', $scripts);
		$tpl->set('s', 'CONTENT', $this->content);
		$tpl->set('s', 'MARGIN', $this->margin);
		$tpl->set('s', 'EXTRA', '');

		$rendered = $tpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['generic_page'],false);

		if ($print == true)
		{
			echo $rendered;
		} else {
			return $rendered;
		}
	}
}

class Link
{
	var $link;
	var $title;
	var $targetarea;
	var $targetframe;
	var $targetaction;
	var $targetarea2;
	var $targetframe2;
	var $targetaction2;
	var $caption;
	var $javascripts;
	var $type;
	var $custom;
	var $content;
	var $attributes;
	var $img_width;
	var $img_height;
	var $img_type;
	var $img_attr;

	function setLink ($link)
	{
		$this->link = $link;
		$this->type = "link";
	}

	function setCLink ($targetarea, $targetframe, $targetaction)
	{
		$this->targetarea = $targetarea;
		$this->targetframe = $targetframe;
		$this->targetaction = $targetaction;
		$this->type = "clink";
	}

	function setMultiLink ($righttoparea, $righttopaction, $rightbottomarea, $rightbottomaction)
	{
		$this->targetarea = $righttoparea;
		$this->targetframe = 3;
		$this->targetaction = $righttopaction;
		$this->targetarea2 = $rightbottomarea;
		$this->targetframe2 = 4;
		$this->targetaction2 = $rightbottomaction;
		$this->type = "multilink";
	}

	function setAlt ($alt)
	{
		$this->alt = $alt;
	}

	function setCustom ($key, $value)
	{
		$this->custom[$key] = $value;
	}

	function setImage ($image)
	{
		$this->images = $image;
	}

	function setJavascript ($js)
	{
		$this->javascripts = $js;
	}

	function setContent ($content)
	{
		$this->content = $content;
	}

	function updateAttributes ($attributes)
	{
		$this->attributes = $attributes;
	}

	function render ()
	{
			global $sess, $cfg;

			if ($this->alt != "")
        	{
        		$alt = 'alt="'.$this->alt.'" title="'.$this->alt.'" ';
        	} else {
        		$alt = " ";
        	}

        	if (is_array($this->custom))
        	{
        		foreach ($this->custom as $key => $value)
        		{
        			$custom .= "&$key=$value";
        		}
        	}

        	if (is_array($this->attributes))
        	{
        		foreach ($this->attributes as $key => $value)
        		{
        			$attributes .= " $key=\"$value\" ";
        		}
        	}

        	switch ($this->targetframe)
        	{
        		case 1: $target = "left_top"; break;
        		case 2: $target = "left_bottom"; break;
        		case 3: $target = "right_top"; break;
        		case 4: $target = "right_bottom"; break;
        		default: $target = "";
        	}

    		switch ($this->type)
    		{
    			case "link":
    				$link =  '<a target="'.$target.'"'.$alt.'href="'.$this->link.'"'.$attributes.'>';
    				break;
    			case "clink":

    				$link = '<a target="'.$target.'"'.$alt.'href="main.php?area='.$this->targetarea.
                                           '&frame='.$this->targetframe.
                                           '&action='.$this->targetaction.$custom."&contenido=".$sess->id.
                                           '"'.$attributes.'>';
                    break;
    			case "multilink":
    				$tmp_mstr = '<a '.$alt.'href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')"'.$attributes.'>';
    				$mstr = sprintf($tmp_mstr, 'right_top',
                                       $sess->url("main.php?area=".$this->targetarea."&frame=".$this->targetframe."&action=".$this->targetaction.$custom),
                                       'right_bottom',
                                       $sess->url("main.php?area=".$this->targetarea2."&frame=".$this->targetframe2."&action=".$this->targetaction2.$custom));
    				$link = $mstr;
    				break;
    		}

			if ($this->images=='') {
				return ($link.$this->content."</a>");
			} else {
				list($this->img_width,$this->img_height,$this->img_type,$this->img_attr) = getimagesize($cfg['path']['contenido'].$this->images);

				return ($link.'<img src="'.$this->images.'" border="0" width="'.$this->img_width.'" height="'.$this->img_height.'"/></a>');
			}
	}
}

class UI_List
{
	var $link;
	var $title;
	var $caption;
	var $javascripts;
	var $type;
	var $image;
	var $alt;
	var $actions;
	var $padding;
	var $imagewidth;
 	var $extra;
 	var $border;
 	var $bgcolor;
 	var $solid;
 	var $width;

	function UI_List ()
	{
		$this->padding = 2;
		$this->border = 0;
	}

	function setWidth ($width)
	{
		$this->width = $width;
	}

	function setCellAlignment ($item, $cell, $alignment)
	{
		$this->cellalignment[$item][$cell] = $alignment;
	}

	function setCellVAlignment ($item, $cell, $alignment)
	{
		$this->cellvalignment[$item][$cell] = $alignment;
	}

	function setBgColor ($item, $color)
	{
		$this->bgcolor[$item] = $color;
	}

	function setCell ($item, $cell, $value)
	{
		$this->cells[$item][$cell] = $value;
		$this->cellalignment[$item][$cell] = "";
	}

    function setCellExtra ($item, $cell, $extra)
	{
		$this->extra[$item][$cell] = $extra;
	}

	function setPadding ($padding)
	{
		$this->padding = $padding;
	}

	function setBorder ($border)
	{
		$this->border = $border;
	}

	function setExtra ($item, $extra)
	{
		$this->extra[$item] = $extra;
	}

	function setSolidBorder ($solid)
	{
		$this->solid = $solid;
	}

	function render($print = false)
	{
		global $sess, $cfg;

		$tpl = new Template;
		$tpl2 = new Template;

		$tpl->reset();
		$tpl->set('s', 'SID', $sess->id);

		$tpl->set('s', 'CELLPADDING', $this->padding);
		$tpl->set('s', 'BORDER', $this->border);
		$tpl->set('s', 'BORDERCOLOR', $cfg['color']['table_border']);

		$colcount = 0;

		if (is_array($this->cells))
		{
			foreach ($this->cells as $row => $cells)
			{
				$thefont='';
				$unne='';
                
				if($colcount == 0)
				{
					$thefont='color: #666666;font-weight: normal;';
				}

				$colcount++;

        	    $dark = !$dark;

            	if ($dark) {
                	$bgColor = $cfg["color"]["table_dark"];
            	} else {
    	            $bgColor = $cfg["color"]["table_light"];
            	}

            	if ($this->bgcolor[$row] != "")
            	{
            		$bgColor = $this->bgcolor[$row];
            	}

            	$content = "";
            	$count = 0;

            	foreach ($cells as $key => $value)
            	{
                    $thefontDispl = $thefont.$this->extra[$row][$key];
            		$count++;
            		$tpl2->reset();

								if ($this->solid)
            		{
									if ($count < count($cells))
										{
											if ($colcount < count($this->cells))
											{
												$tpl2->set('s', 'EXTRA', $thefontDispl.'border: 0px; border-right: 1px; border-color: #B3B3B3; border-style: solid;');
											} else {
												$tpl2->set('s', 'EXTRA', $thefontDispl.'border: 0px; border-right: 1px; border-color: #B3B3B3; border-style: solid;');
											}
										} else {
											if ($colcount < count($this->cells))
											{
												$tpl2->set('s', 'EXTRA', $thefontDispl.'border: 0px;border-color: #B3B3B3; border-style: solid;');
											} else {
												$tpl2->set('s', 'EXTRA', $thefontDispl);
											}
										}
            		}

								if($colcount > 0)
								{
									$tpl2->set('s', 'BORDERS', ';border-bottom:1px solid #B3B3B3;');
								}

            		if ($this->cellalignment[$row][$key] != "")
            		{
            			$tpl2->set('s', 'ALIGN', $this->cellalignment[$row][$key]);
            		} else {
            			$tpl2->set('s', 'ALIGN', 'left');
            		}

            		if ($this->cellvalignment[$row][$key] != "")
            		{
            			$tpl2->set('s', 'VALIGN', $this->cellvalignment[$row][$key]);
            		} else {
            			$tpl2->set('s', 'VALIGN', 'top');
            		}

            		$tpl2->set('s', 'CONTENT', $value);
            		$content .= $tpl2->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['generic_list_row'],true);
            	}

            	$tpl->set('d', 'ROWS', $content);
        			$tpl->set('d', 'BGCOLOR',  $bgColor);
        			$tpl->next();
    		}
		}

		if ($this->width)
		{
			$tpl->set('s', 'EXTRA', 'width: '.$this->width.';');
		}
		$rendered = $tpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['generic_list'],true);

		if ($print == true)
		{
			echo $rendered;
		} else {
			return $rendered;
		}
	}
}

/**
 * Class ScrollableList
 * Class for scrollable backend lists
 */
class cScrollList
{
	/**
     * Data container
     * @var array
     */
	var $data = Array();

	/**
     * Header container
     * @var array
     */
	var $header = Array();

	/**
     * Number of records displayed per page
     * @var string
     */
	var $resultsPerPage;

	/**
     * Start page
     * @var string
     */
	var $listStart;

	/**
     * sortable flag
     * @var string
     */
	var $sortable;

	/**
     * sortlink
     * @var string
     */
	var $sortlink;

	/**
     * Table item
     *
     */
	var $objTable;

	/**
     * Header row
     *
     */
	var $objHeaderRow;

	/**
     * Header item
     *
     */
	var $objHeaderItem;

	/**
     * Header item
     *
     */
	var $objRow;

	/**
     * Header item
     *
     */
	var $objItem;

	/* TODO: Shouldn't $area and $frame be parameters instead of global variables? */
	/**
     * Creates a new FrontendList object.
 	 *
	 * @param $defaultstyle boolean use the default style for object initializing?
     */
	function cScrollList ($defaultstyle = true, $action = "")
	{
		global $cfg, $area, $frame;

		$this->resultsPerPage = 0;
		$this->listStart = 1;
		$this->sortable = false;

		$this->objTable = new cHTMLTable;
		if ($defaultstyle == true)
		{
			$this->objTable->setStyle('border-collapse:collapse;border: 1px; border-style: solid; border-top:0px;border-color: '.$cfg["color"]["table_border"].';');
			$this->objTable->updateAttributes(array("cellspacing" => 0, "cellpadding" => 2));
		}

		$this->objHeaderRow = new cHTMLTableRow;
		if ($defaultstyle == true)
		{
			$this->objHeaderRow->setClass("text_medium");
			$this->objHeaderRow->setStyle("background-color: #E2E2E2;white-space:nowrap;");
		}


		$this->objHeaderItem = new cHTMLTableHead;
		if ($defaultstyle == true)
		{
			$this->objHeaderItem->setClass("textg_medium");
			$this->objHeaderItem->setStyle('white-space:nowrap; border: 1px; border-style: solid;border-bottom: 0px;border-color: '.$cfg["color"]["table_border"].';');
			$this->objHeaderItem->updateAttributes(array("align" => "left"));
		}

		$this->objRow = new cHTMLTableRow;
		if ($defaultstyle == true)
		{
			$this->objRow->setClass("text_medium");
		}

		$this->objItem = new cHTMLTableData;
		if ($defaultstyle == true)
		{
			$this->objItem->setStyle('white-space:nowrap; border: 1px; border-style: solid;border-top:0px;border-color: '.$cfg["color"]["table_border"].';');
		}


		$this->sortlink = new cHTMLLink;
		$this->sortlink->setStyle("color: #666666;");
		$this->sortlink->setCLink($area, $frame, $action);
	}

	/**
     * Sets the sortable flag for a specific row.
 	 *
	 * $obj->setSortable(true);
	 *
     * @param $sortable boolean true or false
     */
    function setSortable ($key, $sortable)
    {
    	$this->sortable[$key] = $sortable;
    }

	/**
     * Sets the custom parameters for sortable links
 	 *
	 * $obj->setCustom($key, $custom);
	 *
     * @param $key Custom entry key
     * @param $custom Custom entry value
     */
    function setCustom ($key, $custom)
    {
    	$this->sortlink->setCustom($key, $custom);
    }

	/**
     * Is called when a new row is rendered
 	 *
     * @param $row The current row which is being rendered
     */
	function onRenderRow ($row)
	{
		global $cfg;

		if ($row % 2)
		{
			$col = $cfg["color"]["table_dark"];
		} else {
			$col = $cfg["color"]["table_light"];
		}

		$this->objRow->setStyle("white-space:nowrap; background-color: $col;");
	}

	/**
     * Is called when a new column is rendered
 	 *
     * @param $row The current column which is being rendered
     */
	function onRenderColumn ($column)
	{
	}

	/**
     * Sets header data.
 	 *
	 * Note: This function eats as many parameters as you specify.
	 *
	 * Example:
	 * $obj->setHeader("foo", "bar");
	 *
	 * Make sure that the amount of parameters stays the same for all
	 * setData calls in a single object.
	 *
     * @param $index	Numeric index
	 * @param ...	Additional parameters (data)
     */
	function setHeader ()
	{
		$numargs = func_num_args();

		for ($i=0;$i<$numargs;$i++)
		{
			$this->header[$i] = func_get_arg($i);
		}
	}

	/**
     * Sets data.
 	 *
	 * Note: This function eats as many parameters as you specify.
	 *
	 * Example:
	 * $obj->setData(0, "foo", "bar");
	 *
	 * Make sure that the amount of parameters stays the same for all
	 * setData calls in a single object. Also make sure that your index
	 * starts from 0 and ends with the actual number - 1.
	 *
     * @param $index	Numeric index
	 * @param ...	Additional parameters (data)
     */
	function setData ($index)
	{
		$numargs = func_num_args();

		for ($i=1;$i<$numargs;$i++)
		{
			$this->data[$index][$i] = func_get_arg($i);
		}
	}

	/**
     * Sets hidden data.
 	 *
	 * Note: This function eats as many parameters as you specify.
	 *
	 * Example:
	 * $obj->setHiddenData(0, "foo", "bar");
	 *
	 * Make sure that the amount of parameters stays the same for all
	 * setData calls in a single object. Also make sure that your index
	 * starts from 0 and ends with the actual number - 1.
	 *
     * @param $index	Numeric index
	 * @param ...	Additional parameters (data)
     */
	function setHiddenData ($index)
	{
		$numargs = func_num_args();

		for ($i=1;$i<$numargs;$i++)
		{
			$this->data[$index]["hiddendata"][$i] = func_get_arg($i);
		}
	}

	/**
     * Sets the number of records per page.
	 *
     * @param $numresults	Amount of records per page
     */
	function setResultsPerPage ($numresults)
	{
		$this->resultsPerPage = $numresults;
	}

	/**
     * Sets the starting page number.
	 *
     * @param $startpage	Page number on which the list display starts
     */
	function setListStart ($startpage)
	{
		$this->listStart = $startpage;
	}

	/**
     * Returns the current page.
	 *
     * @param $none
	 * @returns Current page number
     */
	function getCurrentPage ()
	{
		if ($this->resultsPerPage == 0)
		{
			return 1;
		}

		return ($this->listStart);
	}

	/**
     * Returns the amount of pages.
	 *
     * @param $none
	 * @returns Amount of pages
     */
	function getNumPages ()
	{
		return (ceil(count($this->data) / $this->resultsPerPage));
	}

	/**
     * Sorts the list by a given field and a given order.
	 *
     * @param $field	Field index
	 * @param $order	Sort order (see php's sort documentation)
     */
	function sort ($field, $order)
	{
		if ($order == "")
		{
			$order = SORT_ASC;
		}

		if ($order == "ASC")
		{
			$order = SORT_ASC;
		}

		if ($order == "DESC")
		{
			$order = SORT_DESC;
		}

		$this->sortkey = $field;
		$this->sortmode = $order;

		$field = $field + 1;
		$this->data = array_csort($this->data, "$field", $order);

	}

	/**
     * Field converting facility.
	 * Needs to be overridden in the child class to work properbly.
	 *
     * @param $field	Field index
	 * @param $value 	Field value
     */
	function convert ($field, $value, $hiddendata)
	{
		return $value;
	}

	/**
     * Outputs or optionally returns
	 *
     * @param $return	If true, returns the list
     */
	function render ($return = true)
	{
		global $cfg;

		$currentpage = $this->getCurrentPage();

		$itemstart = (($currentpage-1)*$this->resultsPerPage)+1;

		$headeroutput = "";
		$output = "";

		/* Render header */
		foreach ($this->header as $key => $value)
		{
			if (is_array($this->sortable))
			{
				if (array_key_exists($key, $this->sortable) && $this->sortable[$key] == true)
				{
					$this->sortlink->setContent($value);
					$this->sortlink->setCustom("sortby", $key);

					if ($this->sortkey == $key && $this->sortmode == SORT_ASC)
					{
						$this->sortlink->setCustom("sortmode", "DESC");
					} else {
						$this->sortlink->setCustom("sortmode", "ASC");
					}

					$this->objHeaderItem->setContent($this->sortlink->render());
					$headeroutput .= $this->objHeaderItem->render();
				} else {
    				$this->objHeaderItem->setContent($value);
    				$headeroutput .= $this->objHeaderItem->render();
				}
			} else {
				$this->objHeaderItem->setContent($value);
				$headeroutput .= $this->objHeaderItem->render();
			}
		}

		$this->objHeaderRow->setContent($headeroutput);

		$headeroutput = $this->objHeaderRow->render();

		if ($this->resultsPerPage == 0)
		{
			$itemend = count($this->data) - ($itemstart-1);
		} else {
			$itemend = $currentpage*$this->resultsPerPage;
		}

		if ($itemend > count($this->data))
		{
			$itemend = count($this->data);
		}

		for ($i=$itemstart;$i<$itemend+1;$i++)
		{
			$items = "";

			$this->onRenderRow($i);

			foreach ($this->data[$i-1] as $key => $value)
			{
				$this->onRenderColumn($key);

				if ($key != "hiddendata")
				{
					$hiddendata = $this->data[$i-1]["hiddendata"];

					$this->objItem->setContent($this->convert($key, $value, $hiddendata));
					$items .= $this->objItem->render();
				}
			}

			$this->objRow->setContent($items);
			$items = "";

			$output .= $this->objRow->render();

		}

		$this->objTable->setContent($headeroutput.$output);

		$output = stripslashes($this->objTable->render());

		if ($return == true)
		{
			return $output;
		} else {
			echo $output;
		}
	}
}
?>