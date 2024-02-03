<?php

/**
 *
 * @package    Module
 * @subpackage ContentSitemapHtml
 * @author     marcus.gnass@4fb.de
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

class ContentSitemapHtmlModule
{

    /**
     * @var cDB
     */
    private $db;

    /**
     * @var int
     */
    private $idlang;

    /**
     * @param array{
     *     db: cDB,
     *     idlang: int,
     * } $options
     */
    public function __construct(array $options)
    {
        $this->db = $options['db'];
        $this->idlang = cSecurity::toInteger($options['idlang']);
    }

    /**
     * Adds articles to categories in given array $tree as returned by
     * cCategoryHelper->getSubCategories().
     *
     * @param array $tree
     * @return array
     */
    public function addArticlesToTree(array $tree): array
    {
        $startIdArtLang = $this->getStartIdArtLang();

        foreach ($tree as $key => $wrapper) {
            $tree[$key]['articles'] = $this->getArticlesFromCategory(
                cSecurity::toInteger($wrapper['idcat']), $startIdArtLang
            );
            $tree[$key]['subcats'] = $this->addArticlesToTree($tree[$key]['subcats']);
        }

        return $tree;
    }


    /**
     * Read the IDs of all article languages that are used as start article
     * of their respective category.
     *
     * @return array
     *         of article language IDs
     */
    private function getStartIdArtLang(): array
    {
        // Get all startidartlangs
        $ret = $this->db->query('-- ContentSitemapHtmlModule->getStartIdArtLang()
        SELECT
            startidartlang
        FROM
            `' . cRegistry::getDbTableName('cat_lang') . '`
        WHERE
            visible = 1
            AND public = 1
        ;');

        $result = [];
        while ($this->db->nextRecord()) {
            $result[] = $this->db->f('startidartlang');
        }

        return $result;
    }

    /**
     * Read article languages of given category and the current language.
     * Only online articles that are searchable are considered.
     * Optionally an array of article language IDs to exclude can be given.
     * If no article languages were found an empty array will be returned.
     *
     * @param int $idcat
     *         ID of category to search in
     * @param array $excludedIdArtLangs [optional]
     *         ID of article languages to exclude
     * @return array
     *         of article languages
     */
    private function getArticlesFromCategory(int $idcat, array $excludedIdArtLangs = []): array
    {
        $ret = $this->db->query('-- ContentSitemapHtmlModule->getArticlesFromCategory()
        SELECT
            art_lang.idartlang
        FROM
            `' . cRegistry::getDbTableName('art_lang') . '` AS art_lang,
            `' . cRegistry::getDbTableName('cat_art') . '` AS cat_art
        WHERE
            art_lang.idart = cat_art.idart
            AND art_lang.idlang = ' . $this->idlang . '
            AND art_lang.online = 1
            AND art_lang.searchable = 1
            AND cat_art.idcat = ' . $idcat . '
        ;');

        if (false === $ret) {
            return [];
        }

        $result = [];
        while ($this->db->nextRecord()) {
            // skip article languages to exclude
            if (in_array($this->db->f('idartlang'), $excludedIdArtLangs)) {
                continue;
            }

            // add article languages to result
            $result[] = new cApiArticleLanguage(
                cSecurity::toInteger($this->db->f('idartlang')))
            ;
        }

        return $result;
    }

}