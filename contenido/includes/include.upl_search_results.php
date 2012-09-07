<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * File Manager Search Engine Reulsts
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.9.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-12-29
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.upl_search_results.php 722 2008-08-25 09:17:49Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");
cInclude("includes", "api/functions.frontend.list.php");
cInclude("includes", "functions.upl.php");
cInclude("classes", "class.properties.php");

$appendparameters = $_REQUEST["appendparameters"];

class UploadList extends FrontendList
{
	var $dark;
	var $size;
	var $pathdata;

	function convert($field, $data)
	{
		global $cfg, $sess, $client, $cfgClient, $appendparameters;

		if ($field == 6)
		{
			if ($data == "")
			{
				return i18n("None");
			}
		}
		if ($field == 5)
		{
			return human_readable_size($data);
		}

		if ($field == 4)
		{
			if ($data == "")
			{
				return "&nbsp;";
			} else
			{
				return $data;
			}
		}

		if ($field == 3)
		{
			$vpath = str_replace($cfgClient[$client]["upl"]["path"], "", $this->pathdata);
			$slashpos = strrpos($vpath, "/");
			if ($slashpos === false)
			{
				$file = $vpath;
			} else
			{
				$path = substr($vpath, 0, $slashpos +1);
				$file = substr($vpath, $slashpos +1);
			}

			if ($appendparameters == "imagebrowser" || $appendparameters == "filebrowser")
			{
				$mstr = '<a href="javascript://" onclick="javascript:parent.parent.frames[\'left\'].frames[\'left_top\'].document.getElementById(\'selectedfile\').value= \''.$cfgClient[$client]["upl"]["frontendpath"].$path.$data.'\'; window.returnValue=\''.$cfgClient[$client]["upl"]["frontendpath"].$path.$data.'\'; window.close();">'.$data.'</a>';
			} else
			{
				$markLeftPane = "parent.parent.frames['left'].frames['left_bottom'].upl.click(parent.parent.frames['left'].frames['left_bottom'].document.getElementById('$path'));";

				$tmp_mstr = '<a onmouseover="this.style.cursor=\'pointer\'" href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\');'.$markLeftPane.'">%s</a>';
				$mstr = sprintf($tmp_mstr, 'right_bottom', $sess->url("main.php?area=upl_edit&frame=4&path=$path&file=$file"), 'right_top', $sess->url("main.php?area=upl&frame=3&path=$path&file=$file"), $data);
			}
			return $mstr;
		}

		if ($field == 2)
		{        
			$this->pathdata = $data;

			/* If this file is an image, try to open */
			switch (getFileExtension($data))
			{
				case "png" :
				case "psd" :
				case "gif" :
				case "tiff" :
				case "bmp" :
				case "jpeg" :
				case "jpg" :
				case "bmp" :
				case "iff" :
				case "xbm" :
				case "wbmp" :
                            $sCacheThumbnail = uplGetThumbnail($data, 150);
                            $sCacheName = substr($sCacheThumbnail, strrpos($sCacheThumbnail, "/")+1, strlen($sCacheThumbnail)-(strrchr($sCacheThumbnail, '/')+1));
                            $sFullPath = $cfgClient[$client]['path']['frontend'].'cache/'.$sCacheName;
                            if (file_exists($sFullPath)) {    
                                $aDimensions = getimagesize($sFullPath);
                                $iWidth = $aDimensions[0];
                                $iHeight = $aDimensions[1];
                            } else {
                                $iWidth = 0;
                                $iHeight = 0;
                            }
                
        					if (is_dbfs($data))
        					{
									$retValue = 
									'<a href="JavaScript:iZoom(\''.$sess->url($cfgClient[$client]["path"]["htmlpath"]."dbfs.php?file=".$data).'\');">
										<img class="hover" name="smallImage" src="'.$sCacheThumbnail.'">
										<img class="preview" name="prevImage" src="'.$sCacheThumbnail.'">
									</a>';
									return $retValue; 
        					} else {
									$retValue = 
										'<a href="JavaScript:iZoom(\''.$cfgClient[$client]["path"]["htmlpath"].$cfgClient[$client]["upload"].$data.'\');">
											<img class="hover" name="smallImage"  onMouseOver="correctPosition(this, '.$iWidth.', '.$iHeight.');" onmouseout="if (typeof(previewHideIe6) == \'function\') {previewHideIe6(this)}" src="'.$sCacheThumbnail.'">
											<img class="preview" name="prevImage" src="'.$sCacheThumbnail.'">
										</a>';
									$retValue .= '<a href="JavaScript:iZoom(\''.$cfgClient[$client]["path"]["htmlpath"].$cfgClient[$client]["upload"].$data.'\');"><img class="preview" name="prevImage" src="'.$sCacheThumbnail.'"></a>';
									return $retValue;
        					}
							break;
				default:
                            $sCacheThumbnail = uplGetThumbnail($data, 150);
                            return '<img class="hover_none" name="smallImage" src="'.$sCacheThumbnail.'">';
			}
		}

		if ($field == 1)
		{
			if ($this->dark)
			{
				$data = $cfg["color"]["table_dark"];
			} else
			{
				$data = $cfg["color"]["table_light"];
			}
			$this->dark = !$this->dark;
		}

		return $data;
	}
}

function uplRender($searchfor, $sortby, $sortmode, $startpage = 1, $thumbnailmode)
{
	global $cfg, $client, $cfgClient, $area, $frame, $sess, $appendparameters;

	if ($sortby == "")
	{
		$sortby = 7;
		$sortmode = "DESC";
	}

	if ($startpage == "")
	{
		$startpage = 1;
	}

	$thisfile = $sess->url("main.php?idarea=$area&frame=$frame&appendparameters=$appendparameters&searchfor=$searchfor&thumbnailmode=$thumbnailmode");
	$scrollthisfile = $thisfile."&sortmode=$sortmode&sortby=$sortby";

	if ($sortby == 3 && $sortmode == "DESC")
	{
		$fnsort = '<a href="'.$thisfile.'&sortby=3&sortmode=ASC&startpage='.$startpage.'">'.i18n("Filename / Description").'<img src="images/sort_down.gif" border="0"></a>';
	} else
	{
		if ($sortby == 3)
		{
			$fnsort = '<a href="'.$thisfile.'&sortby=3&sortmode=DESC&startpage='.$startpage.'">'.i18n("Filename / Description").'<img src="images/sort_up.gif" border="0"></a>';
		} else
		{
			$fnsort = '<a href="'.$thisfile.'&sortby=3&sortmode=ASC&startpage='.$startpage.'">'.i18n("Filename / Description").'</a>';
		}
	}

	if ($sortby == 4 && $sortmode == "DESC")
	{
		$pathsort = '<a href="'.$thisfile.'&sortby=4&sortmode=ASC&startpage='.$startpage.'">'.i18n("Path").'<img src="images/sort_down.gif" border="0"></a>';
	} else
	{
		if ($sortby == 4)
		{
			$pathsort = '<a href="'.$thisfile.'&sortby=4&sortmode=DESC&startpage='.$startpage.'">'.i18n("Path").'<img src="images/sort_up.gif" border="0"></a>';
		} else
		{
			$pathsort = '<a href="'.$thisfile.'&sortby=4&sortmode=ASC&startpage='.$startpage.'">'.i18n("Path")."</a>";
		}
	}

	if ($sortby == 5 && $sortmode == "DESC")
	{
		$sizesort = '<a href="'.$thisfile.'&sortby=5&sortmode=ASC&startpage='.$startpage.'">'.i18n("Size").'<img src="images/sort_down.gif" border="0"></a>';
	} else
	{
		if ($sortby == 5)
		{
			$sizesort = '<a href="'.$thisfile.'&sortby=5&sortmode=DESC&startpage='.$startpage.'">'.i18n("Size").'<img src="images/sort_up.gif" border="0"></a>';
		} else
		{
			$sizesort = '<a href="'.$thisfile.'&sortby=5&sortmode=ASC&startpage='.$startpage.'">'.i18n("Size")."</a>";
		}
	}

	if ($sortby == 6 && $sortmode == "DESC")
	{
		$typesort = '<a href="'.$thisfile.'&sortby=6&sortmode=ASC&startpage='.$startpage.'">'.i18n("Type").'<img src="images/sort_down.gif" border="0"></a>';
	} else
	{
		if ($sortby == 6)
		{
			$typesort = '<a href="'.$thisfile.'&sortby=6&sortmode=DESC&startpage='.$startpage.'">'.i18n("Type").'<img src="images/sort_up.gif" border="0"></a>';
		} else
		{
			$typesort = '<a href="'.$thisfile.'&sortby=6&sortmode=ASC&startpage='.$startpage.'">'.i18n("Type")."</a>";
		}
	}

	if ($sortby == 7 && $sortmode == "DESC")
	{
		$srelevance = '<a href="'.$thisfile.'&sortby=7&sortmode=ASC&startpage='.$startpage.'">'.i18n("Relevance").'<img src="images/sort_down.gif" border="0"></a>';
	} else
	{
		if ($sortby == 7)
		{
			$srelevance = '<a href="'.$thisfile.'&sortby=7&sortmode=DESC&startpage='.$startpage.'">'.i18n("Relevance").'<img src="images/sort_up.gif" border="0"></a>';
		} else
		{
			$srelevance = '<a href="'.$thisfile.'&sortby=7&sortmode=ASC&startpage='.$startpage.'">'.i18n("Relevance")."</a>";
		}
	}
    
    $sToolsRow = '<tr class="textg_medium">
                        <td colspan="6" style="border:1px; border-color: #B3B3B3; height:20px; line-height:20px; vertical-align:middle; border-style: solid; background-color: #E2E2E2; padding-left:5px;" id="cat_navbar">
                            <div style="float:right; heigth:20px; line-height:20px; vertical-align:middle; width:300px; padding:0px 5px; text-align:right;">'.i18n("Searched for:")." ".$searchfor.'</div>
                            <div style="clear:both;"></div>
                        </td>
                    </tr>';

	/* List wraps */

    $sSpacedRow = '<tr height="10">
                    <td colspan="6"></td>
                   </tr>';
    
    $pagerwrap = '<tr class="textg_medium">
                    <td colspan="6" style="border:1px; border-color: #B3B3B3; height:20px; line-height:20px; vertical-align:middle; border-style: solid; background-color: #E2E2E2; padding-left:5px;" id="cat_navbar">
                        <div style="float:right; heigth:20px; line-height:20px; vertical-align:middle; width:100px; padding:0px 5px; text-align:right;">-C-SCROLLRIGHT-</div>
                        <div style="float:right; heigth:20px; line-height:20px; vertical-align:middle; width:100px; padding:0px 5px; text-align:right;">-C-PAGE-</div>
                        <div style="float:right; heigth:20px; line-height:20px; vertical-align:middle; width:100px; padding:0px 5px; text-align:right;">-C-SCROLLLEFT-</div>
                        <span style="margin-right:10px; line-height:20px; vertical-align:middle;">'.i18n("Files per Page").'</span> -C-FILESPERPAGE-
                        <div style="clear:both;"></div>
                    </td>
                </tr>';
                
	$startwrap = 
	        '<table cellspacing="0" cellpadding="2" border="0" class="hoverbox">
             <input type="hidden" name="thumbnailmode" value="-C-THUMBNAILMODE-"> 
                '.$pagerwrap.$sSpacedRow.$sToolsRow.$sSpacedRow.'
                <tr bgcolor="#E2E2E2" style="border-color:#B3B3B3; border-style: solid;border-top: 1px;">
                    <td align="left" valign="top" class="textg_medium" style="border: 1px; border-color: #B3B3B3; border-style: solid; border-bottom:0px;white-space:nowrap;" nowrap="nowrap">'.i18n("Preview").'</td>
                    <td align="left" valign="top" class="textg_medium" style="border: 0px; border-top: 1px; border-right: 1px; border-bottom: 0px; border-color: #B3B3B3; border-style: solid; white-space:nowrap;" nowrap="nowrap">'.$fnsort.'</td>
    		            <td align="left" valign="top" class="textg_medium" style="border: 0px; border-top: 1px; border-right: 1px; border-bottom: 0px; border-color: #B3B3B3; border-style: solid; white-space:nowrap;" nowrap="nowrap">'.$pathsort.'</td>
                    <td align="left" valign="top" class="textg_medium" style="border: 0px; border-top: 1px; border-right: 1px; border-bottom: 0px; border-color: #B3B3B3; border-style: solid; white-space:nowrap;" nowrap="nowrap">'.$sizesort.'</td>
                    <td align="left" valign="top" class="textg_medium" style="border: 0px; border-top: 1px; border-right: 1px; border-bottom: 0px; border-color: #B3B3B3; border-style: solid; white-space:nowrap;" nowrap="nowrap">'.$typesort.'</td>
                    <td align="left" valign="top" class="textg_medium" style="border: 0px; border-top: 1px; border-right: 1px; border-bottom: 0px; border-color: #B3B3B3; border-style: solid; white-space:nowrap;" nowrap="nowrap">'.$srelevance.'</td>
                </tr>';
	$itemwrap = '
	          <tr bgcolor="%s">
              <td align="center" valign="top" class="text_medium" style="border: 1px; border-top: 0px; border-color: #B3B3B3; border-style: solid; white-space:nowrap;" nowrap="nowrap">%s</td>
              <td align="left" valign="top" class="text_medium" style="border: 0px; border-right: 1px; border-bottom: 1px; border-color: #B3B3B3; border-style: solid; white-space:nowrap;" width="300" nowrap="nowrap">%s</td>
              <td align="left" valign="top" class="text_medium" style="border: 0px; border-right: 1px; border-bottom: 1px; border-color: #B3B3B3; border-style: solid; white-space:nowrap;" width="60" nowrap="nowrap">%s</td>
	            <td align="left" valign="top" class="text_medium" style="border: 0px; border-right: 1px; border-bottom: 1px; border-color: #B3B3B3; border-style: solid; white-space:nowrap;" width="60" nowrap="nowrap">%s</td>
              <td align="left" valign="top" class="text_medium" style="border: 0px; border-right: 1px; border-bottom: 1px; border-color: #B3B3B3; border-style: solid; white-space:nowrap;" width="60" nowrap="nowrap">%s</td>
              <td align="left" valign="top" class="text_medium" style="border: 0px; border-right: 1px; border-bottom: 1px; border-color: #B3B3B3; border-style: solid; white-space:nowrap;" width="60" nowrap="nowrap">%s</td>
            </tr>';
	$endwrap = $sSpacedRow.$sToolsRow.$sSpacedRow.$pagerwrap.'</table>';

	/* Object initializing */
	$page = new UI_Page;
	$list2 = new UploadList($startwrap, $endwrap, $itemwrap);

	$uploads = new UploadCollection;

	/* Fetch data */
	$files = uplSearch($searchfor);

    $user = new User;
    $user->loadUserByUserID($auth->auth["uid"]);
    
    if ($thumbnailmode == '') 
	{
		$current_mode = $user->getUserProperty('upload_folder_thumbnailmode', md5('search_results_num_per_page'));
		if ($current_mode != '') 
		{
			$thumbnailmode = $current_mode;
		} 
		else 
		{
			$thumbnailmode = getEffectiveSetting('backend','thumbnailmode',100);
		}
	}
    
	switch ($thumbnailmode)
	{
		case 25: $numpics = 25; break;
		case 50: $numpics = 50; break;
		case 100:$numpics = 100; break;
		case 200:$numpics = 200; break;
		default: $thumbnailmode = 100;
				 $numpics = 15;
				 break;	
	}
    
    $user->setUserProperty('upload_folder_thumbnailmode', md5('search_results_num_per_page'), $thumbnailmode);

	$list2->setResultsPerPage($numpics);

	$list2->size = $thumbnailmode;

	$rownum = 0;
	if (!is_array($files))
	{
		$files = array ();
	}

	arsort($files, SORT_NUMERIC);

	$count = 0;
	$properties = new PropertyCollection;

	foreach ($files as $file => $rating)
	{

		$slashpos = strrpos($file, "/");

		if ($slashpos === false)
		{
			$myfilename = $file;
			$mydirname = "";
		} else
		{
			$myfilename = substr($file, $slashpos +1);
			$mydirname = substr($file, 0, $slashpos +1);
		}
		$path = $mydirname;

		$filename = $myfilename;
		$dirname = $cfgClient[$client]["upl"]["path"].$mydirname;

		$uploads->select("idclient = '$client' AND dirname = '$mydirname' AND filename = '$filename'");

		if ($item = $uploads->next())
		{
			$filesize = $item->get("size");

			if ($filesize == 0)
			{
				if (file_exists($dirname.$filename))
				{
					$filesize = filesize($dirname.$filename);
				}

			}

			$description = $item->get("description");
		} else
		{
			if (file_exists($dirname.$filename))
			{
				$filesize = filesize($dirname.$filename);
			}
		}

		$dark = !$dark;

		$count ++;

		$medianame = $properties->getValue("upload", $mydirname.$filename, "file", "medianame");
		$medianotes = $properties->getValue("upload", $mydirname.$filename, "file", "medianotes");

		$showfilename = $filename;
		$bgColor = false;
		
		$list2->setData($rownum, $bgColor, $mydirname.$filename, $showfilename, $mydirname, $filesize, getFileExtension($filename), $rating / 10);

		$rownum ++;
	}

	if ($rownum == 0)
	{
		$page->setContent(i18n("No files found"));
		$page->render();
		return;
	}

	if ($sortmode == "ASC")
	{
		$list2->sort($sortby, SORT_ASC);
	} else
	{
		$list2->sort($sortby, SORT_DESC);
	}

	if ($startpage < 1)
	{
		$startpage = 1;
	}

	if ($startpage > $list2->getNumPages())
	{
		$startpage = $list2->getNumPages();
	}

	$list2->setListStart($startpage);

	/* Create scroller */
	if ($list2->getCurrentPage() > 1)
	{
		$prevpage = '<a href="'.$scrollthisfile.'&startpage='. ($list2->getCurrentPage() - 1).'" class="invert_hover">'.i18n("Previous Page").'</a>';
	} else {
        $nextpage = '&nbsp;';
    }

	if ($list2->getCurrentPage() < $list2->getNumPages())
	{
		$nextpage = '<a href="'.$scrollthisfile.'&startpage='. ($list2->getCurrentPage() + 1).'" class="invert_hover">'.i18n("Next Page").'</a>';
	} else {
        $nextpage = '&nbsp;';
    }

	if ($list2->getNumPages()>1) {
    	$num_pages = $list2->getNumPages();
    	
    	$paging_form.="<script type=\"text/javascript\">
    	    function jumpToPage(select) {
    			var pagenumber = select.selectedIndex + 1;
    			url = '".$sess->url("main.php?idarea=$area&frame=$frame&appendparameters=$appendparameters&searchfor=$searchfor&thumbnailmode=$thumbnailmode")."';
    			document.location.href = url + '&startpage=' + pagenumber;
    		}
        </script>";
    	$paging_form.="<select name=\"start_page\" class=\"text_medium\" onChange=\"jumpToPage(this);\">";
    	for ($i=1;$i<=$num_pages;$i++) {
    		if ($i==$startpage) {
    			$selected = " selected";
    		} else {
    			$selected = "";
    		}
    		$paging_form.="<option value=\"$i\"$selected>$i</option>";
    	}	

    	$paging_form.="</select>";
    } else {
    	$paging_form="1";
    }
    
	$curpage = $paging_form . " / ". $list2->getNumPages();

	$scroller = $prevpage.$nextpage;

	$output = $list2->output(true);

	$output = str_replace("-C-SCROLLLEFT-", $prevpage, $output);
	$output = str_replace("-C-SCROLLRIGHT-", $nextpage, $output);
	$output = str_replace("-C-PAGE-", i18n("Page")." ".$curpage, $output);
    $output = str_replace("-C-THUMBNAILMODE-", $thumbnailmode, $output);   

	$form = new UI_Form("options");
	$form->setVar("contenido", $sess->id);
	$form->setVar("area", $area);
	$form->setVar("frame", $frame);
	$form->setVar("searchfor", $searchfor);
	$form->setVar("sortmode", $sortmode);
	$form->setVar("sortby", $sortby);
	$form->setVar("startpage", $startpage);
	$form->setVar("appendparameters", $appendparameters);

	$select = new cHTMLSelectElement("thumbnailmode_input");

	$values = Array(
					25 	=> "25",
					50 	=> "50",
					100 => "100",
					200 => "200");
	
	foreach ($values as $key => $value)
	{
		$option = new cHTMLOptionElement($value, $key);
		$select->addOptionElement($key, $option);
	}
	
	$select->setDefault($thumbnailmode);	
    $select->setEvent('change', "document.options.thumbnailmode.value = this.value");
    
    $topbar = $select->render().'<input type="image" onmouseover="this.style.cursor=\'pointer\'" src="images/submit.gif" style="vertical-align:middle; margin-left:5px;">';
    
    $output = str_replace("-C-FILESPERPAGE-", $topbar, $output);

	$script = '<script type="text/javascript">
        /* Session-ID */
        var sid = "{SID}";
        
		  function getY(e) 
		  {
		  	var y = 0;
		  	while(e) 
		  	{
		    	y += e.offsetTop;
		      e = e.offsetParent;
		  	}
		  	return y;
			}
		
		  function getX(e) 
		  {
		  	var x = 0;
		  	while(e) 
		  	{
		    	x += e.offsetLeft;
		      e = e.offsetParent;
		  	}
		  	return x;
			}		
		      
			function findPreviewImage(smallImg)
			{
				var prevImages = document.getElementsByName("prevImage");
				for(var i=0; i<prevImages.length; i++)
				{
					if(prevImages[i].src == smallImg.src)
					{
					return prevImages[i];
					}
				}
			}
			
		  /* Hoverbox */
            function correctPosition(theImage, iWidth, iHeight)
			{
				var previewImage = findPreviewImage(theImage);
                
                if (typeof(previewShowIe6) == "function") {
                    previewShowIe6(previewImage);
                }
                previewImage.style.width = iWidth;
                previewImage.style.height = iHeight;
				previewImage.style.marginTop = getY(theImage);
				previewImage.style.marginLeft = getX(theImage) + 100;
			}      
                 

			// Invert selection of checkboxes
			function invertSelection()
			{
				var delcheckboxes = document.getElementsByName("fdelete[]");
				for(var i=0; i<delcheckboxes.length; i++)
				{
					delcheckboxes[i].checked = !(delcheckboxes[i].checked);
				}
			}
    </script>
    <!--[if IE 6]>
        <script type="text/javascript">
            function previewShowIe6 (previewImage) {
                previewImage.style.display = "block"
                previewImage.style.position = "absolute"
                previewImage.style.top = "-33px"
                previewImage.style.left = "-45px"
                previewImage.style.zIndex = "1"
            }
            
            function previewHideIe6(theImage) {
                var previewImage = findPreviewImage(theImage);
                previewImage.style.display = "none";
            }
        </script>
    <![endif]-->';

	$script = str_replace('{SID}', $sess->id, $script);
	$script = str_replace('{RENAME}', i18n("Enter new filename"), $script);

	$page->addScript("script", $script);
	$markSubItem = markSubMenuItem(0, true);

	$page->addScript("mark", $markSubItem);
	$page->addScript('iZoom', '<script type="text/javascript" src="'.$sess->url("scripts/iZoom.js.php").'"></script>');
    $page->addScript('style', '<style type="text/css">
                               select {
                                vertical-align:middle;
                               }
                               a.invert_hover:active, a.invert_hover:link, a.invert_hover:visited {
                                   cursor: pointer;
                                   color: #0060B1;
                               }
                               a.invert_hover:hover {
                                  color: #000000;
                               }
                               
                               
                               </style>');
	$form->add("", $output);
    $page->setContent($form->render());
	$page->render();
}

uplRender($searchfor, $sortby, $sortmode, $startpage, $thumbnailmode);
?>