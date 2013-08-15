<?PHP
  /**
 * description small_advertisement_article
 *
 * @package Module
 * @subpackage content_article_small_advertisement
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

 //get Timestamp & format
 try {
     $idartLang = cRegistry::getArticleLanguageId();
     $col = new cApiArticleLanguage($idartLang);
     $created = $col->getField('created');

     $year = substr($created, 0, 4);
     $date = substr($created, 5, strlen($created));
     $month = substr($date, 0, 2);
     $date = substr($date, 3, strlen($date));
     $day = substr($date, 0, 2);
     $created = $day . '.' . $month . '.' . $year;
 } catch (Exception $e) {
     $e->getMessage();
 }
 // display content types in backend mode
 if (cRegistry::isBackendEditMode()) {
     echo 'Titel: ';
     echo "CMS_HTMLHEAD[1]";
     echo '<br />';
     echo 'Typ: ';
     echo "CMS_HTML[2]";
     echo '<br />';
     echo 'Name: ';
     echo "CMS_HTML[3]";
     echo '<br />';
     echo 'Kontakt: ';
     echo "CMS_HTML[4]";
     echo '<br />';
     echo 'Inserat: ';
     echo "CMS_HTML[5]";
 } else {

     $tpl = Contenido_SmartyWrapper::getInstance();
     $tpl->assign('title', "CMS_HTMLHEAD[1]");
     $tpl->assign('typ', "CMS_HTML[2]");
     $tpl->assign('name', "CMS_HTML[3]");
     $tpl->assign('email', "CMS_HTML[4]");
     $tpl->assign('text', "CMS_HTML[5]");
     $tpl->assign('date', $created);
     $tpl->display('get.tpl');
 }
?>