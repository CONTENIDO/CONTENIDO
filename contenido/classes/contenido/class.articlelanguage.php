<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Article access class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.4.1
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2007-05-25
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-10-19, Murat Purc, moved Article implementation to cApiArticleLanguage in favor of normalizing the API
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Article language collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiArticleLanguageCollection extends ItemCollection
{
    public function __construct($select = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['art_lang'], 'idartlang');
        $this->_setItemClass('cApiArticleLanguage');
        $this->_setJoinPartner('cApiArticleCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiArticleLanguageCollection($select = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($select);
    }

    /**
     * Creates an article language item entry.
     * @global  object  $auth
     * @param   int  $idart
     * @param   int  $idlang
     * @param   string  $title
     * @param   string  $urlname
     * @param   string  $pagetitle
     * @param   string  $summary
     * @param   int  $artspec
     * @param   string  $created
     * @param   string  $lastmodified
     * @param   string  $author
     * @param   string  $published
     * @param   string  $publishedby
     * @param   int  $online
     * @param   int  $redirect
     * @param   string  $redirect_url
     * @param   int  $external_redirect
     * @param   int  $artsort
     * @param   int  $timemgmt
     * @param   string  $datestart
     * @param   string  $dateend
     * @param   int  $status
     * @param   int  $time_move_cat
     * @param   int  $time_target_cat
     * @param   int  $time_online_move
     * @return cApiArticleLanguage
     */
    public function create($idart, $idlang, $title, $urlname, $pagetitle, $summary, $artspec = 0, $created = '',
                           $lastmodified = '', $author = '', $published = '', $publishedby = '', $online = 0,
                           $redirect = 0, $redirect_url = '', $external_redirect = 0, $artsort = 0,
                           $timemgmt = 0, $datestart = '', $dateend = '', $status = 0, $time_move_cat = 0,
                           $time_target_cat = 0, $time_online_move = 0)
    {
        global $auth;

        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $urlname            = (trim($urlname) == '') ? trim($title) : trim($urlname);
        $urlname            = htmlspecialchars(cApiStrCleanURLCharacters($urlname), ENT_QUOTES);
        $timemgmt           = ($timemgmt == 1) ? 1 : 0;
        $time_move_cat      = ($time_move_cat == 1) ? 1 : 0;
        $time_online_move   = ($time_online_move == 1) ? 1 : 0;
        $redirect           = ($redirect == 1) ? 1 : 0;
        $external_redirect  = ($external_redirect == 1) ? 1 : 0;
        $redirect_url       = ($redirect_url == 'http://' || $redirect_url == '') ? '0' : $redirect_url;

        $item = parent::createNewItem();

        $item->set('idart', (int) $idart);
        $item->set('idlang', (int) $idlang);
        $item->set('title', $this->escape($title));
        $item->set('urlname', $this->escape($urlname));
        $item->set('pagetitle', $this->escape($pagetitle));
        $item->set('summary', $this->escape($summary));
        $item->set('artspec', (int) $artspec);
        $item->set('created', $this->escape($created));
        $item->set('lastmodified', $this->escape($lastmodified));
        $item->set('author', $this->escape($author));
        $item->set('published', $this->escape($published));
        $item->set('publishedby', $this->escape($publishedby));
        $item->set('online', (int) $online);
        $item->set('redirect', (int) $redirect);
        $item->set('redirect_url', $this->escape($redirect_url));
        $item->set('external_redirect', (int) $external_redirect);
        $item->set('artsort', (int) $artsort);
        $item->set('timemgmt', (int) $timemgmt);
        $item->set('datestart',  $this->escape($datestart));
        $item->set('dateend',  $this->escape($dateend));
        $item->set('status', (int) $status);
        $item->set('time_move_cat', (int) $time_move_cat);
        $item->set('time_target_cat', (int) $time_target_cat);
        $item->set('time_online_move', (int) $time_online_move);

        $item->store();

        return $item;
    }

}


/**
 * CONTENIDO API - Article Object
 *
 * This object represents a CONTENIDO article
 *
 * Create object with
 * $obj = new cApiArticleLanguage(idartlang);
 * or with
 * $obj = new cApiArticleLanguage();
 * $obj->loadByArticleAndLanguageId(idart, lang);
 *
 * You can now read the article properties with
 * $obj->getField(property);
 *
 * List of article properties:
 *
 * idartlang         - Language dependant article id
 * idart             - Language indepenant article id
 * idclient          - Id of the client
 * idtplcfg          - Template configuration id
 * title             - Internal Title
 * pagetitle         - HTML Title
 * summary           - Article summary
 * created           - Date created
 * lastmodified      - Date lastmodiefied
 * author            - Article author (username)
 * online            - On-/offline
 * redirect          - Redirect
 * redirect_url      - Redirect URL
 * artsort           - Article sort key
 * timemgmt          - Time management
 * datestart         - Time management start date
 * dateend           - Time management end date
 * status            - Article status
 * free_use_01       - Free to use
 * free_use_02       - Free to use
 * free_use_03       - Free to use
 * time_move_cat     - Move category after time management
 * time_target_cat   - Move category to this cat after time management
 * time_online_move  - Set article online after move
 * external_redirect - Open article in new window
 * locked            - Article is locked for editing
 *
 * You can extract article content with the
 * $obj->getContent(contype [, number]) method.
 *
 * To extract the first headline you can use:
 *
 * $headline = $obj->getContent("htmlhead", 1);
 *
 * If the second parameter is ommitted the method returns an array with all available
 * content of this type. The array has the following schema:
 *
 * array( number => content );
 *
 * $headlines = $obj->getContent("htmlhead");
 *
 * $headlines[1] First headline
 * $headlines[2] Second headline
 * $headlines[6] Sixth headline
 *
 * Legal content type string are defined in the CONTENIDO system table 'con_type'.
 * Default content types are:
 *
 * NOTE: This parameter is case insesitive, you can use html or cms_HTML or CmS_HtMl.
 * Your don't need start with cms, but it won't crash if you do so.
 *
 * htmlhead     - HTML Headline
 * html         - HTML Text
 * headline     - Headline (no HTML)
 * text         - Text (no HTML)
 * img          - Upload id of the element
 * imgdescr     - Image description
 * link         - Link (URL)
 * linktarget   - Linktarget (_self, _blank, _top ...)
 * linkdescr    - Linkdescription
 * swf          - Upload id of the element
 *
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiArticleLanguage extends Item
{
    /**
     * Config array
     * @var array
     */
    public $tab;

    /**
     * Article content
     * @var array
     */
    public $content = null;

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     * @param  bool  $fetchContent  Flag to fetch content
     */
    public function __construct($mId = false, $fetchContent = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['art_lang'], 'idartlang');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
            if (true === $fetchContent) {
                $this->_getArticleContent();
            }
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiArticleLanguage($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

    /**
     * Load data by article and language id
     *
     * @param  int  $idart   Article id
     * @param  int  $idlang  Language id
     * @param  bool  $fetchContent  Flag to fetch content
     * @return  bool  true on success, otherwhise false
     */
    public function loadByArticleAndLanguageId($idart, $idlang, $fetchContent = false)
    {
        $result = true;
        if ($this->virgin == true) {
            $aProps = array('idart' => $idart, 'idlang' => $idlang);
            $aRecordSet = $this->_oCache->getItemByProperties($aProps);
            if ($aRecordSet) {
                // entry in cache found, load entry from cache
                $this->loadByRecordSet($aRecordSet);
            } else {
                $idartlang = $this->_getIdArtLang($idart, $idlang);
                $result = $this->loadByPrimaryKey($idartlang);
            }
        }

        if (true === $fetchContent) {
            $this->_getArticleContent();
        }

        return $result;
    }

    /**
     * Extract 'idartlang' for a specified 'idart' and 'idlang'
     *
     * @param int $idart Article id
     * @param int $idlang Language id
     * @return int Language dependant article id
     */
    protected function _getIdArtLang($idart, $idlang)
    {
        global $cfg;

        $sql = 'SELECT idartlang FROM `%s` WHERE idart = %d AND idlang = %d';
        $this->db->query($sql, $cfg['tab']['art_lang'], $idart, $idlang);
        $this->db->next_record();

        return $this->db->f('idartlang');
    }

    /**
     * Load the articles content and stores it in the 'content' property of the
     * article object.
     *
     * $article->content[type][number] = value;
     */
    public function loadArticleContent()
    {
        $this->_getArticleContent();
    }

    /**
     * Load the articles content and stores it in the 'content' property of the
     * article object.
     *
     * $article->content[type][number] = value;
     *
     * @return void
     */
    protected function _getArticleContent()
    {
        global $cfg;

        if (null !== $this->content) {
            return;
        }

        $sql = 'SELECT b.type, a.typeid, a.value FROM `%s` AS a, `%s` AS b '
             . 'WHERE a.idartlang = %d AND b.idtype = a.idtype '
             . 'ORDER BY a.idtype, a.typeid';

        $this->db->query($sql, $cfg['tab']['content'], $cfg['tab']['type'], $this->get('idartlang'));

        $this->content = array();
        while ($this->db->next_record()) {
            $this->content[strtolower($this->db->f('type'))][$this->db->f('typeid')] = $this->db->f('value');
        }
    }

    /**
     * Get the value of an article property
     *
     * List of article properties:
     *
     * idartlang         - Language dependant article id
     * idart             - Language indepenant article id
     * idclient          - Id of the client
     * idtplcfg          - Template configuration id
     * title             - Internal Title
     * pagetitle         - HTML Title
     * summary           - Article summary
     * created           - Date created
     * lastmodified      - Date lastmodiefied
     * author            - Article author (username)
     * online            - On-/offline
     * redirect          - Redirect
     * redirect_url      - Redirect URL
     * artsort           - Article sort key
     * timemgmt          - Time management
     * datestart         - Time management start date
     * dateend           - Time management end date
     * status            - Article status
     * free_use_01       - Free to use
     * free_use_02       - Free to use
     * free_use_03       - Free to use
     * time_move_cat     - Move category after time management
     * time_target_cat   - Move category to this cat after time management
     * time_online_move  - Set article online after move
     * external_redirect - Open article in new window
     * locked            - Article is locked for editing
     *
     * @param   string  $name
     * @return  string  Value of property
     */
    public function getField($name)
    {
        return $this->values[$name];
    }

    /**
     * Get content(s) from an article.
     *
     * Returns the specified content element or an array("id"=>"value") if the
     * second parameter is omitted.
     *
     * Legal content type string are defined in the CONTENIDO system table 'con_type'.
     * Default content types are:
     *
     * NOTE: Parameter is case insesitive, you can use html or cms_HTML or CmS_HtMl.
     * Your don't need start with cms, but it won't crash if you do so.
     *
     * htmlhead     - HTML Headline
     * html         - HTML Text
     * headline     - Headline (no HTML)
     * text         - Text (no HTML)
     * img          - Upload id of the element
     * imgdescr     - Image description
     * link         - Link (URL)
     * linktarget   - Linktarget (_self, _blank, _top ...)
     * linkdescr    - Linkdescription
     * swf          - Upload id of the element
     *
     * @param   string  $type  CMS_TYPE - Legal cms type string
     * @param   int|null     $id    Id of the content
     * @return  string|array  Content data
     */
    public function getContent($type, $id = null)
    {

        if (null === $this->content) {
            $this->_getArticleContent();
        }

        if (empty($this->content)) {
            return '';
        }

        if ($type == '') {
            return 'Class ' . get_class($this) . ': content-type must be specified!';
        }

        $type = strtolower($type);

        if (!strstr($type, 'cms_')) {
            $type = 'cms_' . $type;
        }

        if (is_null($id)) {
            // return Array
            return $this->content[$type];
        }

        // return String
        return (isset($this->content[$type][$id])) ? $this->content[$type][$id] : '';
    }

    /**
     * Returns all available content types
     *
     * @return array
     */
    public function getContentTypes()
    {
        if (empty($this->content)) {
            throw new Exception('getContentTypes() No content loaded');
        }
        return array_keys($this->content);
    }

}

?>