<?php

/**
 * This file contains the versioning class.
 *
 * @package Core
 * @subpackage Versioning
 * @version
 *
 * @author Jann Dieckmann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Versioning.
 *
 * @package Core
 * @subpackage Versioning
 */
class cContentVersioning {

    /**
     * CONTENIDO database object
     *
     * @var cDb
     */
    protected $db;

    /**
     * article type (current, version, editable)
     *
     * @var string
     */
    protected $articleType;

    /**
     * selected article language (version)
     *
     * @var cApiArticleLanguageVersion or cApiArticleLanguage
     */
    public $selectedArticle;

    /**
     * editable article language version id
     *
     * @var int
     */
    public $editableArticleId;

    /**
     * Constructor to create an instance of this class.
     */
    public function __construct() {
        $this->db = cRegistry::getDb();
    }

    /**
     * Cms type sort function for article output.
     *
     * @SuppressWarnings docBlocks
     * @param array $result[cms type][typeId] = value
     * @return array $result[cms type][typeId] = value
     */
    public function sortResults(array $result) {
        uksort($result, function($a, $b) {
            // cms type sort sequence
            $cmsType = [
                "CMS_HTMLHEAD",
                "CMS_HEAD",
                "CMS_HTML",
                "CMS_TEXT",
                "CMS_IMG",
                "CMS_IMGDESCR",
                "CMS_IMGEDITOR",
                "CMS_LINK",
                "CMS_LINKTARGET",
                "CMS_LINKDESCR",
                "CMS_LINKEDITOR",
                "CMS_DATE",
                "CMS_TEASER",
                "CMS_FILELIST",
                "CMS_RAW"
            ];

            return array_search($a, $cmsType) - array_search($b, $cmsType);
        });

        return $result;
    }

    /**
     * Return date for select box.
     * If current time - lastModified < 1 hour return "%d minutes ago"
     * else return "Y-M-D H:I:S".
     *
     * @param string $lastModified
     * @return string
     */
    public function getTimeDiff($lastModified) {
        $currentTime = new DateTime(date('Y-m-d H:i:s'));
        $modifiedTime = new DateTime($lastModified);
        $diff = $currentTime->diff($modifiedTime);
        $diff2 = (int) $diff->format('%Y%M%D%H');

        if ($diff2 === 0) {
            return sprintf(i18n("%d minutes ago"), $diff->format('%i'));
        } else {
            return date('d.m.Y, H:i\h', strtotime($lastModified));
        }
    }

    /**
     * Returns the current versioning state (disabled (default), simple, advanced).
     *
     * @return string $versioningState
     *
     * @throws cDbException
     * @throws cException
     */
    public static function getState() {
        static $versioningState;

        if (!isset($versioningState)) {

            // versioning enabled is a tri-state => disabled (default), simple, advanced
            $systemPropColl = new cApiSystemPropertyCollection();
            $prop = $systemPropColl->fetchByTypeName('versioning', 'enabled');
            $versioningState = $prop ? $prop->get('value') : false;

            if (false === $versioningState || NULL === $versioningState) {
                $versioningState = 'disabled';
            } else if ('' === $versioningState) {
                // NOTE: An non empty default value overrides an empty value
                $versioningState = 'disabled';
            }

        }

        return $versioningState;
    }

    /**
     * Returns selected article.
     *
     * @todo $idArtlangVersion <-> $selectedArticleId
     *
     * @param int    $idArtLangVersion
     * @param int    $idArtLang
     * @param string $articleType
     * @param int    $selectedArticleId [optional]
     *
     * @return cApiArticleLanguage|cApiArticleLanguageVersion $this->selectedArticle
     *
     * @throws cDbException
     * @throws cException
     */
    public function getSelectedArticle($idArtLangVersion, $idArtLang, $articleType, $selectedArticleId = NULL) {
        $this->editableArticleId = $this->getEditableArticleId($idArtLang);
        $versioningState = $this->getState();
        $this->selectedArticle = NULL;
        if (is_numeric($selectedArticleId)) {
            $idArtLangVersion = $selectedArticleId;
        }

        if (($articleType == 'version' || $articleType == 'editable') && ($versioningState == 'advanced')
            || ($articleType == 'version' && $versioningState == 'simple')) {
            if (is_numeric($idArtLangVersion) && $articleType == 'version') {
                $this->selectedArticle = new cApiArticleLanguageVersion($idArtLangVersion);
            } else if (isset($this->editableArticleId)) {
                $this->selectedArticle = new cApiArticleLanguageVersion($this->editableArticleId);
            }
        } else if ($articleType == 'current' || $articleType == 'editable') {
            $this->selectedArticle = new cApiArticleLanguage($idArtLang);
        }

        return $this->selectedArticle;
    }

    /**
     * Returns $list[1] = CMS_HTMLHEAD for every content existing
     * in article/version with $idArtLang.
     *
     * @param int    $idArtLang
     * @param string $articleType
     *
     * @return array $list
     *
     * @throws cDbException
     * @throws cException
     */
    public function getList($idArtLang, $articleType) {
        $sql = 'SELECT DISTINCT b.idtype as idtype, b.type as name
                FROM %s AS a, %s AS b
                WHERE a.idartlang = %d AND a.idtype = b.idtype
                ORDER BY idtype, name';

        if (($articleType == 'version' || $articleType == 'editable') && $this->getState() == 'advanced'
            || $articleType == 'version' && $this->getState() == 'simple') {
            $this->db->query($sql, cRegistry::getDbTableName('content_version'), cRegistry::getDbTableName('type'), $idArtLang);
        } else if ($articleType == 'current' || $articleType == 'editable' && $this->getState() != 'advanced') {
            $this->db->query($sql, cRegistry::getDbTableName('content'), cRegistry::getDbTableName('type'), $idArtLang);
        }

        $list = [];
        while ($this->db->nextRecord()) {
            $list[$this->db->f('idtype')] = $this->db->f('name');
        }

        return $list;
    }

    /**
     * Return max idcontent.
     *
     * @return int
     * @throws cDbException
     */
    public function getMaxIdContent() {
        $sql = 'SELECT max(idcontent) AS max FROM %s';
        $this->db->query($sql, cRegistry::getDbTableName('content'));
        $this->db->nextRecord();

        return $this->db->f('max');
    }

    /**
     * Returns type of article (current, version or editable).
     *
     * @param int    $idArtLangVersion
     * @param int    $idArtLang
     * @param string $action
     * @param mixed  $selectedArticleId
     *
     * @return string $this->articleType
     *
     * @throws cDbException
     * @throws cException
     */
    public function getArticleType($idArtLangVersion, $idArtLang, $action, $selectedArticleId) {
        $this->editableArticleId = $this->getEditableArticleId($idArtLang);

        if ($this->getState() == 'disabled' // disabled
            || ($this->getState() == 'simple' && ($selectedArticleId == 'current'
                || $selectedArticleId == NULL)
                && ($action == 'con_meta_deletetype' || $action == 'copyto'
                 || $action == 'con_content' || $idArtLangVersion == NULL
                 || $action == 'con_saveart' || $action == 'con_edit' || $action == 'con_meta_edit' || $action == 'con_editart'))
            || $idArtLangVersion == 'current' && $action != 'copyto'
            || $action == 'copyto' && $idArtLangVersion == $this->editableArticleId
            || $action == 'con_meta_change_version' && $idArtLang == 'current'
            || $selectedArticleId == 'current' && $action != 'copyto'
            || $this->editableArticleId == NULL
            && $action != 'con_meta_saveart' && $action != 'con_newart') { // advanced
            $this->articleType = 'current';
        } else if ($this->getState() == 'advanced' && ($selectedArticleId == 'editable'
            || $selectedArticleId == NULL || $this->editableArticleId === $selectedArticleId)
            && ($action == 'con_content' || $action == 'con_meta_deletetype'
                || $action == 'con_meta_edit' || $action == 'con_edit' || $action == 'con_editart')
            || $action == 'copyto' || $idArtLangVersion == 'current'
            || $idArtLangVersion == $this->editableArticleId
            || $action == 'importrawcontent' || $action == 'savecontype'
            || $action == 'con_editart' && $this->getState() == 'advanced' && $selectedArticleId == 'editable'
            || $action == 'con_edit' && $this->getState() == 'advanced' && $selectedArticleId == NULL
            || $action == '20' && $idArtLangVersion == NULL
            || $action == 'con_meta_saveart' || $action == 'con_saveart'
            || $action == 'con_newart' || $action == 'con_meta_change_version'
            && $idArtLangVersion == $this->editableArticleId) {
            $this->articleType = 'editable';
        } else {
            $this->articleType = 'version';
        }

        return $this->articleType;
    }

    /**
     *
     * @param string $class
     * @param string $selectElement
     * @param string $copyToButton
     * @param string $infoText
     * @return string
     */
    public function getVersionSelectionField($class, $selectElement, $copyToButton, $infoText) {
        // TODO avoid inline CSS!!!
        $versionselection = '
            <div class="%s">
                <div>
                    <span style="width: 280px; display: inline; padding: 0px 0px 0px 2px;">
                        <span style="font-weight:bold;color:black;">' . i18n('Select Article Version') . '</span>
                        <span style="margin: 0;"> %s %s
                        </span>
                        <a
                            href="#"
                            id="pluginInfoDetails-link"
                            class="main i-link infoButton"
                            title="">
                        </a>
                    </span>
                </div>
                <div id="pluginInfoDetails" style="display:none;" class="nodisplay">
                       %s
                </div>
            </div>';

        return sprintf(
            $versionselection,
            $class,
            $selectElement,
            $copyToButton,
            $infoText
        );
    }

    /**
     * Returns idartlangversion of editable article.
     *
     * @param int $idArtLang
     *
     * @return int $editableArticleId
     *
     * @throws cDbException
     * @throws cException
     */
    public function getEditableArticleId($idArtLang) {
        if ($this->getState() == 'advanced') {
            $this->db->query(
                'SELECT
                    max(idartlangversion) AS max
                FROM
                    %s
                WHERE
                    idartlang = %d',
                cRegistry::getDbTableName('art_lang_version'),
                $idArtLang
            );
            $this->db->nextRecord();
            $this->editableArticleId = $this->db->f('max');

            return $this->editableArticleId;
        } else if ($this->getState() == 'simple' || $this->getState() == 'disabled') {
            return $idArtLang;
        }
    }

    /**
     * Returns idcontent or idcontentversion.
     *
     * @todo check datatype of return value
     * @param int $idArtLang
     * @param int $typeId
     * @param int $type
     * @param int $versioningState
     * @param int $articleType
     * @param int $version
     * @return array $idContent
     * @throws cDbException
     */
    public function getContentId($idArtLang, $typeId, $type, $versioningState, $articleType, $version) {
        $idContent = [];
        $type = addslashes($type);

        if ($versioningState == 'simple' && $articleType != 'version'
            || $versioningState == 'advanced' && $articleType == 'current'
            || $versioningState == 'disabled') {
            $this->db->query("
                SELECT
                    a.idcontent
                FROM
                    " . cRegistry::getDbTableName('content') . " as a,
                    " . cRegistry::getDbTableName('type') . " as b
                WHERE
                    a.idartlang=" . $idArtLang . "
                    AND a.idtype=b.idtype
                    AND a.typeid = " . $typeId . "
                    AND b.type = '" . $type . "'
                ORDER BY
                    a.idartlang, a.idtype, a.typeid");
            while ($this->db->nextRecord()) {
                $idContent = $this->db->f('idcontent');
            }
        } else {
            $this->db->query("
                SELECT
                    a.idcontentversion
                FROM
                    " . cRegistry::getDbTableName('content_version') . " as a,
                    " . cRegistry::getDbTableName('type') . " as b
                WHERE
                    a.version <= " . $version . "
                    AND a.idartlang = " . $idArtLang . "
                    AND a.idtype = b.idtype
                    AND a.typeid = " . $typeId . "
                    AND b.type = '" . $type . "'
                ORDER BY
                    a.version DESC, a.idartlang, a.idtype, a.typeid LIMIT 1");
            while ($this->db->nextRecord()) {
                $idContent = $this->db->f('idcontentversion');
            }
        }

        return $idContent;
    }

    /**
     * Returns $artLangVersionMap[version][idartlangversion] = lastmodified
     * either from each article-/content- or metatag-version.
     *
     * @param int    $idArtLang
     * @param string $selectElementType [optional]
     *                                  either 'content', 'seo' or 'config'
     *
     * @return array
     * @throws cException
     */
    public function getDataForSelectElement($idArtLang, $selectElementType = '') {
        $artLangVersionMap = [];

        $artLangVersionColl = new cApiArticleLanguageVersionCollection();
        $artLangVersionColl->addResultField('version');
        $artLangVersionColl->addResultField('lastmodified');
        $artLangVersionColl->setWhere('idartlang', $idArtLang);
        $artLangVersionColl->setOrder('version desc');

        try {

            if ($selectElementType == 'content') {

                // select only versions with different content versions
                $contentVersionColl = new cApiContentVersionCollection();
                $contentVersionColl->addResultField('version');
                $contentVersionColl->setWhere('idartlang', $idArtLang);
                $contentVersionColl->query();
                $contentFields['version'] = 'version';

                // check ...
                if (0 >= $contentVersionColl->count()) {
                    throw new cException('no content versions');
                }

                $table = $contentVersionColl->fetchTable($contentFields);

                // add ...
                $contentVersionMap = [];
                foreach ($table AS $key => $item) {
                    $contentVersionMap[] = $item['version'];
                }
                $contentVersionMap = array_unique($contentVersionMap);
                $artLangVersionColl->setWhere('version', $contentVersionMap, 'IN');

            } else if ($selectElementType == 'seo') {

                // select only versions with different seo versions
                $metaVersionColl = new cApiMetaTagVersionCollection();
                $metaVersionColl->addResultField('version');
                $metaVersionColl->setWhere('idartlang', $idArtLang);
                $metaVersionColl->query();
                $metaFields['version'] = 'version';

                // check...
                if (0 >= $metaVersionColl->count()) {
                    throw new cException('no meta versions');
                }

                $table = $metaVersionColl->fetchTable($metaFields);

                // add ...
                $metaVersionMap = [];
                foreach ($table AS $key => $item) {
                    $metaVersionMap[] = $item['version'];
                }
                $metaVersionMap = array_unique($metaVersionMap);
                $artLangVersionColl->setWhere('version', $metaVersionMap, 'IN');

            } else if ($selectElementType == 'config') {

                // select all versions

            }

        } catch (cException $e) {
            return $artLangVersionMap;
        }

        $artLangVersionColl->query();

        $fields['idartlangversion'] = 'idartlangversion';
        $fields['version'] = 'version';
        $fields['lastmodified'] = 'lastmodified';

        if (0 < $artLangVersionColl->count()) {
            $table = $artLangVersionColl->fetchTable($fields);

            foreach ($table AS $key => $item) {
                $artLangVersionMap[$item['version']][$item['idartlangversion']] = $item['lastmodified'];
            }
        }

        return $artLangVersionMap;
    }

    /**
     * Prepares content for saving (consider versioning-mode; prevents multiple
     * storings for filelists e.g.).
     *
     * @param int         $idartlang
     *         the contents idartlang
     * @param cApiContent $content
     *         the content to store
     * @param string      $value
     *         the contents value to store
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function prepareContentForSaving($idartlang, cApiContent $content, $value) {
        // Through a CONTENIDO bug filelists save each of their changes multiple
        // times. Therefore its necessary to check if the same change already
        // has been saved and prevent multiple savings.
        static $savedTypes = [];

        $contentTypeIdent = $content->get('idartlang') . $content->get('idtype') . $content->get('typeid');

        if (isset($savedTypes[$contentTypeIdent])) {
            return;
        }
        $savedTypes[$contentTypeIdent] = $value;

        $versioningState = $this->getState();
        $date = date('Y-m-d H:i:s');

        $auth = cRegistry::getAuth();
        $author = $auth->auth['uname'];

        switch ($versioningState) {
            case 'simple':
                // Create Content Version
                $idContent = NULL;
                if ($content->isLoaded()) {
                    $idContent = $content->getField('idcontent');
                }

                if ($idContent == NULL) {
                    $idContent = $this->getMaxIdContent() + 1;
                }

                $parameters = [
                    'idcontent' => $idContent,
                    'idartlang' => $idartlang,
                    'idtype' => $content->get('idtype'),
                    'typeid' => $content->get('typeid'),
                    'value' => $value,
                    'author' => $author,
                    'created' => $date,
                    'lastmodified' => $date
                ];

                $this->createContentVersion($parameters);
            case 'disabled':
                if ($content->isLoaded()) {
                    // Update existing entry
                    $content->set('value', $value);
                    $content->set('author', $author);
                    $content->set('lastmodified', date('Y-m-d H:i:s'));
                    $content->store();
                } else {
                    // Create new entry
                    $contentColl = new cApiContentCollection();
                    $content = $contentColl->create(
                        $idartlang,
                        $content->get('idtype'),
                        $content->get('typeid'),
                        $value,
                        0,
                        $author,
                        $date,
                        $date
                    );
                }

                // Touch the article to update last modified date
                $lastmodified = date('Y-m-d H:i:s');
                $artLang = new cApiArticleLanguage($idartlang);
                $artLang->set('lastmodified', $lastmodified);
                $artLang->set('modifiedby', $author);
                $artLang->store();

                break;
            case 'advanced':
                // Create Content Version
                $idContent = NULL;
                if ($content->isLoaded()) {
                    $idContent = $content->getField('idcontent');
                }

                if ($idContent == NULL) {
                    $idContent = $this->getMaxIdContent() + 1;
                }

                $parameters = [
                    'idcontent' => $idContent,
                    'idartlang' => $idartlang,
                    'idtype' => $content->get('idtype'),
                    'typeid' => $content->get('typeid'),
                    'value' => $value,
                    'author' => $author,
                    'created' => $date,
                    'lastmodified' => $date
                ];

                $this->createContentVersion($parameters);
            default:
                break;
        }
    }

    /**
     * Create new content version.
     *
     * @param mixed[] $parameters
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function createContentVersion(array $parameters) {
        // set parameters for article language version
        $currentArticle = cRegistry::getArticleLanguage();

        // create new article language version and get the version number
        $parametersArticleVersion = [
            'idartlang' => $currentArticle->getField('idartlang'),
            'idart' => $currentArticle->getField('idart'),
            'idlang' => $currentArticle->getField('idlang'),
            'title' => $currentArticle->getField('title'),
            'urlname' => $currentArticle->getField('urlname'),
            'pagetitle' => $currentArticle->getField('pagetitle'),
            'summary' => $currentArticle->getField('summary'),
            'artspec' => $currentArticle->getField('artspec'),
            'created' => $currentArticle->getField('created'),
            'iscurrentversion' => 1,
            'author' => $currentArticle->getField('author'),
            'lastmodified' => date('Y-m-d H:i:s'),
            'modifiedby' => $currentArticle->getField('author'),
            'published' => $currentArticle->getField('published'),
            'publishedby' => $currentArticle->getField('publishedby'),
            'online' => $currentArticle->getField('online'),
            'redirect' => $currentArticle->getField('redirect'),
            'redirect_url' => $currentArticle->getField('redirect_url'),
            'external_redirect' => $currentArticle->getField('external_redirect'),
            'artsort' => $currentArticle->getField('artsort'),
            'timemgmt' => $currentArticle->getField('timemgmt'),
            'datestart' => $currentArticle->getField('datestart'),
            'dateend' => $currentArticle->getField('dateend'),
            'status' => $currentArticle->getField('status'),
            'time_move_cat' => $currentArticle->getField('time_move_cat'),
            'time_target_cat' => $currentArticle->getField('time_target_cat'),
            'time_online_move' => $currentArticle->getField('time_online_move'),
            'locked' => $currentArticle->getField('locked'),
            'free_use_01' => $currentArticle->getField('free_use_01'),
            'free_use_02' => $currentArticle->getField('free_use_02'),
            'free_use_03' => $currentArticle->getField('free_use_03'),
            'searchable' => $currentArticle->getField('searchable'),
            'sitemapprio' => $currentArticle->getField('sitemapprio'),
            'changefreq' => $currentArticle->getField('changefreq')
        ];

        $artLangVersion = $this->createArticleLanguageVersion($parametersArticleVersion);

        // get the version number of the new article language version that belongs to the content
        $parameters['version'] = $artLangVersion->getField('version');

        $parametersToCheck = $parameters;
        unset(
            $parametersToCheck['lastmodified'],
            $parametersToCheck['author'],
            $parametersToCheck['value'],
            $parametersToCheck['created']
        );

        // if there already is a content type like this in this version,
        // create a new article version, too (needed for storing
        // a version after first change in simple-mode)
        $contentVersion = new cApiContentVersion();
        $contentVersion->loadByMany($parametersToCheck);
        if ($contentVersion->isLoaded()) {
            $artLangVersion = $this->createArticleLanguageVersion($parametersArticleVersion);
            $parameters['version'] = $artLangVersion->getField('version');
            $contentVersionColl = new cApiContentVersionCollection();
            $contentVersionColl->create($parameters);
        } else {
            // if there is no content type like this in this version, create one
            $contentVersionColl = new cApiContentVersionCollection();
            $contentVersionColl->create($parameters);
        }
    }

    /**
     * Create new article language version.
     *
     * @param mixed[] $parameters
     *
     * @return cApiArticleLanguageVersion
     *
     * @throws cDbException
     * @throws cException
     * @global int    $lang
     * @global object $auth
     * @global string $urlname
     * @global string $page_title
     */
    public function createArticleLanguageVersion(array $parameters) {
        global $lang, $auth, $urlname, $page_title;

        // Some stuff for the redirect
        global $redirect, $redirect_url, $external_redirect;

        // Used to indicate "move to cat"
        global $time_move_cat;

        // Used to indicate the target category
        global $time_target_cat;

        // Used to indicate if the moved article should be online
        global $time_online_move;

        global $timemgmt;

        $page_title = (empty($parameters['pagetitle'])) ? addslashes($page_title) : $parameters['pagetitle'];

        $parameters['title'] = stripslashes($parameters['title']);

        $redirect = (empty($parameters['redirect'])) ? cSecurity::toInteger($redirect) : $parameters['redirect'];
        $redirect_url = (empty($parameters['page_title'])) ? stripslashes($redirect_url) : stripslashes($parameters['redirect_url']);
        $external_redirect = (empty($parameters['external_redirect'])) ? stripslashes($external_redirect) : stripslashes($parameters['external_redirect']);

        $urlname = (trim($urlname) == '')? trim($parameters['title']) : trim($urlname);

        if ($parameters['isstart'] == 1) {
            $timemgmt = 0;
        }

        if (!is_array($parameters['idcatnew'])) {
            $parameters['idcatnew'][0] = 0;
        }

        // Set parameters for article language version
        $artLangVersionParameters = [
            'idartlang' => $parameters['idartlang'],
            'idart' => $parameters['idart'],
            'idlang' => $lang,
            'title' => $parameters['title'],
            'urlname' => $urlname,
            'pagetitle' => $page_title,
            'summary' => $parameters['summary'],
            'artspec' => $parameters['artspec'],
            'created' => $parameters['created'],
            'iscurrentversion' => $parameters['iscurrentversion'],
            'author' => $parameters['author'],
            'lastmodified' => date('Y-m-d H:i:s'),
            'modifiedby' => $auth->auth['uname'],
            'published' => $parameters['published'],
            'publishedby' => $parameters['publishedby'],
            'online' => $parameters['online'],
            'redirect' => $redirect,
            'redirect_url' => $redirect_url,
            'external_redirect' => $external_redirect,
            'artsort' => $parameters['artsort'],
            'timemgmt' => $timemgmt,
            'datestart' => $parameters['datestart'],
            'dateend' => $parameters['dateend'],
            'status' => 0,
            'time_move_cat' => $time_move_cat,
            'time_target_cat' => $time_target_cat,
            'time_online_move' => $time_online_move,
            'locked' => 0,
            'free_use_01' => '',
            'free_use_02' => '',
            'free_use_03' => '',
            'searchable' => $parameters['searchable'],
            'sitemapprio' => $parameters['sitemapprio'],
            'changefreq' => $parameters['changefreq']
        ];

        // Create article language version entry
        $artLangVersionColl = new cApiArticleLanguageVersionCollection();
        $artLangVersion = $artLangVersionColl->create($artLangVersionParameters);

        // version Contents if contents are not versioned yet
        if (isset($parameters['idartlang'])){
            $where = 'idartlang = ' . $parameters['idartlang'];
            $contentVersionColl = new cApiContentVersionCollection();
            $contentVersions = $contentVersionColl->getIdsByWhereClause($where);
        }

        if (empty($contentVersions)) {
            $artLang = new cApiArticleLanguage($parameters['idartlang']);
            $conType = new cApiType();
            $content = new cApiContent();
            $artLangContent = $artLang->getContent();
            if (is_array($artLangContent)) {
                foreach ($artLangContent as $type => $typeids) {
                    foreach ($typeids as $typeid => $value) {
                        $conType->loadByType($type);
                        $content->loadByArticleLanguageIdTypeAndTypeId(
                            $parameters['idartlang'],
                            $conType->get('idtype'),
                            $typeid
                        );
                        $content->markAsEditable($artLangVersion->get('version'), 0);
                    }
                }
            }
        }

        // version meta tags if they are not versioned yet
        if (isset($parameters['idartlang'])) {
            $where = 'idartlang = ' . $parameters['idartlang'];
            $metaTagVersionColl = new cApiMetaTagVersionCollection();
            $metaTagVersions = $metaTagVersionColl->getIdsByWhereClause($where);
        }

        if (empty($metaTagVersions)) {
            $where = 'idartlang = ' . $parameters['idartlang'];
            $metaTagColl = new cApiMetaTagCollection();
            $metaTags = $metaTagColl->getIdsByWhereClause($where);
            $metaTag = new cApiMetaTag();
            foreach ($metaTags AS $id) {
                $metaTag->loadBy('idmetatag', $id);
                $metaTag->markAsEditable($artLangVersion->get('version'));
            }
        }

        return $artLangVersion;
    }

    /**
     * Create new Meta Tag Version.
     *
     * @param mixed[] $parameters {
     * @return cApiMetaTagVersion
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
    */
    public function createMetaTagVersion(array $parameters) {
        $coll = new cApiMetaTagVersionCollection();
        $item = $coll->create(
            $parameters['idmetatag'],
            $parameters['idartlang'],
            $parameters['idmetatype'],
            $parameters['value'],
            $parameters['version']
        );

        return $item;
    }
}
