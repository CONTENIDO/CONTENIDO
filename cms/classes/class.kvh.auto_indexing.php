<?php

 /**
 *
 * @package classes
 * @version SVN Revision $Rev:$
 * @author claus.schunk
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class provides auto keyword generation for all articles.
 */
 class AutoIndexing {

     /**
      * starts autoindexing process.
      */
     public static function start() {
         $data = array();

         $db = cRegistry::getDb();
         $sql = 'SELECT idart from con_art_lang';
         $db->query($sql);
         while ($db->next_record()) {
             array_push($data, $db->toArray());
         }
         $data = Tools::mergeAssoziativ($data, 'idart');

         foreach ($data as $key => $val)
         {
             var_dump($val);
            $article = new cApiArticleLanguage();
            $article->loadByArticleAndLanguageId($val, cRegistry::getLanguageId());
            conMakeArticleIndex($article->getField('idartlang'), $val);
         }

         return 0;
     }

 }

?>
