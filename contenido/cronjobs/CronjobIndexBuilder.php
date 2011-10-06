<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Cronjob to run on a regular base for indexing the content  
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @version    2.0.1
 * @author     Willi Man
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 04.04.2005 
 * 	 modified by Mario Diaz (4fb)
 * 	 modified by adieter 2010-06-22
 * 	 modified 2010-12-02, Munkh-Ulzii Balidar, Code improvement, Merge all implemented indexing functions
 * }}
 * 
 * $Id$
 */

if (!defined("CON_FRAMEWORK")) 
{
    define("CON_FRAMEWORK", true);
}
else
{
	die("script is autonomous and should not be included from anywhere");
}

// include security class and check request variables
include_once (realpath( "../../")."/"	.	'contenido/classes/class.security.php');
Contenido_Security::checkRequests();

global $cfg, $cfgClient;
require_once(realpath( "../../")."/"	.	'contenido/includes/startup.php');
require_once($cfg['path']['contenido']	.	'classes/class.genericdb.php');
require_once($cfg['path']['contenido']	.	'includes/config.php');
require_once($cfg['path']['contenido']	.	'includes/config.path.php');
require_once($cfg['path']['contenido']	.	'includes/config.misc.php');
require_once($cfg['path']['contenido']	.	'includes/cfg_sql.inc.php');
require_once($cfg['path']['contenido']	.	'includes/functions.con.php');
require_once($cfg['path']['contenido']	.	'includes/functions.con2.php');
require_once($cfg['path']['contenido']	.	'includes/functions.api.string.php');
require_once($cfg['path']['contenido']	.	'includes/functions.general.php');
require_once($cfg['path']['contenido']	.	'classes/class.article.php');
require_once($cfg['path']['contenido']	.	'../conlib/db_mysql.inc');
require_once($cfg['path']['contenido']	.	'../conlib/ct_sql.inc');
require_once($cfg['path']['contenido']	.	'../conlib/session.inc');
require_once($cfg['path']['contenido']	.	'../conlib/auth.inc');
require_once($cfg['path']['contenido']	.	'../conlib/perm.inc');
require_once($cfg['path']['contenido']	.	'../conlib/local.php');
require_once($cfg['path']['contenido']	.	$cfg['path']['plugins']	.	'content_allocation/includes/config.plugin.php');
require_once($cfg['path']['contenido']	.	$cfg['path']['plugins']	.	'search/config.search.php');
require_once($cfg["path"]["contenido"]	.	$cfg["path"]["plugins"]	.	'search/FulltextIndexParser.php');
require_once($cfg["path"]["contenido"]	.	$cfg["path"]["plugins"]	.	'search/Article_API.php');
require_once($cfg["path"]["contenido"]	.	$cfg["path"]["plugins"]	.	'search/class.logfile.php');
require_once($cfg["path"]["contenido"]	.	$cfg["path"]["plugins"]	.	'search/IndexTerm.php');
require_once($cfg["path"]["contenido"]	.	$cfg["path"]["plugins"]	.	'search/IndexBuilder.php');
require_once($cfg["path"]["contenido"]	.	$cfg["path"]["plugins"]	.	'search/IndexTermSummary.php');
require_once($cfg["path"]["contenido"]	.	$cfg["path"]["plugins"]	.	'search/IndexTermSummaryCommon.php');
require_once($cfg["path"]["contenido"]	.	$cfg["path"]["plugins"]	.	'search/Staffers.php');
require_once($cfg["path"]["contenido"]	.	$cfg["path"]["plugins"]	.	'search/Downloads.php');
require_once($cfg["path"]["contenido"]	.	$cfg["path"]["plugins"]	.	'search/Advertisements.php');
require_once($cfg["path"]["contenido"]	.	$cfg["path"]["plugins"]	.	'search/ContentAllocs.php');

#####################################################################################################################
# CONFIGURATION #
#################
# init client

rereadClients();
global $client, $lang;
$client = 1;


##########################################
# CODE
ini_set('max_execution_time', 6000);

# init objects and datastructures
$db = new DB_Contenido();

# get client
$sClientName = getClientName($client);
$aLanguages = tool_getLanguagesByClient($db, $cfg, $client);
$aOnlineLanguages = tool_getOnlineLanguagesByClient($db, $cfg, $client);

// the following part is commented out, seems dirty project specific but worth to keep...
#if (!isset($cfgClient[$client]['upl']["frontendpath"]) || empty($cfgClient[$client]['upl']["frontendpath"]))
#{
#	$cfgClient[$client]['upl']["frontendpath"] = "/srv/www/vhosts/default/www/intranet/cms/upload/";
#}

$oLog = new LogFile();
$sLogMessageStart  = "Start Cronjob: ".$_SERVER["HTTP_USER_AGENT"]." ".date("d-m-Y H:i:s")."\n";
$oLog->logMessageByMode($sLogMessageStart, $cfg["path"]["contenido"].$cfg["path"]["plugins"].'search/logs/cronjob.txt', "read_write_end");

#### actions ##############################################################
// keep web-users out !!
if (!isset($argv[0]) && false)
{
	die("access not allowed.");
}

#### START
$start = getmicrotime();

// content_allocs
for ($i = 0; $i < count($aOnlineLanguages); $i++)
{
 	$lang = $i;
	echo "\nlang_".$aOnlineLanguages[$i]. " : indexing...";
	flush();

	$sEncoding = $aLanguages[$aOnlineLanguages[$i]]['encoding'];
	if (file_exists($cfg['path']['contenido'].$cfg['path']['plugins']._translation_table_path_.$sEncoding.'.php'))
	{
		# define arary $aTranslationTable
		include($cfg['path']['contenido'].$cfg['path']['plugins']._translation_table_path_.$sEncoding.'.php');
	}else
	{
		$aTranslationTable = array();
	}
	#echo utf8_decode(urldecode('Wertschöp-fung'));
	Rebuild_Pdf_Index($db, $cfg, $client, $aOnlineLanguages[$i], $aTranslationTable, $sEncoding, $aLanguages);
	RebuildIndex($db, $cfg, $client, $aOnlineLanguages[$i], $aTranslationTable, $sEncoding, $aLanguages);
	Rebuild_CA_Index($db, $cfg, $client,  $aOnlineLanguages[$i], $aTranslationTable, $sEncoding, $aLanguages);
	Rebuild_Downloads_Index($db, $cfg, $client,  $aOnlineLanguages[$i], $aTranslationTable, $sEncoding, $aLanguages);
	Rebuild_Staffers_Index($db, $cfg, $client, $aOnlineLanguages[$i], $aTranslationTable, $sEncoding, $aLanguages);
	
	createContentAllocationConfiguration($db, $cfg, $aOnlineLanguages[$i]);
}

$result = getmicrotime() - $start;

$oLog = new LogFile();
$sLogMessage  = date("d-m-Y H:i:s")."\n";
$sLogMessage .= "Cronjob Done. Client ".$client."\n";
$sLogMessage .= $result." seconds needed for complete indexing of contenido articles, faqs, courses and references in all online languages.\n";
$sLogMessage .= "\n";
echo $sLogMessage;
$oLog->logMessageByMode($sLogMessage, $cfg["path"]["contenido"].$cfg["path"]["plugins"].'search/logs/cronjob.txt', "read_write_end");

#### END

##########################################################################################################################################
# helper functions

/**
 * PDF Index, builds words
 * http://en.wikipedia.org/wiki/Pdftotext
 * aptitude / apt-get install xpdf
 * has to be installed on OS for indexing pdf files
 * Enter description here ...
 * @param Contenido_DB $db
 * @param array 	$cfg
 * @param int 		$client
 * @param int 		$iLang
 * @param array 	$aTranslationTable
 * @param string 	$sEncoding
 * @param array 	$aLanguages
 */
function Rebuild_Pdf_Index($db, $cfg, $client, $iLang, $aTranslationTable, $sEncoding = 'iso-8859-1', $aLanguages)
{
	$aStopWords = array();
	$wc = 0;
	$oFulltextIndexParser = new FulltextIndexParser($sEncoding, $aTranslationTable, $aStopWords, $cfg);
	$oIndexTerm = new IndexTerm($db, $cfg, $client, $iLang);
	$oIndexTermSummary = new IndexTermSummary($db, $cfg, $client, $iLang);
	$oIndexTermSummaryCommon = new IndexTermSummaryCommon($db, $cfg, $client, $iLang);
	$oIndexBuilder = new IndexBuilder($client, $iLang, $oFulltextIndexParser, $oIndexTerm, $oIndexTermSummary, $oIndexTermSummaryCommon);
	$oLog = new LogFile();
	global $cfgClient;
	$sType = "pdf";
	$oIndexBuilder->deleteIndexOfClientByLanguageByType('pdf');
	// build filelist
	//print_r($cfgClient);
	echo $cfgClient[$client]['upl']['path'];
	
	echo "\n building " . $sType . " filelist";
	$aFiles = get_filelist($cfgClient[$client]['upl']['path'], $sType);
	echo " \n _______________ \n";
	//print_r($aFiles);
	foreach ($aFiles as $value)
	{
		$sPdfTextFileDir = $cfgClient[$client]['path']['frontend'] . 'tmp/';
		
		if (!file_exists($sPdfTextFileDir) || !is_dir($sPdfTextFileDir)) {
			mkdir($sPdfTextFileDir);
			chmod($sPdfTextFileDir, 0777);
		}
		$value1 = md5($value);
		$sFileFullPath = $sPdfTextFileDir . $value1.".txt";
		pdfToText ($value, $sFileFullPath);
		
		if (file_exists($sFileFullPath)) {
			$fileContent = file_get_contents($sFileFullPath);
			// store content to database
			// $sql = "INSERT INTO pichain_pdfindex (file, filecontent) VALUES ('".$value."', '".addslashes($fileContent)."')";
			// $db->query($sql);
			//echo "[".$value."]\n".$fileContent."\n";
			flush();
			$words = explode(" ", $fileContent);
			//print_r($words);
			foreach ($words as $value2)
			{
				$value2 = str_replace(array('.', ',', ':', ';'), ' ', $value2);
				$value2 = preg_replace('/[0-9;:,."\']/', ' ', $value2);
				$value2 = trim($value2);
				if ($value2 != "")
				{
					echo ". ";
					flush();
					$wc++;
					if (strlen($value2) > 2 ) {
						$soundex = soundex($value2);
					} else {
						$soundex = '';
					}
					
					$oIndexTerm->setIndexTerm( $value2, 1, $soundex, $value,  'file',  0, 'pdf');
				}
			}
		} else {
			echo "\n Can not found the text file for pdf. " . $sFileFullPath . "\n";
		}
	
	}
	echo "\n_________\n".$wc."___\n";
	
	
	$end = getmicrotime();
	$result = $end - $start;
	
	$sLogMessage  = date("d-m-Y H:i:s")."\n";
	$sLogMessage .= "Article Cronjob Rebuild Index Done. Client ".$client." Language ".$iLang." ".$aLanguages[$iLang]['name']."\n";
	$sLogMessage .= $result." seconds needed for ".count($aRange)." articles\n";
	$sLogMessage .= "####\n";
	 
	$oLog->logMessageByMode($sLogMessage, $cfg["path"]["contenido"].$cfg["path"]["plugins"].'search/logs/cronjob.txt', "read_write_end");


	echo "pdfbuild done\n";
}
 
/**
 * Rebuild the index of the contenido articles
 * @param Contenido_DB $db
 * @param array 	$cfg
 * @param int 		$client
 * @param int 		$iLang
 * @param array 	$aTranslationTable
 * @param string 	$sEncoding
 * @param array 	$aLanguages
 */
function RebuildIndex($db, $cfg, $client, $iLang, $aTranslationTable, $sEncoding = 'iso-8859-1', $aLanguages)
{
	
	if (isset($iLang) AND is_int((int)$iLang) AND $iLang > 0 AND isset($client) AND is_int((int)$client) AND $client > 0)
	{
		
		$start = getmicrotime();
		
		#$aStopWords =  array('der', 'die', 'das', 'und', 'an', 'am', 'von');
		$aStopWords = array();
		$oMyArticle = new ArticleContent($db, $cfg, $client, $iLang);
		$oMyArticleRange = new ArticleRange($db, $cfg, $client, $iLang);  
		$oFulltextIndexParser = new FulltextIndexParser($sEncoding, $aTranslationTable, $aStopWords, $cfg);
		$oIndexTerm = new IndexTerm($db, $cfg, $client, $iLang);
		$oIndexTermSummary = new IndexTermSummary($db, $cfg, $client, $iLang);
		$oIndexTermSummaryCommon = new IndexTermSummaryCommon($db, $cfg, $client, $iLang);
		$oIndexBuilder = new IndexBuilder($client, $iLang, $oFulltextIndexParser, $oIndexTerm, $oIndexTermSummary, $oIndexTermSummaryCommon);
		$oLog = new LogFile();
		
		$oIndexBuilder->oIndexTermSummaryCommon->bDebug = false;
		$oMyArticleRange->bDebug = false;
		
		### DELETE INDEX
		#$oIndexBuilder->deleteIndexOfClientByLanguage();
		$oIndexBuilder->deleteIndexOfClientByLanguageByType('contenido_article');
		
		/*if ($iLang == 1)
		{
			# index articles which are online and articles in categories which are online and not protected and articles with articlespecification 4
			$aRange = $oMyArticleRange->getArticlesOfClientByLanguageByCategoryByStatusByArticleSpec(1, 1, 1, array(4));
		}else
		{*/
			# index articles which are online and articles in categories which are online and not protected
			$aRange = $oMyArticleRange->getArticlesOfClientByLanguageByCategoryByStatusByArticleSpec(1, 1, 1);
		#}
		#print "<pre>aRange<br>"; print_r($aRange); print "</pre>";
		#print "<br>count range "; print (count($aRange)); print "<br>";
		$anz_k = count($aRange);
		echo "\n".$anz_k.":";
		for ($k = 0; $k < $anz_k; $k++)
		{
			if(($k%10)==0)
			{
				echo ".";
			}
			if(($k%100)==0)
			{
				echo "[".$k."] ";
			}
			flush();
		
			#print $aRange[$k]['idart']; print "<br>";
			$aArticleContent = $oMyArticle->getArticleContent($aRange[$k]['idartlang']);
			#print "<pre>"; print_r($aArticleContent); print "</pre>";
			
			$sValueCommon = ' ';
			
			$aKeysOfContentTypes = array_keys($aArticleContent);
			
			for ($i = 0; $i < count($aKeysOfContentTypes); $i++)
			{	
		
				$aKeysOfContentTypeValues = array_keys($aArticleContent[$aKeysOfContentTypes[$i]]);
				$sContentType = $aKeysOfContentTypes[$i];
				#print "<br>content type $sContentType<br>";
				for ($j = 0; $j < count($aKeysOfContentTypeValues); $j++)
				{
					#print "<pre>Key<br>"; print_r($aKeysOfContentTypeValues[$j]); print "</pre>";
					#print "<pre>Value<br>"; print_r($aArticleContent[$aKeysOfContentTypes[$i]][$aKeysOfContentTypeValues[$j]]); print "</pre>";
					
					$iContentTypeNumber = $aKeysOfContentTypeValues[$j];
					#print "<br>content type number $iContentTypeNumber<br>";
					$sValue = $aArticleContent[$aKeysOfContentTypes[$i]][$aKeysOfContentTypeValues[$j]];
					#print "<br>value <br>$sValue<br>";
					
					$sValueCommon .= $sValue.' ';
					
					### BUILD INDEX
					if (trim($sValue) != "")
						$oIndexBuilder->buildIndexSummary($sValue, $aRange[$k]['idart'], $sContentType, $iContentTypeNumber, 'contenido_article');
				}
			}
			#print "<br>value common <br>$sValueCommon<br>";
			### BUILD INDEX
			if (trim($sValueCommon) != "")
			{
				$oIndexBuilder->buildIndexTerms($sValueCommon, $aRange[$k]['idart'], 'contenido_article', 0, 'contenido_article');
				$oIndexBuilder->buildIndexSummaryCommon($sValueCommon, $aRange[$k]['idart'], 'contenido_article');
			}
		}
		
		
		$end = getmicrotime();
		$result = $end - $start;
		
		$sLogMessage  = date("d-m-Y H:i:s")."\n";
        $sLogMessage .= "Article Cronjob Rebuild Index Done. Client ".$client." Language ".$iLang." ".$aLanguages[$iLang]['name']."\n";
        $sLogMessage .= $result." seconds needed for ".count($aRange)." articles\n";
        $sLogMessage .= "####\n";
         
        $oLog->logMessageByMode($sLogMessage, $cfg["path"]["contenido"].$cfg["path"]["plugins"].'search/logs/cronjob.txt', "read_write_end");
	}
		echo "indexbuild done\n";
	
}


/**
 * Indexing of content allocations from content allocation plugin
 * Content allocation ids and association with articles
 * @param Contenido_DB $db
 * @param array 	$cfg
 * @param int 		$client
 * @param int 		$iLang
 * @param array 	$aTranslationTable
 * @param string 	$sEncoding
 * @param array 	$aLanguages
 */
function Rebuild_CA_Index($db, $cfg, $client, $iLang, $aTranslationTable, $sEncoding = 'iso-8859-1', $aLanguages)
{
	$aStopWords = array();
	$oFulltextIndexParser = new FulltextIndexParser($sEncoding, $aTranslationTable, $aStopWords, $cfg);
	$oIndexTerm = new IndexTerm($db, $cfg, $client, $iLang);
	$oIndexTermSummary = new IndexTermSummary($db, $cfg, $client, $iLang);
	$oIndexTermSummaryCommon = new IndexTermSummaryCommon($db, $cfg, $client, $iLang);
	$oIndexBuilder = new IndexBuilder($client, $iLang, $oFulltextIndexParser, $oIndexTerm, $oIndexTermSummary, $oIndexTermSummaryCommon);
	$oLog = new LogFile();
	
	$oIndexBuilder->oIndexTermSummaryCommon->bDebug = false; 
	
	// delete index
	$oIndexBuilder->deleteIndexOfClientByLanguageByType('content_allocation');
	echo "\n\nIndexing Content Allocation ...\n";
	$oContentAllocs = new ContentAllocs($db, $cfg, $iLang);
	$aContentAllocs = $oContentAllocs->getContentAllocs();
	
	$fields = array("idpica_alloc", "name");
	
	for ($i = 0; $i < count($aContentAllocs); $i++)
	{
		// hier allgemein: Also die Werte zusammenf�gen:					
		for ($j = 0; $j < count($fields); $j++)
		{
			if (trim($aContentAllocs[$i][$fields[$j]]) != "") {
			#echo $aContentAllocs[$i][$fields[$j]] . ' - ' . $aContentAllocs[$i]['idart'] . ' - ' .  $aContentAllocs[$i]['type'] . "\n";
				$oIndexTerm->setIndexTerm( $aContentAllocs[$i][$fields[$j]], 1, '', $aContentAllocs[$i]['idart'],  $fields[$j],  0, $aContentAllocs[$i]['type']);
				
			}
		}
		echo ". ";
	}
	$sLogMessage  = date("d-m-Y H:i:s")."\n";
    $sLogMessage .= "Content allocation Cronjob Rebuild Index Done. Client ".$client." Language ".$iLang." ".$aLanguages[$iLang]['name']."\n";
    $sLogMessage .= "####\n";
    $oLog->logMessageByMode($sLogMessage, $cfg["path"]["contenido"].$cfg["path"]["plugins"].'search/logs/cronjob.txt', "read_write_end");
	echo "\nIndexing of Content Allocation done!\n";    
}
 
/**
 * Indexing of staffers from plugin visiting card
 * @param Contenido_DB $db
 * @param array 	$cfg
 * @param int 		$client
 * @param int 		$iLang
 * @param array 	$aTranslationTable
 * @param string 	$sEncoding
 * @param array 	$aLanguages
 */

function Rebuild_Staffers_Index($db, $cfg, $client, $iLang, $aTranslationTable, $sEncoding = 'iso-8859-1', $aLanguages)
{	
	$aStopWords = array();
	$oFulltextIndexParser = new FulltextIndexParser($sEncoding, $aTranslationTable, $aStopWords, $cfg);
	$oIndexTerm = new IndexTerm($db, $cfg, $client, $iLang);
	$oIndexTermSummary = new IndexTermSummary($db, $cfg, $client, $iLang);
	$oIndexTermSummaryCommon = new IndexTermSummaryCommon($db, $cfg, $client, $iLang);
	$oIndexBuilder = new IndexBuilder($client, $iLang, $oFulltextIndexParser, $oIndexTerm, $oIndexTermSummary, $oIndexTermSummaryCommon);
	$oLog = new LogFile();
	
	$oIndexBuilder->oIndexTermSummaryCommon->bDebug = false; 
	
	### DELETE INDEX
	$oIndexBuilder->deleteIndexOfClientByLanguageByType('staffers');
	
	$oStaffers = new Staffers($db, $cfg, $client, $iLang);
	$aStaffers = $oStaffers->getStaffers();
	
	$fields = array("personalnummer", "title", "name", "firstname", "position", "company", "range", "department", "email", "phonenumber", 
						"faxnumber", "roomnumber", "location", "country", "responsibility", "costcenter");
	
	for ($i = 0; $i < count($aStaffers); $i++)
	{
		// hier allgemein: Also die Werte zusammenf�gen:
		#$sCourseSummaryCommon = ' '.$aCourses[$i]['venue'].' '.$aCourses[$i]['title'].' '.$aCourses[$i]['topics'].' '.$aCourses[$i]['objectives'].' '.$aCourses[$i]['description'];
		
		$sCourseSummaryCommon = "";
		for ($j = 0; $j < count($fields); $j++)
		{
			#if ($fields[$j] == "responsibility" && trim($aStaffers[$i][$fields[$j]]) != "")
			#	echo $aStaffers[$i]['personalnummer']." - ".$aStaffers[$i][$fields[$j]]."<br/>";
			$sCourseSummaryCommon .= " ".trim($aStaffers[$i][$fields[$j]]);
		}
		echo "\n " . $aStaffers[$i]['personalnummer'] . "\n";	
		if (trim($sCourseSummaryCommon) != "")
		{
			
			$oIndexBuilder->buildIndexTerms($sCourseSummaryCommon, $aStaffers[$i]['personalnummer'], 'staffers', 0, 'staffers');
			$oIndexBuilder->buildIndexSummaryCommon($sCourseSummaryCommon, $aStaffers[$i]['personalnummer'], 'staffers');
		}
			
		// !!!NEW: Responsibility might be more than one!!!
		for ($j = 0; $j < count($fields); $j++)
		{
			if ($fields[$j] != 'responsibility')
			{
				if (trim($aStaffers[$i][$fields[$j]]) != "")
					$oIndexBuilder->buildIndexSummary($aStaffers[$i][$fields[$j]], $aStaffers[$i]['personalnummer'], $fields[$j], 0, 'staffers');
			}
			else
			{
				$tmp_arr = explode(" ", $aStaffers[$i][$fields[$j]]);

				for ($k = 0; $k < count($tmp_arr); $k++)
				{
					$oIndexBuilder->buildIndexSummary(trim($tmp_arr[$k]), $aStaffers[$i]['personalnummer'], $fields[$j], 0, 'staffers');
				}
			}
		}
	}
	
	$sLogMessage  = date("d-m-Y H:i:s")."\n";
    $sLogMessage .= "Staffers Cronjob Rebuild Index Done. Client ".$client." Language ".$iLang." ".$aLanguages[$iLang]['name']."\n";
    $sLogMessage .= "####\n";
    $oLog->logMessageByMode($sLogMessage, $cfg["path"]["contenido"].$cfg["path"]["plugins"].'search/logs/cronjob.txt', "read_write_end");
}


/**
 * Function for Indexing of Downloads
 * @param Contenido_DB $db
 * @param array 	$cfg
 * @param int 		$client
 * @param int 		$iLang
 * @param array 	$aTranslationTable
 * @param string 	$sEncoding
 * @param array 	$aLanguages
 */
function Rebuild_Downloads_Index($db, $cfg, $client, $iLang, $aTranslationTable, $sEncoding = 'iso-8859-1', $aLanguages)
{
	global $cfgClient;

	$aStopWords = array();
	$oFulltextIndexParser = new FulltextIndexParser($sEncoding, $aTranslationTable, $aStopWords, $cfg);
	$oIndexTerm = new IndexTerm($db, $cfg, $client, $iLang);
	$oIndexTermSummary = new IndexTermSummary($db, $cfg, $client, $iLang);
	$oIndexTermSummaryCommon = new IndexTermSummaryCommon($db, $cfg, $client, $iLang);
	$oIndexBuilder = new IndexBuilder($client, $iLang, $oFulltextIndexParser, $oIndexTerm, $oIndexTermSummary, $oIndexTermSummaryCommon);
	$oLog = new LogFile();
	
	$oIndexBuilder->oIndexTermSummaryCommon->bDebug = false; 
	
	// delete index
	$oIndexBuilder->deleteIndexOfClientByLanguageByType('downloads');
	
	$oDownloads = new Downloads($db, $cfg, $client, $iLang, $cfgClient);
	$aDownloads = $oDownloads->getDownloads();
	
	$fields = array("medianame", "url", "description");
	
	for ($i = 0; $i < count($aDownloads); $i++)
	{
		// hier allgemein: Also die Werte zusammenf�gen:
	
		$sCourseSummaryCommon = "";
		for ($j = 0; $j < count($fields); $j++)
			$sCourseSummaryCommon .= " ".$aDownloads[$i][$fields[$j]];
			
		if (trim($sCourseSummaryCommon) != "")
		{
			$oIndexBuilder->buildIndexTerms($sCourseSummaryCommon, $aDownloads[$i]['iddownload'], 'downloads', 0, 'downloads');
			$oIndexBuilder->buildIndexSummaryCommon($sCourseSummaryCommon, $aDownloads[$i]['iddownload'], 'downloads');
		}
			
		for ($j = 0; $j < count($fields); $j++)
		{
			if (trim($aDownloads[$i][$fields[$j]]) != "")
				$oIndexBuilder->buildIndexSummary($aDownloads[$i][$fields[$j]], $aDownloads[$i]['iddownload'], $fields[$j], 0, 'downloads');
		}
	}
	
	$sLogMessage  = date("d-m-Y H:i:s")."\n";
    $sLogMessage .= "Downloads Cronjob Rebuild Index Done. Client ".$client." Language ".$iLang." ".$aLanguages[$iLang]['name']."\n";
    $sLogMessage .= "####\n";
    $oLog->logMessageByMode($sLogMessage, $cfg["path"]["contenido"].$cfg["path"]["plugins"].'search/logs/cronjob.txt', "read_write_end");
}

/**
 * read filenames wih pathname from dir
 * $filetype string which extension to look for
 * $dir string directory to look in 
 * return 1 dim array with pathnames.filename
 * Enter description here ...
 * @param string 	$dir
 * @param string 	$filetype
 * @return array	File list
 */
function get_filelist( $dir, $filetype  ) {
	$aFileList = array();
	echo"\t\tchecking ".$dir . " for " . $filetype." ...\n";;
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) {  
			
			$aTmpParts = pathinfo($file);
			//print_r($aTmpParts);
			$sTmp = filetype($file);
			if (!isset($aTmpParts['extension']) && $file != "." && $file != "..") {
				//echo " dir found: " . $file."\n";
				$aTmp = get_filelist($dir.$file, $filetype);
				$aFileList = array_merge($aFileList, $aTmp);
			}
			else 
			{
				if (stristr($aTmpParts['extension'], $filetype)) {
					$aFileList[] = $dir ."/". $file;
					echo $dir ."/". $file." found as " . $filetype ."\n";
				}
			}
		}
	}
	else
	{
		echo "Directory " . $dir . " could not be accessed!\n";
	}
   closedir($handle);
  // print_R($aFileList);
   return $aFileList;
}


/**
 * creates a file for inclusion to extend the config array with an assoc array of Content Allocation IDs
 * file is in 
 * @param Contenido_DB 	$db
 * @param array 		$cfg
 * @param int 			$iLang
 */
function createContentAllocationConfiguration($db, &$cfg, $iLang)
{
		$iLang = ((int)$iLang);
		
		$sql = "SELECT name, idpica_alloc "
				. " FROM " . $cfg["tab"]['pica_lang'] 
				. " WHERE online>0 AND idlang=".$iLang;
				
		$db->query($sql);
		$sTxt = "<?php \n//File is created during automated Indexing. Do not manipulate, any changes will be overwritten!";
		$sTxt .= "\n" .'$cfg['."'content_allocation'". '] = (isset($cfg['."'content_allocation'". '])) ? $cfg['."'content_allocation'". '] : array();';
		$sTxt .= "\n". '$cfg['."'content_allocation'". '] = array_merge( $cfg['."'content_allocation'". '], array( ';
		
		while ($db->next_record())
		{
			$sTxt .= "\n\t\t'id_".$db->f('idpica_alloc')."'\t=>\t'".(capiStrCleanURLCharacters (Contenido_Security::unFilter($db->f('name'))))."',";
			$sTxt .= "\n\t\t'".(capiStrCleanURLCharacters (Contenido_Security::unFilter($db->f('name'))))."'\t=>\t'".$db->f('idpica_alloc')."',";
		}
		$sTxt = substr($sTxt, 0, (strlen($sTxt)-1));
		$sTxt .= "\n\t)\n);\n?>";
		$sFilename = $cfg['path']['contenido']	.	$cfg['path']['plugins']	.	'search/cache/allocations_lang_'.$iLang.'.php';
		$pFile = fopen($sFilename, 'w+');
		fwrite($pFile, $sTxt);
		fclose($pFile);

}

/**
 * Build language select Box
 *
 * @return String HTML
 */
function buildLanguageSelect($iSelectedLang, $sSelectBoxName, $aLanguages, $client)
{

	if (!isset($iSelectedLang) OR !is_int((int)$iSelectedLang) OR $iSelectedLang < 0 OR !isset($client) OR !is_int((int)$client) OR $client < 0) { return false; }
    
    $html = '<select name="'.$sSelectBoxName.'">';
    
    $aLanguagesKeys = array_keys($aLanguages);
	
    for ($i = 0; $i < count($aLanguagesKeys); $i++)
    {
        if ($iSelectedLang != $aLanguages[$aLanguagesKeys[$i]]['language_id'])
        {
            $html .= '<option value="'.$aLanguages[$aLanguagesKeys[$i]]['language_id'].'" style="background-color:#EFEFEF"> ('.$aLanguages[$aLanguagesKeys[$i]]['language_id'].') '.$aLanguages[$aLanguagesKeys[$i]]['name'].'</option>';
        } else {
            $html .= '<option value="'.$aLanguages[$aLanguagesKeys[$i]]['language_id'].'" style="background-color:#EFEFEF" selected="selected"> ('.$aLanguages[$aLanguagesKeys[$i]]['language_id'].') '.$aLanguages[$aLanguagesKeys[$i]]['name'].'</option>';
        }
    }

    $html .= '</select>';
    
    return $html;
}

/**
 * Get languages by client
 *
 * @return array
 */
function tool_getLanguagesByClient(&$db, &$cfg, $client)
{
	if (!isset($client) OR !is_int((int)$client) OR $client < 0) { return false; }
    
   	$sql = "
		SELECT
			a.idlang, a.name, a.active, a.encoding
		FROM
			".$cfg["tab"]["lang"]." AS a, 
			".$cfg["tab"]["clients_lang"]." AS b
		WHERE
			b.idclient = ".$client." AND 
			a.idlang = b.idlang 
			ORDER BY a.active DESC";

	#echo "<pre>$sql</pre>";

    $db->query($sql);
	
	$aLanguages = array();
    while ($db->next_record())
    {
        $iLanguage = $db->f("idlang");
        $sLanguageName = $db->f("name").' active '.$db->f("active").' '.$db->f("encoding");
  		$aLanguages[$db->f("idlang")] = array('language_id' => $iLanguage, 'name' => $sLanguageName, 'active' => $db->f("active"), 'encoding' => $db->f("encoding"));
    }

    return $aLanguages;
}

/**
 * Get online languages by client
 *
 * @return String HTML
 */
function tool_getOnlineLanguagesByClient(&$db, &$cfg, $client)
{
	if (!isset($client) OR !is_int((int)$client) OR $client < 0) { return false; }
    
   	$sql = "
		SELECT
			a.idlang, a.name, a.active, a.encoding
		FROM
			".$cfg["tab"]["lang"]." AS a, 
			".$cfg["tab"]["clients_lang"]." AS b
		WHERE
			b.idclient = ".$client." AND
			a.active = 1 AND  
			a.idlang = b.idlang ";

	#echo "<pre>$sql</pre>";

    $db->query($sql);
	
	$aLanguages = array();
    while ($db->next_record())
    {
        $iLanguage = $db->f("idlang");
  		$aLanguages[] = $iLanguage;
    }

    return $aLanguages;
}

/**
 * For Linux System!
 * Import the content from a pdf file into a text file.
 * Has to be installed on OS for indexing pdf files!
 * @param string	$sSourceFile
 * @param sting 	$sTargetFile
 * @param string 	$sOptions
 */
function pdfToText ($sSourceFile, $sTargetFile = '', $sOptions = '-raw -q')
{
	global $cfg, $cfgClient;
	
	echo "___________\nProcessing pdf2text ". $sSourceFile . "\n_________________\n";
	
	exec("pdftotext $sOptions $sSourceFile $sTargetFile");
	
	$val = $sSourceFile . '.txt';
	if (preg_match('/\.txt$/', $val) && file_exists($val)) {
		#echo "\n############ " . $val . " ######## \n";
		#unlink($val);
	}
}

/**
 * For Windows System!
 * Import the content from a pdf file into a text file.
 * Has to be installed on OS for indexing pdf files!
 * @param string	$sSourceFile
 * @param sting 	$sTargetFile
 * @param string 	$sOptions
 */
function pdfToTextWin ($sSourceFile, $sTargetFile = '', $sOptions = '-raw -q')
{
    global $cfg;
    //exec($cfg['path']['contenido'] . "plugins/literature/bin/pdftotext $sOptions $sSourceFile $sTargetFile");
}
?>
