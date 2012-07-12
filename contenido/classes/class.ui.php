<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO UI Classes
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.5.3
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *  created 2003-05-20
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * @deprecated This class shouldn't be used anymore. Please use templates instead.
 */
class UI_Left_Top
{
    var $link;
    var $javascripts;

    function UI_Left_Top ()
    {
        cDeprecated("This class shouldn't be used anymore. Please use templates instead.");
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

/**
 *
 * @deprecated This class was replaced by cGuiPage. Please use it instead.
 */
class UI_Page
{
    var $scripts;
    var $content;
    var $margin;

    function UI_Page ()
    {
        cDeprecated("This class was replaced by cGuiPage. Please use that instead.");
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

        $colcount = 0;

        if (is_array($this->cells))
        {
            foreach ($this->cells as $row => $cells)
            {
                $thefont='';
                $unne='';

                $colcount++;

                $content = "";
                $count = 0;

                foreach ($cells as $key => $value)
                {
                    $count++;
                    $tpl2->reset();

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
                    if($colcount == 1) {
                        $content .= $tpl2->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['generic_list_head'],true);
                    } else {
                        $content .= $tpl2->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['generic_list_row'],true);
                    }
                }

                $tpl->set('d', 'ROWS', $content);
                $tpl->next();
            }
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
?>