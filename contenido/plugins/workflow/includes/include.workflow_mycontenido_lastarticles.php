<?php

/******************************************
* File      :   includes.mycontenido_lastarticles.php
* Project   :   Contenido
* Descr     :   Displays all last edited articles
*               of a category 
*
* Author    :   Timo A. Hummel
* Created   :   08.05.2003
* Modified  :   08.05.2003
*
* © four for business AG
*****************************************/

if (isset($_REQUEST['cfg'])) {
    die ('Illegal call!');
}



$debug = false;




        $sql = "SELECT
                    logtimestamp
                FROM
                    ".$cfg["tab"]["actionlog"]."
                WHERE
                   user_id = '". $auth->auth["uid"] . "'
                ORDER BY
                    logtimestamp DESC
                LIMIT 2";

        $db->query($sql);
        $db->next_record();
        $db->next_record();

        $lastlogin = $db->f("logtimestamp");

            $sql = "SELECT
                    a.idart AS idart,
                    a.idartlang AS idartlang,
                    a.title AS title,
                    c.idcat AS idcat,
                    a.idlang AS idlang,
                    c.is_start AS is_start,
                    c.idcatart AS idcatart,
                    a.idtplcfg AS idtplcfg,
                    a.online AS online,
                    a.created AS created,
                    a.lastmodified AS lastmodified
                FROM
                    ".$cfg["tab"]["art_lang"]." AS a,
                    ".$cfg["tab"]["art"]." AS b,
                    ".$cfg["tab"]["cat_art"]." AS c,
                    ".$cfg["tab"]["actionlog"]." AS d
                WHERE
                    a.idlang    = '".$lang."' AND
                    a.idart     = b.idart AND
                    b.idclient  = '".$client."' AND
                    b.idart     = c.idart AND
                    d.idaction  = 56 AND
                    d.user_id    = '" . $auth->auth["uid"] ."' AND 
                    d.idcatart  = c.idcatart
                    GROUP BY
                        c.idcatart
                    ORDER BY
                        logtimestamp DESC
                    LIMIT 5";
        
        # Debug info
        if ( $debug ) {

            echo "<pre>";
            echo $sql;
            echo "</pre>";

        }

        $db->query($sql);

        # Reset Template
        $tpl->reset();

        # No article
        $no_article = true;

        $tpl->set('s', 'LASTARTICLES', i18n("Recently edited articles", "workflow").":");

$clients = $classclient->getAccessibleClients();

        if(count($clients) > 1)
        {
        
            $clientform = '<form style="margin: 0px" name="clientselect" method="post" target="_top" action="'.$sess->url("index.php").'">';
            $clientselect = '<select class="text_medium" name="changeclient">';

            foreach ($clients as $key => $v_client)
            {
                if ($perm->have_perm_client_lang($key, $lang))
                {

                    $selected = "";
                    if ($key == $client)
                    {
                        $selected = "selected";
                    }
                    $clientselect .= '<option value="'.$key.'" '.$selected.'>'.$v_client['name']." (". $key . ')</option>';
                }
            }

            $clientselect .= "</select>";
            $tpl->set('s', 'CLIENTFORM', $clientform);
            $tpl->set('s', 'PULL_DOWN_MANDANTEN', $clientselect);
            $tpl->set('s', 'OKBUTTON', '<input type="image" src="images/but_ok.gif" alt="'.i18n("Change client", "workflow").'" title="'.i18n("Change client", "workflow").'" border="0">');
         } else {
            $tpl->set('s', 'OKBUTTON', '');
            $tpl->set('s', 'CLIENTFORM', '');
            foreach ($clients as $key => $v_client)
            {
                $name = $v_client['name']." (". $key . ')';
            }
            $tpl->set('s', 'PULL_DOWN_MANDANTEN', $name);
         }

		$str  = i18n("Welcome", "workflow") ." <b>" .$classuser->getRealname($auth->auth["uid"]). "</b>. ";
		$str .= i18n("You are logged in as", "workflow").": <b>" . $auth->auth["uname"] . "</b>.<br><br>";
		$str .= i18n("Last login", "workflow").": ".$lastlogin;

         $tpl->set('s', 'LASTLOGIN',$str);


        while ( $db->next_record() ) {
            $idtplcfg   = $db->f("idtplcfg");
            $idartlang  = $db->f("idartlang");
            $idlang     = $db->f("idlang");
            $idcat      = $db->f("idcat");
            $idart      = $db->f("idart");
            $online     = $db->f("online");
            $is_start   = $db->f("is_start");
            $idcatart   = $db->f("idcatart");
            $created    = $db->f("created");
            $modified   = $db->f("lastmodified");
            $category = "";
            conCreateLocationString($idcat, "&nbsp;/&nbsp;", $category);
            if ($category == "")
            {
                $category = "&nbsp;";
            }
            
            $bgcolor = ( is_int($tpl->dyn_cnt / 2) ) ? $cfg["color"]["table_light"] : $cfg["color"]["table_dark"];

            # Backgroundcolor of the table row
            $tpl->set('d', 'BGCOLOR', $bgcolor);

            # Article Title
            $tmp_alink = $sess->url("frameset.php?area=con&override_area4=con_editcontent&override_area3=con&action=con_editart&idartlang=$idartlang&idart=$idart&idcat=$idcat&idartlang=$idartlang");
            $tpl->set('d', 'ARTICLE', $db->f('title'));

            # Created
            $tpl->set('d', 'CREATED', $created);

            # Lastmodified
            $tpl->set('d', 'LASTMODIFIED', $modified);

            # Category
            $tpl->set('d', 'CATEGORY', $category);
            # Article Template
            if ( 0 == $idtplcfg ) { # Uses Category Template
                $a_tplname = "--- ".i18n("None", "workflow")." ---";
                $a_idtpl = 0;

            } else { # Has own Template

                if ( !is_object($db2) ) {
                    $db2 = new DB_Contenido;
                }

                $sql2 = "SELECT
                            b.name AS tplname,
                            b.idtpl AS idtpl
                         FROM
                            ".$cfg["tab"]["tpl_conf"]." AS a,
                            ".$cfg["tab"]["tpl"]." AS b
                         WHERE
                            a.idtplcfg = '".$idtplcfg."' AND
                            a.idtpl = b.idtpl";
                            
                $db2->query($sql2);
                $db2->next_record();
                
                $a_tplname = $db2->f("tplname");
                $a_idtpl = $db2->f("idtpl");
            }

            if ($a_tplname == "")
            {
                $a_tplname = "&nbsp;";
            }

            $tpl->set('d', 'TPLNAME', $a_tplname);

            # Make Startarticle button
            $tmp_img = (1 == $is_start) ? '<img src="images/isstart1.gif" border="0">' : '<img src="images/isstart0.gif" border="0">';
            $tpl->set('d', 'START', $tmp_img);
            
            if ( $online ) {
                $tmp_online = '<img src="images/online.gif" title="'.i18n("Article is online", "workflow").'" alt="'.i18n("Article is online", "workflow").'" border="0">';

            } else {
                $tmp_online = '<img src="images/offline.gif" title="'.i18n("Article is offline", "workflow").'" alt="'.i18n("Article is offline", "workflow").'" border="0"></a>';
            }

            $tpl->set('d', 'ONLINE', $tmp_online);
            

            # Next iteration
            $tpl->next();
            
            # Articles found
            $no_article = false;
            
        }

        # Sortierungs select
        $s_types = array(1 => "Alphabetisch",
                         2 => "Letze Änderung",
                         3 => "Erstellungsdatum");

        $tpl2 = new Template;
        $tpl2->set('s', 'NAME', 'sort');
        $tpl2->set('s', 'CLASS', 'text_medium');
        $tpl2->set('s', 'OPTIONS', 'onchange="artSort(this)"');
        
        foreach ($s_types as $key => $value) {

            $selected = ( isset($HTTP_GET_VARS['sort']) && $HTTP_GET_VARS['sort'] == $key ) ? 'selected="selected"' : '';

            $tpl2->set('d', 'VALUE',    $key);
            $tpl2->set('d', 'CAPTION',  $value);
            $tpl2->set('d', 'SELECTED', $selected);
            $tpl2->next();
            
        }

        $select     = ( !$no_article ) ? $tpl2->generate($cfg["path"]["templates"] . $cfg['templates']['generic_select'], true) : '';
        $caption    = ( !$no_article ) ? 'Artikel sortieren' : '';
        
        $tpl->set('s', 'ARTSORTCAPTION', $caption);
        $tpl->set('s', 'ARTSORT', $select);

        # Extract Category and Catcfg
        $sql = "SELECT
                    b.name AS name,
                    d.idtpl AS idtpl
                FROM
                    ".$cfg["tab"]["cat"]." AS a,
                    ".$cfg["tab"]["cat_lang"]." AS b,
                    ".$cfg["tab"]["tpl_conf"]." AS c
                LEFT JOIN
                    ".$cfg["tab"]["tpl"]." AS d
                ON
                    d.idtpl = c.idtpl
                WHERE
                    a.idclient = '".$client."' AND
                    a.idcat = '".$idcat."' AND
                    b.idlang = '".$lang."' AND
                    b.idcat = a.idcat AND
                    c.idtplcfg = b.idtplcfg";

        $db->query($sql);
        $db->next_record();

        $cat_idtpl = $db->f("idtpl");

        # Hinweis wenn kein Artikel gefunden wurde
        if ( $no_article ) {

            $tpl->set("d", "START", "&nbsp;");
            $tpl->set("d", "ARTICLE", i18n("No article found", "workflow"));
            $tpl->set("d", "CREATED", "&nbsp;");
            $tpl->set("d", "LASTMODIFIED", "&nbsp;");
            $tpl->set("d", "ARTCONF", "&nbsp;");
            $tpl->set("d", "TPLNAME", "&nbsp;");
            $tpl->set("d", "TPLCONF", "&nbsp;");
            $tpl->set("d", "ONLINE", "&nbsp;");
            $tpl->set('d', 'CATEGORY', '&nbsp');
            $tpl->set("d", "DELETE", "&nbsp;");

            $tpl->next();

        }

        # Kategorie anzeigen und Konfigurieren button
        $tpl->set('s', 'CATEGORY', $cat_name);
        $tpl->set('s', 'CATEGORYCONF', '<a href="'.$sess->url("main.php?area=tplcfg&action=tplcfg_edit&idcat=$idcat&idtpl=$idtpl&frame=4").'"><img src="images/configure.gif" border="0" title="Kategorie konfigurieren" alt="Kategorie konfigurieren"></a>');

        # SELF_URL (Variable für das javascript);
        $tpl->set('s', 'SELF_URL', $sess->url("main.php?area=con&frame=4&idcat=$idcat"));

        # Neuer Artikel link
        $tpl->set('s', 'NEWARTICLE', '<a href="'.$sess->url("main.php?area=con_editart&frame=$frame&action=con_newart&idcat=$idcat").'">Neuen Artikel erstellen</a>');

        # Generate template
        $tpl->generate($cfg['path']['templates'] . $cfg['templates']['mycontenido_lastarticles']);
    


?>
<?php
/*****************************************
* File      :   $RCSfile: include.workflow_mycontenido_lastarticles.php,v $
* Project   :   Contenido Workflow
* Descr     :   Workflow task overview mask
*
* Author    :   $Author: timo.hummel $
*               
* Created   :   30.07.2003
* Modified  :   $Date: 2006/01/13 15:54:41 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.workflow_mycontenido_lastarticles.php,v 1.4 2006/01/13 15:54:41 timo.hummel Exp $
******************************************/

echo "<br>";
echo i18n("Current workflow tasks", "workflow").":<br>";

plugin_include('workflow', 'classes/class.workflow.php');
plugin_include('workflow', 'includes/functions.workflow.php');
cInclude('classes', 'class.ui.php');

$wfa = new WorkflowArtAllocations;
$wfu = new WorkflowUserSequences;
$users = new User;
$db2 = new DB_Contenido;

ob_start();

if ($usershow == "")
{
	$usershow = $auth->auth["uid"];
}


$wfa->select();

while ($wfaitem = $wfa->next())
{
	$wfaid = $wfaitem->get("idartallocation");
	$usersequence[$wfaid] = $wfaitem->get("idusersequence");
	$lastusersequence[$wfaid] = $wfaitem->get("lastusersequence");
	$article[$wfaid] = $wfaitem->get("idartlang");
} 

if (is_array($usersequence))
{
    foreach ($usersequence as $key => $value)
    {
    	$wfu->select("idusersequence = '$value'");
    	if ($obj = $wfu->next())
    	{
    		$userids[$key] = $obj->get("iduser");
    	}
    }
}

if (is_array($userids))
{
    foreach ($userids as $key=>$value)
    {
        $isCurrent[$key] = false;
        
        if ($usershow == $value)
        {
        	$isCurrent[$key] = true;
        }
        
        if ($users->loadUserByUserID($value) == false)
        {
        	/* Yes, it's a group. Let's try to load the group members! */
        	$sql = "SELECT user_id FROM "
        			.$cfg["tab"]["groupmembers"]."
                    WHERE group_id = '".$value."'";
            $db2->query($sql);
       
            while ($db2->next_record())
            {
            	if ($db2->f("user_id") == $usershow)
            	{
            		$isCurrent[$key] = true;
            	}
            }
        } else {
        	if ($value == $usershow)
        	{
        		$isCurrent[$key] = true;
        	}
        }
        
        if ($lastusersequence[$key] == $usersequence[$key])
        {
        	$isCurrent[$key] = false;
        }
    }
}

echo "<br>";

$currentUserSequence = new WorkflowUserSequence;

$languages = new Languages;

//$accessibleClients = $clients->getAccessibleClients();
//{
$availableLanguages = new Languages;
$availableLanguages->select();
$db = new DB_Contenido;
$db2 = new DB_Contenido;
		
if ($availableLanguages->count() > 0)
{
	while ($myLang = $availableLanguages->nextAccessible())
	{
		$key = $myLang->get("idlang");
		$langName[$key] = $myLang->get("name");

        $sql = "SELECT idclient FROM ".$cfg["tab"]["clients_lang"]." WHERE
				idlang = '$key'";
						
		$db2->query($sql);
		if ($db2->next_record())
		{			   
			$myidclient = $db2->f("idclient");
			$myidlang = $key;
			
			if (is_array($isCurrent))
			{   			    		
                foreach ($isCurrent as $key => $value)
                {
                	if ($value == true)
                	{
                		$idartlang = $article[$key];
                    	$sql = "SELECT B.idcat AS idcat, A.title AS title, A.created AS created, A.lastmodified AS changed
                    			FROM ".$cfg["tab"]["art_lang"]." AS A,
                                     ".$cfg["tab"]["cat_art"]." AS B,
                 					 ".$cfg["tab"]["art"]." AS C
                					 WHERE A.idartlang = '$idartlang' AND
                						   A.idart = B.idart AND
                						   A.idart = C.idart AND
                						   A.idlang = '$myidlang' AND
                 						   C.idclient = '$myidclient'";
                    	$db->query($sql);
                
                    	if ($db->next_record())
                    	{
                    		$articleCount[$myidclient][$myidlang]++;
                    	}
                	}
                }
			}
		}
	}
}


$table = new Table($cfg["color"]["table_border"], "solid", 0, 2, $cfg["color"]["table_header"], $cfg["color"]["table_light"], $cfg["color"]["table_dark"]);
$table->start_table();
$table->header_row();
$table->header_cell(i18n("Client", "workflow"));
$table->header_cell(i18n("Language", "workflow"));
$table->header_cell(i18n("Article Count", "workflow"));
$table->end_row();

if (is_array($articleCount))
{
	foreach ($articleCount as $key => $value)
	{
		if (is_array($value))
		{
			foreach ($value as $key2 => $value2)
			{
				$table->row();
				$table->cell($classclient->getClientname($key));
				$table->cell($langName[$key2]);
				$table->cell($value2);
			}
		}
	}
} else {
	$table->row();
	$table->cell(i18n("No tasks found", "workflow"),"center","top",'colspan="4"');
}

$table->end_table();

$frame = ob_get_contents();
ob_end_clean();

$page = new UI_Page;
$page->setContent($frame);
$page->render();

?>
