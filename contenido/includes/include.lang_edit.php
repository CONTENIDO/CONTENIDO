<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Display rights
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.3.2
 * @author     Timo A. Hummel, Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created 2003-04-30
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2009-05-25, Oliver Lohkemper, add iso-639-2- & iso-3166-selecter
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


includePlugins("languages");

$clang = new Language;
$clang->loadByPrimaryKey($idlang);

#Script for refreshing Language Box in Header
$newOption = '';

$db2 = new DB_Contenido;

$sReload = '<script language="javascript">
                var left_bottom = top.content.left.left_bottom;

                if (left_bottom) {
                    var href = left_bottom.location.href;
                    href = href.replace(/&idlang[^&]*/, \'\');
                    left_bottom.location.href = href+"&idlang="+"'.$idlang.'";
                }
            </script>';

if ($action == "lang_newlanguage" || $action == "lang_deletelanguage")
{
    	$page = new UI_Page;
        
        if ($action == "lang_deletelanguage")
		{
            // finally delete from dropdown in header
            $newOption = '<script>
							var langList = top.header.document.getElementById("cLanguageSelect");
							var thepos="";
							for(var i=0;i<langList.length;i++) {
								if(langList.options[i].value == '.$idlang.') {
									thepos = langList.options[i].index;
								}
							}
							langList.remove(thepos);
						  </script>';
        }
        
        if ($action == "lang_newlanguage") {
            // update language dropdown in header
            $new_idlang = 0;
            $db->query( 'SELECT max(idlang) as newlang FROM '.$cfg["tab"]["lang"].';' );
            if ($db->next_record()) {
                $new_idlang = $db->f('newlang');
            }

            $newOption = '<script language="javascript">
							var newLang = new Option("'.i18n("New language").' ('.$new_idlang.')", "'.$new_idlang.'", false, false);
							var langList = top.header.document.getElementById("cLanguageSelect");
							langList.options[langList.options.length] = newLang;
							</script>';
            $idlang = $new_idlang;
        }
        
        if ($targetclient == $client) {
            $page->addScript('refreshHeader', $newOption);
        }
        $page->addScript('reload', $sReload);
    	$page->render();	
} else
{
	if ($action == "lang_edit")
	{
		callPluginStore("languages");
		
		$language = new Language;
    	$language->loadByPrimaryKey($idlang);
    	
    	$language->setProperty("dateformat", "full", stripslashes($datetimeformat));
    	$language->setProperty("dateformat", "date", stripslashes($dateformat));
    	$language->setProperty("dateformat", "time", stripslashes($timeformat));
        
    	$language->setProperty("language", "code", stripslashes($languagecode) );
    	$language->setProperty("country", "code", stripslashes($countrycode) );
		
        // update dropdown in header
        $newOption = '<script language="javascript">
						var langList = top.header.document.getElementById("cLanguageSelect");
						var thepos="";
						for(var i=0;i<langList.length;i++)
						{
							if(langList.options[i].value == '.$idlang.')
							{
								langList.options[i].innerHTML = \''.$langname.' ('.$idlang.')\';
							}
						}
						</script>';
	}
	
    if(!$perm->have_perm_area_action($area, $action))
    {
      $notification->displayNotification("error", i18n("Permission denied"));
	  
    } else {
    
		if ( !isset($idlang) && $action != "lang_new")
		{
		  $notification->displayNotification("error", "no language id given. Usually, this shouldn't happen, except if you played around with your system. if you didn't play around, please report a bug.");
		
		} else {
		
			if (($action == "lang_edit") && ($perm->have_perm_area_action($area, $action)))
			{
				langEditLanguage($idlang, $langname, $sencoding, $active, $direction);
				$noti = $notification->returnNotification("info", i18n("Changes saved"))."<br>";
			} 
		
		
			$tpl->reset();
			
			$sql = "SELECT
						A.idlang AS idlang, A.name AS name, A.active as active, A.encoding as encoding, A.direction as direction,
						B.idclient AS idclient 
					FROM
						".$cfg["tab"]["lang"]." AS A,
						".$cfg["tab"]["clients_lang"]." AS B
					WHERE
						A.idlang = '".Contenido_Security::toInteger($idlang)."' AND
						B.idlang = '".Contenido_Security::toInteger($idlang)."'";
		
			$db->query($sql);
			$db->next_record();
		
			$form = new UI_Table_Form("lang_properties");
			$form->setVar("idlang", $idlang);
			$form->setVar("targetclient", $db->f("idclient"));
			$form->setVar("action", "lang_edit");
			$form->setVar("area", $area);
			$form->setVar("frame", $frame);
			
			
			$charsets = array();
			foreach ($cfg['AvailableCharsets'] as $charset)
			{
				$charsets[$charset] = $charset;	
			}
			
			if ($error) {
				echo $error;
			}
			
			
			$iso_639_2_tags = array('aa' => 'Afar', 'ab' => 'Abkhazian', 'af' => 'Afrikaans', 'am' => 'Amharic', 'ar' => 'Arabic', 'as' => 'Assamese', 'ay' => 'Aymara', 'az' => 'Azerbaijani', 'ba' => 'Bashkir', 'be' => 'Byelorussian', 'bg' => 'Bulgarian', 'bh' => 'Bihari', 'bi' => 'Bislama', 'bn' => 'Bengali', 
									'bo' => 'Tibetan', 'br' => 'Breton', 'ca' => 'Catalan', 'co' => 'Corsican', 'cs' => 'Czech', 'cy' => 'Welsh', 'da' => 'Danish', 'de' => 'German', 'dz' => 'Bhutani', 'el' => 'Greek', 'en' => 'English', 'eo' => 'Esperanto', 'es' => 'Spanish', 'et' => 'Estonian', 'eu' => 'Basque', 'fa' => 'Persian', 
									'fi' => 'Finnish', 'fj' => 'Fiji', 'fo' => 'Faeroese', 'fr' => 'French', 'fy' => 'Frisian', 'ga' => 'Irish', 'gd' => 'Gaelic', 'gl' => 'Galician', 'gn' => 'Guarani', 'gu' => 'Gujarati', 'ha' => 'Hausa', 'hi' => 'Hindi', 'hr' => 'Croatian', 'hu' => 'Hungarian', 'hy' => 'Armenian', 
									'ia' => 'Interlingua', 'ie' => 'Interlingue', 'ik' => 'Inupiak', 'in' => 'Indonesian', 'is' => 'Icelandic', 'it' => 'Italian', 'iw' => 'Hebrew', 'ja' => 'Japanese', 'ji' => 'Yiddish', 'jw' => 'Javanese', 'ka' => 'Georgian', 'kk' => 'Kazakh', 'kl' => 'Greenlandic', 'km' => 'Cambodian', 
									'kn' => 'Kannada', 'ko' => 'Korean', 'ks' => 'Kashmiri', 'ku' => 'Kurdish', 'ky' => 'Kirghiz', 'la' => 'Latin', 'ln' => 'Lingala', 'lo' => 'Laothian', 'lt' => 'Lithuanian', 'lv' => 'Latvian', 'mg' => 'Malagasy', 'mi' => 'Maori', 'mk' => 'Macedonian', 'ml' => 'Malayalam', 'mn' => 'Mongolian', 
									'mo' => 'Moldavian', 'mr' => 'Marathi', 'ms' => 'Malay', 'mt' => 'Maltese', 'my' => 'Burmese', 'na' => 'Nauru', 'ne' => 'Nepali', 'nl' => 'Dutch', 'no' => 'Norwegian', 'oc' => 'Occitan', 'om' => 'Oromo', 'or' => 'Oriya', 'pa' => 'Punjabi', 'pl' => 'Polish', 'ps' => 'Pashto', 'pt' => 'Portuguese', 
									'qu' => 'Quechua', 'rm' => 'Rhaeto-Romance', 'rn' => 'Kirundi', 'ro' => 'Romanian', 'ru' => 'Russian', 'rw' => 'Kinyarwanda', 'sa' => 'Sanskrit', 'sd' => 'Sindhi', 'sg' => 'Sangro', 'sh' => 'Serbo-Croatian', 'si' => 'Singhalese', 'sk' => 'Slovak', 'sl' => 'Slovenian', 'sm' => 'Samoan', 
									'sn' => 'Shona', 'so' => 'Somali', 'sq' => 'Albanian', 'sr' => 'Serbian', 'ss' => 'Siswati', 'st' => 'Sesotho', 'su' => 'Sudanese', 'sv' => 'Swedish', 'sw' => 'Swahili', 'ta' => 'Tamil', 'te' => 'Tegulu', 'tg' => 'Tajik', 'th' => 'Thai', 'ti' => 'Tigtinya', 'tk' => 'Turkmen', 'tl' => 'Tagalog', 
									'tn' => 'Setswana', 'to' => 'Tonga', 'tr' => 'Turkish', 'ts' => 'Tsonga', 'tt' => 'Tatar', 'tw' => 'Twi', 'uk' => 'Ukrainian', 'ur' => 'Urdu', 'uz' => 'Uzbek', 'vi' => 'Vietnamese', 'vo' => 'Volapuk', 'wo' => 'Wolof', 'xh' => 'Xhosa', 'yo' => 'Yoruba', 'zh' => 'Chinese', 'zu' => 'Zulu' );
			array_multisort($iso_639_2_tags);
			$iso_3166_codes = array('af' => 'Afghanistan', 'al' => 'Albania', 'dz' => 'Algeria', 'as' => 'American Samoa', 'ad' => 'Andorra', 'ao' => 'Angola', 'ai' => 'Anguilla', 'aq' => 'Antarctica', 'ag' => 'Antigua/Barbuda', 'ar' => 'Argentina', 'am' => 'Armenia', 'aw' => 'Aruba', 'au' => 'Australia', 'at' => 'Austria', 
									'az' => 'Azerbaijan', 'bs' => 'Bahamas', 'bh' => 'Bahrain', 'bd' => 'Bangladesh', 'bb' => 'Barbados', 'by' => 'Belarus', 'be' => 'Belgium', 'bz' => 'Belize', 'bj' => 'Benin', 'bm' => 'Bermuda', 'bt' => 'Bhutan', 'bo' => 'Bolivia', 'ba' => 'Bosnia/Herzegowina', 'bw' => 'Botswana', 
									'bv' => 'Bouvet Island', 'br' => 'Brazil', 'io' => 'British Indian Ocean Territory', 'bn' => 'Brunei Darussalam', 'bg' => 'Bulgaria', 'bf' => 'Burkina Faso', 'bi' => 'Burundi', 'kh' => 'Cambodia', 'cm' => 'Cameroon', 'ca' => 'Canada', 'cv' => 'Cape verde', 'ky' => 'Cayman Islands', 
									'cf' => 'Central African Republic', 'td' => 'Chad', 'cl' => 'Chile', 'cn' => 'China', 'cx' => 'Christmas Island', 'cc' => 'Cocos -Keeling- Islands', 'co' => 'Colombia', 'km' => 'Comoros', 'cd' => 'Congo, Democratic Republic of/Zaire', 'cg' => 'Congo, Peoples Republic of', 
									'ck' => 'Cook Islands', 'cr' => 'Costa Rica', 'ci' => 'Cote D\'ivoire', 'hr' => 'Croatia/Hrvatska', 'cu' => 'Cuba', 'cy' => 'Cyprus', 'cz' => 'Czech Republic', 'dk' => 'Denmark', 'dj' => 'Djibouti', 'dm' => 'Dominica', 'do' => 'Dominica Republic', 'tl' => 'East Timor', 'ec' => 'Ecuador', 
									'eg' => 'Egypt', 'sv' => 'El Salvador', 'gq' => 'Equatorial Guinea', 'er' => 'Eritrea', 'ee' => 'Estonia', 'et' => 'Ethiopia', 'fk' => 'Falkland Islands/Malvinas', 'fo' => 'Faroe Islands', 'fj' => 'Fiji', 'fi' => 'Finland', 'fr' => 'France', 'fx' => 'France, Metropolitan', 
									'gf' => 'French Guinea', 'pf' => 'French Polynesia', 'tf' => 'French Southern Territories', 'ga' => 'Gabon', 'gm' => 'Gambia', 'ge' => 'Georgia', 'de' => 'Germany', 'gh' => 'Ghana', 'gi' => 'Gibraltar', 'gr' => 'Greece', 'gl' => 'Greenland', 'gd' => 'Grenada', 'gp' => 'Guadeloupe', 
									'gu' => 'Guam', 'gt' => 'Guatemala', 'gn' => 'Guinea', 'gw' => 'Guinea-Bissau', 'gy' => 'Guyana', 'ht' => 'Haiti', 'hm' => 'Heard and Mc Donald Islands', 'hn' => 'Honduras', 'hk' => 'Hong Kong', 'hu' => 'Hungary', 'is' => 'Iceland', 'in' => 'India', 'id' => 'Indonesia', 
									'ir' => 'Iran, Islamic Republic of', 'iq' => 'Iraq', 'ie' => 'Ireland', 'il' => 'Israel', 'it' => 'Italy', 'jm' => 'Jamaica', 'jp' => 'Japan', 'jo' => 'Jordan', 'kz' => 'Kazakhstan', 'ke' => 'Kenya', 'ki' => 'Kiribati', 'kp' => 'Korea, Democratic Peoples Republic of', 
									'kr' => 'Korea, Republic of', 'kw' => 'Kuwait', 'kg' => 'Kyrgyzstan', 'la' => 'Lao Peples Democratic Republic', 'lv' => 'Latvia', 'lb' => 'Lebanon', 'ls' => 'Lesotho', 'lr' => 'Liberia', 'ly' => 'Libyan Arab Jamahiriya', 'li' => 'Liechtenstein', 'lt' => 'Lithuania', 'lu' => 'Luxembourg', 
									'mo' => 'Macau', 'mk' => 'Macedonia, The Former Yugoslav Republic Of', 'mg' => 'Madagascar', 'mw' => 'Malawi', 'my' => 'Malaysia', 'mv' => 'Maldives', 'ml' => 'Mali', 'mt' => 'Malta', 'mh' => 'Marshall Islands', 'mq' => 'Martinique', 'mr' => 'Mauritania', 'mu' => 'Mauritius', 
									'yt' => 'Mayotte', 'mx' => 'Mexico', 'fm' => 'Micronesia, Federated States Of', 'md' => 'Moldova, Republic Of', 'mc' => 'Monaco', 'mn' => 'Mongolita', 'ms' => 'Montserrat', 'ma' => 'Morocco', 'mz' => 'Mozambique', 'mm' => 'Myanmar', 'na' => 'Nambia', 'nr' => 'Nauru', 'np' => 'Nepal', 
									'nl' => 'Netherlands', 'an' => 'Netherlands Antilles', 'nc' => 'New Caledonia', 'nz' => 'New Zealand', 'ni' => 'Nicaragua', 'ne' => 'Niger', 'ng' => 'Nigeria', 'nu' => 'Niue', 'nf' => 'Norfolk Islands', 'mp' => 'Northern Mariana Islands', 'no' => 'Norway', 'om' => 'Oman', 
									'pk' => 'Pakistan', 'pw' => 'Palau', 'ps' => 'Palestinian Territory, Occupied', 'pa' => 'Panama', 'pg' => 'Papua New Guinea', 'py' => 'Paraguay', 'pe' => 'Peru', 'ph' => 'Philippines', 'pn' => 'Pitcairn', 'pl' => 'Poland', 'pt' => 'Portugal', 'pr' => 'Puerto Rico', 'qa' => 'Qatar', 
									're' => 'Reunion', 'ro' => 'Romania', 'ru' => 'Russian Federation', 'rw' => 'Rwanda', 'kn' => 'Saint Kitts/Nevis', 'lc' => 'Saint Lucia', 'vc' => 'Saint Vincent/Grenadines', 'ws' => 'Samoa', 'sm' => 'San Marino', 'st' => 'Sao Tome/Principe', 'sa' => 'Saudi Arabia', 'sn' => 'Senegal', 
									'sc' => 'Seychelles', 'sl' => 'Sierra Leone', 'sg' => 'Singapore', 'sk' => 'Slovakia/Slovak Republic', 'si' => 'Slovenia', 'sb' => 'Solomon Islands', 'so' => 'Somalia', 'za' => 'South Africa', 'gs' => 'South Georgia/South Sandwich Islands', 'es' => 'Spain', 'lk' => 'Sri Lanka', 
									'sh' => 'Santa Helena', 'pm' => 'Santa Pierre/Miquelon', 'sd' => 'Sudan', 'sr' => 'Suriname', 'sj' => 'Svalbard/Jan Mayen Islands', 'sz' => 'Swaziland', 'se' => 'Sweden', 'ch' => 'Switzerland', 'sy' => 'Syrian Arab Republic', 'tw' => 'Taiwan', 'tj' => 'Tajikistan', 
									'tz' => 'Tanzania, United Republic Of', 'th' => 'Thailand', 'tg' => 'Togo', 'tk' => 'Tokelau', 'to' => 'Tonga', 'tt' => 'Trinidad/Tobago', 'tn' => 'Tunisia', 'tr' => 'Turkey', 'tm' => 'Turkmenistan', 'tc' => 'Turks/Caicos Islands', 'tv' => 'Tuvalu', 'ug' => 'Uganda', 'ua' => 'Ukraine', 
									'ae' => 'United Arab Emirates', 'gb' => 'United Kingdom', 'us' => 'United States', 'um' => 'United States Minor Outlying Islands', 'uy' => 'Uruguay', 'uz' => 'Uzbekistan', 'vu' => 'Vanuatu', 'va' => 'Vatican City State -Holy See-', 've' => 'Venezuela', 'vn' => 'Viet Nam', 
									'vg' => 'Virgin Islands, British', 'vi' => 'Virgin Islands, U.S.', 'wf' => 'Wallis/Futuna Islands', 'eh' => 'Western Sahara', 'ye' => 'Yemen', 'yu' => 'Yougoslavia', 'zm' => 'Zambia', 'zw' => 'Zimbabwe' );
			array_multisort($iso_3166_codes);						
			
			
			$eselect = new cHTMLSelectElement("sencoding");
			$eselect->setStyle('width:255px');
			$eselect->autoFill($charsets);
			$eselect->setDefault($db->f("encoding"));
			
			$languagecode = new cHTMLSelectElement("languagecode");
			$languagecode->setStyle('width:255px');
			$languagecode->autoFill($iso_639_2_tags);
			$languagecode->setDefault($clang->getProperty("language", "code"));
									
			$countrycode = new cHTMLSelectElement("countrycode");
			$countrycode->setStyle('width:255px');
			$countrycode->autoFill($iso_3166_codes);
			$countrycode->setDefault($clang->getProperty("country", "code"));
			
			$directionSelect = new cHTMLSelectElement("direction");
			$directionSelect->setStyle('width:255px');
			$directionSelect->autoFill(array("ltr" => i18n("Left to right"), "rtl" => i18n("Right to left")));
			$directionSelect->setDefault($db->f("direction"));
			
			
			$fulldateformat = new cHTMLTextbox("datetimeformat", $clang->getProperty("dateformat", "full"), 40);
			
			$dateformat = new cHTMLTextbox("dateformat", $clang->getProperty("dateformat", "date"), 40);
			
			$timeformat = new cHTMLTextbox("timeformat", $clang->getProperty("dateformat", "time"), 40);
			
			
			displayPlugin("languages", $form);
			
			
			$form->addHeader(i18n("Edit language"));
			
			$form->add(i18n("Language name"), formGenerateField ("text", "langname", htmlspecialchars($db->f("name")), 40, 255));
			$form->add(i18n("Active"), formGenerateCheckbox ("active", "1",$db->f("active")));
			
			$form->addSubHeader(i18n("Language"));
			$form->add(i18n("Encoding"), $eselect);
			$form->add(i18n("Language"), $languagecode->render());
			$form->add(i18n("Country"), $countrycode->render());
			$form->add(i18n("Text direction"), $directionSelect);
			
			$form->addSubHeader(i18n("Time format"));
			$form->add(i18n("Date/Time format"), $fulldateformat->render()); 
			$form->add(i18n("Date format"), $dateformat->render());
			$form->add(i18n("Time format"), $timeformat->render());
			
		
			$page = new UI_Page;
			$page->setContent($noti.$form->render());
			
			if ($targetclient == $client) {
				$page->addScript('refreshHeader', $newOption);
			}
			
			if ($_REQUEST['action'] != '') {
				$page->addScript('reload', $sReload);
			}
			
			$page->render();
		}
    } 
}
?>