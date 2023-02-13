<?php

/**
 * This file contains the article content helper class.
 *
 * @package Core
 * @subpackage Backend
 * @author Murat Purç <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for the article content helper class in CONTENIDO.
 *
 * @since CONTENIDO 4.10.2
 * @package Core
 * @subpackage Backend
 */
class cArticleContentHelper {

    /**
     * @var cDb
     */
    protected $_db = null;

    /**
     * Constructor.
     *
     * @param cDb|null $db Database instance
     */
    public function __construct(cDb $db = null) {
        if ($db instanceof cDb) {
            $this->_db = $db;
        }
    }

    /**
     * Get content from article by article language.
     *
     * @param int $iIdArtLang
     *         ArticleLanguageId of an article (idartlang)
     *
     * @return array
     *         Array with content of an article indexed by content-types as follows:
     *         - $arr[type][typeid] = value;
     *
     * @throws cDbException|cInvalidArgumentException
     */
    function getContentByIdArtLang($iIdArtLang) {
        if (!$this->_db instanceof cDb) {
            $this->_db = cRegistry::getDb();
        }
        $aContent = [];

        $sql = '-- cArticleContentHelper->getContentByIdArtLang()
            SELECT
                A.value, C.type, A.typeid
            FROM
                `:tab_content`  AS A,
                `:tab_art_lang` AS B,
                `:tab_type`     AS C
            WHERE
                A.idtype    = C.idtype AND
                A.idartlang = B.idartlang AND
                A.idartlang = :id_art_lang';

        $this->_db->query($sql, [
            'tab_content' => cRegistry::getDbTableName('content'),
            'tab_art_lang' => cRegistry::getDbTableName('art_lang'),
            'tab_type' => cRegistry::getDbTableName('type'),
            'id_art_lang' => cSecurity::toInteger($iIdArtLang)
        ]);

        while ($this->_db->nextRecord()) {
            $typeId = cSecurity::toInteger($this->_db->f('typeid'));
            $aContent[$this->_db->f('type')][$typeId] = $this->_db->f('value');
        }

        return $aContent;
    }

    /**
     * Get content from article by article id and language id.
     *
     * @param int $idArt
     *         Id of an article (idart)
     * @param int $idLang
     *         Id of a language (idlang)
     *
     * @return array
     *         Array with content of an article indexed by content-types as follows:
     *         - $arr[type][typeid] = value;
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function getContentByIdArtAndIdLang($idArt, $idLang) {
        if (!$this->_db instanceof cDb) {
            $this->_db = cRegistry::getDb();
        }

        $aContent = [];

        $sql = '-- cArticleContentHelper->getContentByIdArtAndIdLang()
            SELECT
                A.value, C.type, A.typeid
            FROM
                `:tab_content`  AS A,
                `:tab_art_lang` AS B,
                `:tab_type`     AS C
            WHERE
                A.idtype    = C.idtype AND
                A.idartlang = B.idartlang AND
                B.idart     = :id_art AND
                B.idlang    = :id_lang';

        $this->_db->query($sql, [
            'tab_content' => cRegistry::getDbTableName('content'),
            'tab_art_lang' => cRegistry::getDbTableName('art_lang'),
            'tab_type' => cRegistry::getDbTableName('type'),
            'id_art' => cSecurity::toInteger($idArt),
            'id_lang' => cSecurity::toInteger($idLang),
        ]);

        while ($this->_db->nextRecord()) {
            $typeId = cSecurity::toInteger($this->_db->f('typeid'));
            $aContent[$this->_db->f('type')][$typeId] = $this->_db->f('value');
        }

        return $aContent;
    }

}