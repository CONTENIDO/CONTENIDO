<?php

/**
 * class Contenido_Navigation
 *
 * Class for the dynamic Contenido
 * backend navigation.
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 */

cInclude("classes", "class.lang.php");
cInclude("includes", "functions.api.string.php");

class Contenido_Navigation {

   /**
    * @var debug
    */
   var $debug = 0;

   /**
    * array storing all data
    * @var array
    */
   var $data = array();

   /**
    * Constructor
    */
   function Contenido_Navigation() {

      global $cfg, $belang;

        if (!class_exists('XML_doc')) {
            cInclude ("classes", 'class.xml.php');
        }

      $this->xml = new XML_Doc;
      $this->plugxml = new XML_Doc;

      # Load language file
      if ($this->xml->load($cfg['path']['xml'] . $cfg['lang'][$belang]) == false)
      {
         if ($this->xml->load($cfg['path']['xml'] . 'lang_en_US.xml') == false)
         {
            die("Unable to load any XML language file");
         }
      }

   }

   function getName($location) {

        global $cfg, $belang;

      # Extract caption from the xml language file
      # including plugins extended multilang version

      # If a ";" is found entry is from a plugin ->
      # explode location, first is xml file path,
      # second is xpath location in xml file
        if(strstr($location, ';')) {

            $locs = explode(";", $location);
            $file       = trim($locs[0]);
            $xpath      = trim($locs[1]);

            $filepath = explode('/',$file);
            $counter = count($filepath)-1;

            if ($filepath[$counter] == "") {
               unset($filepath[$counter]);
               $counter--;
            }

            if(strstr($filepath[$counter], '.xml')) {
                $filename = $filepath[$counter];
                unset($filepath[$counter]);
                $counter--;
            }

            $filepath[($counter+1)] = "";

            $filepath = implode("/",$filepath);

            if ($this->plugxml->load($cfg["path"]["plugins"] . $filepath . $cfg['lang'][$belang]) == false)
            {
                if (!isset($filename)) { $filename = 'lang_en_US.xml'; }
                if ($this->plugxml->load($cfg["path"]["plugins"] . $filepath . $filename) == false)
                {
                    die("Unable to load $filepath XML language file");
                }
            }
            $caption = $this->plugxml->valueOf( $xpath );

        } else {
            $caption = $this->xml->valueOf( $location );
        }

        return $caption;

   }


   function _buildHeaderData() {

      global $cfg, $perm, $belang;

      $db   = new DB_Contenido;
      $db2 = new DB_Contenido;

      # Load main items
      $sql = "SELECT idnavm, location FROM ".$cfg["tab"]["nav_main"]." ORDER BY idnavm";

      $db->query($sql);

      # Loop result and build array
      while ( $db->next_record() ) {

         /* Extract names from the XML document. */
         $main = $this->getName($db->f("location"));

         # Build data array
         $this->data[$db->f('idnavm')] = array($main);

         $sql = "SELECT
                  a.location AS location,
                  b.name AS area,
                  b.relevant
               FROM
                  ".$cfg["tab"]["nav_sub"]." AS a,
                  ".$cfg["tab"]["area"]." AS b
               WHERE
                  a.idnavm   = '".$db->f('idnavm')."' AND
                  a.level    = '0' AND
                  b.idarea   = a.idarea AND
                  a.online   = '1' AND
                  b.online   = '1'
               ORDER BY
                  a.idnavs";

         $db2->query($sql);

         while ( $db2->next_record() ) {

            $area = $db2->f('area');

            if ($perm->have_perm_area_action($area) || $db2->f('relevant') == 0){

               /* Extract names from the XML document. */
               $name = $this->getName($db2->f("location"));

               $this->data[$db->f('idnavm')][] = array($name, $area);

            }

         } // end while

      } // end while

      # debugging information
      if ($this->debug) {
         echo '<pre>';
         print_r($this->data);
         echo '</pre>';
      }

   } # end function

   #
   # Method that builds the
   # Contenido header document
   #
    function buildHeader($lang) {

        global $cfg, $sess, $client, $changelang, $auth, $cfgClient;

        $this->_buildHeaderData();

        $main = new Template;
        $sub  = new Template;

        $cnt = 0;
        $t_sub = '';
        $numSubMenus = 0;
        
        $smallNavigation = getEffectiveSetting("backend", "small-navigation", "false");
        
        $properties = new PropertyCollection;
        $clientImage = $properties->getValue ("idclient", $client, "backend", "clientimage", false);
        
        if ($smallNavigation !== "false")
        {
        	$main->set('s', 'IESTYLE', 'header_ie_small.css');
        	$main->set('s', 'NSSTYLE', 'header_ns_small.css');
        	$main->set('s', 'OTHERSTYLE', 'header_ns_small.css');
        	$imgsize = "small";
        	$submenuIndent = 116;
        	$itemWidth = 16;
        	$clientWidth = 84;
        } else {
        	$main->set('s', 'IESTYLE', 'header_ie.css');
        	$main->set('s', 'NSSTYLE', 'header_ns.css');
        	$main->set('s', 'OTHERSTYLE', 'header_ns.css');
        	$imgsize = "regular";
        	$submenuIndent = 237;
        	$itemWidth = 32;
        	$clientWidth = 130;
        }
        
				$first = true;
        foreach ($this->data as $id => $item)
        {
            $sub->reset();
						$genSubMenu = false;
						
            foreach ($item as $key => $value)
            {
                if (is_array($value))
                {
                    $sub->set('s', 'SUBID', 'sub_'.$id);
                    $sub->set('d', 'SUBIMGID', 'img_'.$id.'_'.$sub->dyn_cnt);
                    if ($cfg['help'] == true)
                    {
                    	$sub->set('d', 'CAPTION', '<a class="sub" target="content" href="'.
																$sess->url("frameset.php?area=$value[1]").
																'" onclick="document.getElementById(\'help\').setAttribute(\'data\',\''.
																$value[0].'\'); ">'.$value[0].'</a>');
                    } 
                    else 
                    {
												$sub->set('d', 'CAPTION', '<a class="sub" target="content" href="'.
												$sess->url("frameset.php?area=$value[1]").
												'"">'.$value[0].'</a>');                    	
                    }
                    $sub->next();
                    $genSubMenu = true;
                }
            }

            if ($genSubMenu == true)
            {
            	if ($first == true)
                {
                	$t_img = 'border_start_light.gif';
                	$first = false;
                } else {
                	$t_img = 'border_light_light.gif';
                }
            
                # first entry in array is a main menu item
                $main->set('d', 'IMAGE', $t_img);
                $main->set('d', 'SIZE', $imgsize);
                $main->set('d', 'MIMGID', 'mimg_'.$id);
                $main->set('d', 'OPTIONS', 'style="background-color:#A9AEC2" id="'.$id.'"');
								$main->set('d', 'CAPTION', '<a class="main" id="main_'.$id.'" ident="sub_'.$id.'" href="javascript://">'.$item[0].'</a>');
                $main->next();

                $numSubMenus ++;

            } else {
                # first entry in array is a main menu item
            }

            # generate a sub menu item.
			      $sub->set('s', 'LEFTPOS', $submenuIndent);
            $t_sub .= $sub->generate($cfg['path']['templates'] . $cfg['templates']['submenu'], true);
            $cnt ++;
        }

        $main->set('s', 'RIGHTBORDER', 'border_light_dark');
        $main->set('s', 'SUBMENUS', $t_sub);

        $main->set('s', 'MYCONTENIDO', '<a class="main" target="content" href="' .$sess->url("frameset.php?area=mycontenido&frame=4").'"><img src="'.$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"].'my_contenido.gif" border="0" alt="MyContenido" onclick="navi_reset();" title="MyContenido"></a>');
        $main->set('s', 'INFO', '<a class="main" target="content" href="' .$sess->url("frameset.php?area=info&frame=4").'"><img src="'.$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"].'info.gif" border="0" alt="Info" title="Info" onclick="navi_reset();"></a>');
        $main->set('s', 'LOGOUT', $sess->url("logout.php"));
        
        if ($cfg['help'] == true)
        {
	        $script = 'callHelp(document.getElementById(\'help\').getAttribute(\'data\'));';
        	$main->set('s', 'HELP', '<a id="help" class="main" onclick="'.$script.'" data="common"><img src="'.$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"].'but_help.gif" border="0" alt="Hilfe" title="Hilfe"></a>');
        } else {
        	$main->set('s', 'HELP', '');	
        }

        $main->set('s', 'KILLPERMS', '<a class="main" target="header" href="' . $sess->url("header.php?killperms=1").'"><img src="'.$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"].'mycon.gif" border="0" alt="Reload Permission" title="Reload Permissions"></a>');
        $main->set('s', 'CLIENTWIDTH', $clientWidth);

        $tpl = new Template();
        $classuser = new User();
        $classclient = new Client();

        $tpl->set('s', 'NAME', 'changelang');
        $tpl->set('s', 'CLASS', 'text_medium');
        $tpl->set('s', 'ID', 'cLanguageSelect');
        $tpl->set('s', 'OPTIONS', 'onchange="changeContenidoLanguage(this.value)"');

		$availableLanguages = new Languages;
		
		if (getEffectiveSetting("system", "languageorder", "name") == "name")
		{
			$availableLanguages->select("", "", "name ASC");
		} else {
			$availableLanguages->select("", "", "idlang ASC");	
		}
		
		$db = new DB_Contenido;

		if ($availableLanguages->count() > 0)
		{
			while ($myLang = $availableLanguages->nextAccessible())
			{
				$key = $myLang->get("idlang");
				$value = $myLang->get("name");

				/* I want to get rid of such silly constructs
                   very soon :) */

               $sql = "SELECT idclient FROM ".$cfg["tab"]["clients_lang"]." WHERE
						idlang = '$key'";

			   $db->query($sql);

			   if ($db->next_record())
			   {
			   	  if ($db->f("idclient") == $client)
			   	  {
                if ($key == $lang) {
                	$tpl->set('d', 'SELECTED', 'selected');
            	} else {
                    $tpl->set('d', 'SELECTED', '');
            	}

              $tpl->set('d', 'VALUE', $key);
            	$tpl->set('d', 'CAPTION', $value.' ('.$key.')');
            	$tpl->next();

			   	  }
			   }

			}
		} else {
            $tpl->set('d', 'VALUE', 0);
            $tpl->set('d', 'CAPTION', '-- Sprache anlegen --');
            $tpl->next();
		}


        $select = $tpl->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'],true);

        $main->set('s','ACTION', $sess->url("index.php"));
        $main->set('s', 'LANG', $select);
        $main->set('s', 'SIZE', $imgsize);
        $main->set('s', 'WIDTH', $itemWidth);

        $sClientName = $classclient->getClientName($client);
        if (strlen($sClientName) > 25) {
            $sClientName = capiStrTrimHard($sClientName, 25);
        }
        
        if ($clientImage !== false && $clientImage != "")
        {
        	$id = $classclient->getClientName($client).' ('.$client.')';
			$hints = 'alt="'.$id.'" title="'.$id.'"';
        	$clientImage = '<img src="'.$cfgClient[$client]["path"]["htmlpath"].$clientImage.'" '.$hints.'>';
        	$main->set('s', 'CHOSENCLIENT', "<b>".i18n("Client").":</b>&nbsp;".$clientImage);
        } else {
        	$main->set('s', 'CHOSENCLIENT', "<b>".i18n("Client").":</b> ".$sClientName." (".$client.")");
        }
        $main->set('s', 'CHOSENUSER', "<b>".i18n("User").":</b> ".$classuser->getRealname($auth->auth["uid"]));
        $main->set('s', 'SID', $sess->id);
        $main->set('s', 'MAINLOGINLINK', $sess->url("frameset.php?area=mycontenido&frame=4"));
        $main->generate($cfg['path']['templates'] . $cfg['templates']['header']);

    } # end function

} # end class

?>
