<?php

/**
 * This file contains the cContentTypeTeaser class.
 *
 * @package Core
 * @subpackage ContentType
 * @author Timo Trautmann
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.api.images.php');

/**
 * Content type CMS_TEASER which lets the editor select articles in various ways
 * which are displayed as teasers.
 *
 * @package Core
 * @subpackage ContentType
 */
class cContentTypeTeaser extends cContentTypeAbstractTabbed {

    /**
     * Name of the content type.
     *
     * @var string
     */
    const CONTENT_TYPE = 'CMS_TEASER';

    /**
     * Whether the settings should be interpreted as plaintext or XML.
     *
     * @var string
     */
    const SETTINGS_TYPE = 'xml';

    /**
     * Prefix used for posted data.
     * Replaces the property $this->>_prefix.
     *
     * @var string
     */
    const PREFIX = 'teaser';

    /**
     * Array which contains all avariable CMS_Types and its IDs in current
     * CONTENIDO installation (described as hash [idtype => cmstypename]).
     *
     * @var array
     */
    private $_cmsTypes;

    /**
     * Content types in this array will be completely ignored by CMS_TEASER.
     *
     * They won't be displayed in the frontend and they won't be shown as an
     * option in the backend.
     *
     * @var array
     */
    private $_ignoreTypes = array();

    /**
     * If CMS_TEASER tries to load one of the content types listed as the keys
     * of this array it will load the value of that key instead.
     *
     * These won't be listed as an option in the backend either.
     *
     * @var array
     */
    private $_forwardTypes = array(
        "CMS_EASYIMG" => "CMS_IMGEDITOR",
        "CMS_IMG" => "CMS_IMGEDITOR",
        "CMS_LINK" => "CMS_LINKEDITOR"
    );

    /**
     * Placeholders for labels in frontend.
     *
     * Important: This must be a static array!
     *
     * @var array
     */
    protected static $_translations = array(
        "MORE"
    );

    /**
     * Variable for detecting current interation.
     *
     * @var int
     */
    protected $iteration = 0;

    /**
     * Constructor to create an instance of this class.
     *
     * Initialises class attributes and handles store events.
     *
     * @param string $rawSettings
     *         the raw settings in an XML structure or as plaintext
     * @param int    $id
     *         ID of the content type, e.g. 3 if CMS_DATE[3] is used
     * @param array  $contentTypes
     *         array containing the values of all content types
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function __construct($rawSettings, $id, array $contentTypes) {

        // set props
        $this->_formFields = array(
            'teaser_title',
            'teaser_category',
            'teaser_count',
            'teaser_style',
            'teaser_manual',
            'teaser_start',
            'teaser_source_head',
            'teaser_source_head_count',
            'teaser_source_text',
            'teaser_source_text_count',
            'teaser_source_image',
            'teaser_source_image_count',
            'teaser_filter',
            'teaser_sort',
            'teaser_sort_order',
            'teaser_character_limit',
            'teaser_image_width',
            'teaser_image_height',
            'teaser_manual_art',
            'teaser_image_crop',
            'teaser_source_date',
            'teaser_source_date_count'
        );

        // call parent constructor
        parent::__construct($rawSettings, $id, $contentTypes);

        // if form is submitted, store the current teaser settings
        // notice: also check the ID of the content type (there could be more
        // than one content type of the same type on the same page!)
        if (isset($_POST[static::PREFIX . '_action']) && $_POST[static::PREFIX . '_action'] == 'store' && isset($_POST[static::PREFIX . '_id']) && (int) $_POST[static::PREFIX . '_id'] == $this->_id) {
            $this->_storeSettings();
        }

        $this->_setDefaultValues();
    }

    /**
     * Returns all translation strings for mi18n.
     *
     * @param array $translationStrings
     *         translation strings
     * @return array
     *         updated translation string
     */
    public static function addModuleTranslations(array $translationStrings) {
        foreach (self::$_translations as $value) {
            $translationStrings[] = $value;
        }

        return $translationStrings;
    }

    /**
     * Sets some default values for teaser in case that there is no value
     * defined.
     */
    private function _setDefaultValues() {
        // character limit is 120 by default
        if ((int) $this->_settings['teaser_character_limit'] == 0) {
            $this->_settings['teaser_character_limit'] = 120;
        }

        // teaser cont is 6 by default
        if ((int) $this->_settings['teaser_count'] == 0) {
            $this->_settings['teaser_count'] = 6;
        }

        // teasersort is creationdate by default
        if (cString::getStringLength($this->_settings['teaser_sort']) == 0) {
            $this->_settings['teaser_sort'] = 'creationdate';
        }

        // teaser style is liststyle by default
        if (cString::getStringLength($this->_settings['teaser_style']) == 0) {
            $this->_settings['teaser_style'] = 'cms_teaser_slider.html';
        }

        // teaser image width default
        if ((int) $this->_settings['teaser_image_width'] == 0) {
            $this->_settings['teaser_image_width'] = 100;
        }

        // teaser image height default
        if ((int) $this->_settings['teaser_image_height'] == 0) {
            $this->_settings['teaser_image_height'] = 75;
        }

        // cms type head default
        if (cString::getStringLength($this->_settings['teaser_source_head']) == 0) {
            $this->_settings['teaser_source_head'] = 'CMS_HTMLHEAD';
        }

        // cms type text default
        if (cString::getStringLength($this->_settings['teaser_source_text']) == 0) {
            $this->_settings['teaser_source_text'] = 'CMS_HTML';
        }

        // cms type image default
        if (cString::getStringLength($this->_settings['teaser_source_image']) == 0) {
            $this->_settings['teaser_source_image'] = 'CMS_IMG';
        }

        // cms type date default
        if (cString::getStringLength($this->_settings['teaser_source_date']) == 0) {
            $this->_settings['teaser_source_date'] = 'CMS_DATE';
        }

        // sort order of teaser articles
        if (cString::getStringLength($this->_settings['teaser_sort_order']) == 0) {
            $this->_settings['teaser_sort_order'] = 'asc';
        }

        // teaser image crop option
        if (cString::getStringLength($this->_settings['teaser_image_crop']) == 0 || $this->_settings['teaser_image_crop'] == 'false') {
            $this->_settings['teaser_image_crop'] = 'false';
        }
    }

    /**
     * Generates the code which should be shown if this content type is shown in
     * the frontend.
     *
     * @return string
     *         escaped HTML code which sould be shown if content type is shown in frontend
     */
    public function generateViewCode() {
        $code = '";?><?php
                    $teaser = new cContentTypeTeaser(\'%s\', %s, %s);
                    echo $teaser->generateTeaserCode();
                 ?><?php echo "';
        // escape ' to avoid accidently ending the string in $code
        $code = sprintf($code, str_replace('\'', '\\\'', $this->_rawSettings), $this->_id, 'array()');

        return $code;
    }

    /**
     * Function returns idarts of selected articles as array
     *
     * @return array
     *
     * @throws cDbException
     * @throws cException
     */
    public function getConfiguredArticles() {
        $articles = $this->generateTeaserCode(true);

        return $articles;
    }

    /**
     * Function is called in edit- and viewmode in order to generate teasercode
     * for output
     *
     * @param bool $returnAsArray [optional]
     *                            modeswitch betwwen template generation and returning result as array
     *
     * @return mixed
     *         string of select box or array of articles
     *
     * @throws cDbException
     * @throws cException
     */
    public function generateTeaserCode($returnAsArray = false) {

        $articles = array();

        $template = new cTemplate();
        // set title of teaser
        $template->set('s', 'TEASER_TITLE', $this->_settings['teaser_title']);

        // decide if it is a manual or category teaser
        if ($this->_settings['teaser_manual'] == 'true' && count($this->_settings['teaser_manual_art']) > 0) {
            $manualArts = $this->_settings['teaser_manual_art'];
            if (!empty($manualArts) && !is_array($manualArts)) {
                $manualArts = array(
                    $manualArts
                );
            }
            if (is_array($manualArts)) {
                $i = 0;
                // in manual case get all art to display and generate article objects manually
                foreach ($manualArts as $idArt) {
                    $article = new cApiArticleLanguage();
                    $article->loadByArticleAndLanguageId($idArt, $this->_lang);

                    // try to fill teaser image
                    if ($returnAsArray == false && $this->_fillTeaserTemplateEntry($article, $template)) {
                        $i++;
                        // break render, if teaser limit is reached
                        if ($i == $this->_settings['teaser_count']) {
                            break;
                        }
                    }

                    if ($returnAsArray == true && $this->_fillTeaserTemplateEntry($article, $template)) {
                        array_push($articles, $article);

                        if ($i == $this->_settings['teaser_count']) {
                            break;
                        }
                    }
                }
            }
        } else {
            // in case of automatic teaser use class cArticleCollector for getting all arts in category
            $options = array(
                'lang' => $this->_lang,
                'client' => $this->_client,
                'idcat' => $this->_settings['teaser_category'],
                'order' => $this->_settings['teaser_sort'],
                'direction' => $this->_settings['teaser_sort_order'],
                'limit' => $this->_settings['teaser_count'],
                'start' => false,
                'offline' => false
            );

            if ($this->_settings['teaser_start'] == 'true') {
                $options['start'] = true;
            }

            $artCollector = new cArticleCollector($options);
            foreach ($artCollector as $article) {
                $title = trim(
                    $this->_getArtContent(
                        $article,
                        $this->_settings['teaser_source_head'],
                        $this->_settings['teaser_source_head_count']
                    )
                );
                $text = trim(
                    $this->_getArtContent(
                        $article,
                        $this->_settings['teaser_source_text'],
                        $this->_settings['teaser_source_text_count']
                    )
                );
                $imageId = trim(
                    $this->_getArtContent(
                        $article,
                        $this->_settings['teaser_source_image'],
                        $this->_settings['teaser_source_image_count']
                    )
                );

                if (!empty($title) || !empty($text) || !empty($imageId)) {
                    if ($returnAsArray) {
                        array_push($articles, $article);
                    } else {
                        $this->_fillTeaserTemplateEntry($article, $template);
                    }
                }
            }
        }

        // generate teaser template
        if ($returnAsArray) {
            return $articles;
        } else {
            $filename = $this->_cfgClient[$this->_client]['path']['frontend'] . 'templates/' . $this->_settings['teaser_style'];
            if (file_exists($filename) && count($template->Dyn_replacements) > 0) {
                $code = $template->generate($filename, true);
            } else {
                $code = '';
            }

            return $code;
        }
    }

    /**
     * In edit and view mode this function fills teaser template with
     * informations from a CONTENIDO article object.
     *
     * @param cApiArticleLanguage $article
     *         CONTENIDO Article object
     * @param cTemplate           $template
     *         CONTENIDO Template object (as reference)
     *
     * @return bool
     *         success state of this operation
     * 
     * @throws cDbException
     * @throws cException
     */
    private function _fillTeaserTemplateEntry(cApiArticleLanguage $article, cTemplate &$template) {
        global $contenido;

        // get necessary informations for teaser from articles use properties in a Settings for retrieval
        $title = $this->_getArtContent($article, $this->_settings['teaser_source_head'], $this->_settings['teaser_source_head_count']);
        $text = $this->_getArtContent($article, $this->_settings['teaser_source_text'], $this->_settings['teaser_source_text_count']);
        $imageId = $this->_getArtContent($article, $this->_settings['teaser_source_image'], $this->_settings['teaser_source_image_count']);
        $date = $this->_getArtContent($article, $this->_settings['teaser_source_date'], $this->_settings['teaser_source_date_count']);

        // check if CMS type is date before trying to parse it as date
        if ('CMS_DATE' === $this->_settings['teaser_source_date']) {
            $date = trim($date);
            $date = new cContentTypeDate($date, 1, array('CMS_DATE'));
            $date = $date->generateViewCode();
        } else {
            $date = trim(strip_tags($date));
        }

        $idArt = $article->getField('idart');
        $published = $article->getField('published');
        $online = $article->getField('online');
        $afields = [];
        foreach ($article as $item => $value){
            if ($item === 'values') {
                foreach ($value as $field => $val){
                    $afields[$field] = $val;
                }
            }
        }

        if ($online == 1 || $contenido) {
            // teaserfilter defines strings which must be contained in text for display
            // if string is defined check if article contains this string and abort, if article does not contain this string
            if ($this->_settings['teaser_filter'] != '') {
                $iPosText = cString::findLastPos(conHtmlEntityDecode($text), $this->_settings['teaser_filter']);
                $iPosHead = cString::findLastPos(conHtmlEntityDecode($title), $this->_settings['teaser_filter']);
                if (is_bool($iPosText) && !$iPosText && is_bool($iPosHead) && !$iPosHead) {
                    return false;
                }
            }

            // convert date to readable format
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/', $published, $results)) {
                $published = $results[3] . '.' . $results[2] . '.' . $results[1];
            }

            // strip tags in teaser text and cut it if it is to long
            $title = trim(strip_tags($title));
            $text = trim(strip_tags($text));
            if (cString::getStringLength($text) > $this->_settings['teaser_character_limit']) {
                $text = cString::trimAfterWord($text, $this->_settings['teaser_character_limit']) . '...';
            }

            // try to get a teaser image directly from cms_img or try to extract if a content type is given, wich contains html
            $cApiUploadMeta = new cApiUploadMeta();
            if ((int) $imageId > 0) {
                $image = $this->_getImage($imageId, $this->_settings['teaser_image_width'], $this->_settings['teaser_image_height'], $this->_settings['teaser_image_crop']);
                $template->set('d', 'IMAGE', $image['element']);
                $template->set('d', 'IMAGE_SRC', $image['src']);
                $cApiUploadMeta->loadByMany(array('idlang' => cRegistry::getLanguageId(), 'idupl' => $imageId));
                if($cApiUploadMeta->isLoaded()) {
                    $template->set('d', 'IMAGE_MEDIANAME', $cApiUploadMeta->get('medianame'));
                } else {
                    $template->set('d', 'IMAGE_MEDIANAME', '');
                }
            } elseif (strip_tags($imageId) != $imageId && cString::getStringLength($imageId) > 0) {
                $image = $this->_extractImage($imageId);
                if (cString::getStringLength($image['src']) > 0) {
                    $template->set('d', 'IMAGE', $image['element']);
                    $template->set('d', 'IMAGE_SRC', $image['src']);
                    $cApiUploadMeta->loadByMany(array('idlang' => cRegistry::getArticleLanguageId(), 'idupl' => $imageId));
                    if($cApiUploadMeta->isLoaded()) {
                        $template->set('d', 'IMAGE_MEDIANAME', $cApiUploadMeta->get('medianame'));
                    } else {
                        $template->set('d', 'IMAGE_MEDIANAME', '');
                    }
                } else {
                    $template->set('d', 'IMAGE', '');
                    $template->set('d', 'IMAGE_SRC', '');
                    $template->set('d', 'IMAGE_MEDIANAME', '');
                }
            } else {
                $template->set('d', 'IMAGE_SRC', '');
                $template->set('d', 'IMAGE', '');
                $template->set('d', 'IMAGE_MEDIANAME', '');
            }

            // strip all tags from manual teaser date
            $date = strip_tags($date);

            // set generated values to teaser template
            $template->set('d', 'TITLE', $title);
            $template->set('d', 'TEXT', $text);

            $template->set('d', 'IDART', $idArt);
            $template->set('d', 'ART_URL', 'front_content.php?idart=' . $idArt);
            $template->set('d', 'PUBLISHED', $published);
            $template->set('d', 'PUBLISHED_MANUAL', $date);
            foreach ($afields as $field => $value) {
                $template->set('d', strtoupper($field), $value);
            }

            if ($date != '') {
                $template->set('d', 'PUBLISHED_COMBINED', $date);
            } else {
                $template->set('d', 'PUBLISHED_COMBINED', $published);
            }

            foreach (self::$_translations as $translationString) {
                $template->set('d', $translationString, mi18n($translationString));
            }

            if ($this->iteration == 0) {
                $template->set('d', 'ACTIVE', 'active');
            } else {
                $template->set('d', 'ACTIVE', '');
            }
            $this->iteration++;

            $template->next();
        }

        return true;
    }

    /**
     * Teaser allows to get a list of ids in which article content is searched
     * in article like 1,2,5,6 the result with largest character count is
     * returned
     *
     * @param cApiArticleLanguage $article
     *         CONTENIDO article object
     * @param string              $contentTypeName
     *         Name of Content type to extract informations from
     * @param string              $ids
     *         list of ids to search in
     *
     * @return string
     *         largest result of content
     *
     * @throws cDbException
     */
    private function _getArtContent(cApiArticleLanguage &$article, $contentTypeName, $ids) {
        $this->_initCmsTypes();

        $return = '';
        // split ids, if there is only one id, array has only one place filled, that is also ok
        foreach (explode(',', $ids) as $currentId) {
            if (!empty($this->_forwardTypes[$contentTypeName])) {
                $contentTypeName = $this->_forwardTypes[$contentTypeName];
            }
            $return .= ' ' . $article->getContent($contentTypeName, $currentId);
        }

        return $return;
    }

    /**
     * When a HTML Code is given for a Teaser image try to find a image in this
     * code and generate teaser image on that basis.
     *
     * @param string $content
     *         HTML string to search image in
     *
     * @return array
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function _extractImage($content) {
        $image = array();

        // search an image tag
        $regEx = "/<img[^>]*?>.*?/i";

        $match = array();
        preg_match($regEx, $content, $match);

        // if found extract its src content
        $regEx = "/(src)(=)(['\"]?)([^\"']*)(['\"]?)/i";
        $img = array();
        preg_match($regEx, $match[0], $img);

        // check if this image lies in upload folder
        $pos = cString::findLastPos($img[4], $this->_cfgClient[$this->_client]['upload']);
        if (!is_bool($pos)) {
            // if it is generate full internal path to image and scale it for
            // display using class internal function getImage()
            $file = $this->_cfgClient[$this->_client]['path']['frontend'] . $img[4];
            $image = $this->_getImage($file, $this->_settings['teaser_image_width'], $this->_settings['teaser_image_height'], $this->_settings['teaser_image_crop'], true);
        }

        return $image;
    }

    /**
     * Function gets path to an image of base of idupload in CONTENIDO,
     * scales this image on basis of teaser settings and returns path to
     * scaled image.
     *
     * It is also possible to give path to image directly, in this case set
     * fourth parameter to true.
     *
     * @param int  $image
     *                     idupl of image to use for teaser
     * @param int  $maxX
     *                     maximum image width
     * @param int  $maxY
     *                     maximum image height
     * @param      $cropped
     * @param bool $isFile [optional]
     *                     in case of a direct file path retrival from database is not needed
     *
     * @return array
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function _getImage($image, $maxX, $maxY, $cropped, $isFile = false) {
        $return = array();

        if ($cropped == 'true') {
            $cropped = true;
        } else {
            $cropped = false;
        }

        // check if there is a need to get image path
        if ($isFile == false) {
            $upload = new cApiUpload($image);
            $dirname = $upload->get('dirname');
            $filename = $upload->get('filename');
            if (!empty($filename)) {
                $teaserImage = $this->_cfgClient[$this->_client]['path']['frontend'] . 'upload/' . $dirname . $filename;
            } else {
                $teaserImage = '';
            }
        } else {
            $teaserImage = $image;
        }

        // scale image if exists and return it
        if (file_exists($teaserImage)) {
            // Scale Image using cApiImgScale
            $imgSrc = cApiImgScale($teaserImage, $maxX, $maxY, $cropped);

            if ($this->_useXHTML == 'true') {
                $letter = ' /';
            } else {
                $letter = '';
            }

            // Put Image into the teasertext
            $content = '<img alt="" src="' . $imgSrc . '" class="teaser_image"' . $letter . '>';
        } else {
            $imgSrc = '';
            $content = '';
        }

        $return['element'] = $content;
        $return['src'] = $imgSrc;

        return $return;
    }

    /**
     * Generates the code which should be shown if this content type is edited.
     *
     * @return string
     *         escaped HTML code which should be shown if content type is edited
     * 
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function generateEditCode() {
        $this->_initCmsTypes();

        $template = new cTemplate();
        // Set some values into javascript for a better handling
        $template->set('s', 'ID', $this->_id);
        $template->set('s', 'IDARTLANG', $this->_idArtLang);
        $template->set('s', 'FIELDS', "'" . implode("','", $this->_formFields) . "'");

        $templateTabs = new cTemplate();
        $templateTabs->set('s', 'PREFIX', static::PREFIX);

        // create code for general tab
        $templateTabs->set('d', 'TAB_ID', 'general');
        $templateTabs->set('d', 'TAB_CLASS', 'general');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabGeneral());
        $templateTabs->next();

        // create code for advanced tab
        $templateTabs->set('d', 'TAB_ID', 'advanced');
        $templateTabs->set('d', 'TAB_CLASS', 'advanced');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabAdvanced());
        $templateTabs->next();

        // create code for manual tab
        $templateTabs->set('d', 'TAB_ID', 'manual');
        $templateTabs->set('d', 'TAB_CLASS', 'manual');
        $templateTabs->set('d', 'TAB_CONTENT', $this->_generateTabManual());
        $templateTabs->next();

        $codeTabs = $templateTabs->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_tabs.html', true);

        // construct the top code of the template
        $templateTop = new cTemplate();
        $templateTop->set('s', 'ICON', 'images/isstart0.gif');
        $templateTop->set('s', 'ID', $this->_id);
        $templateTop->set('s', 'PREFIX', static::PREFIX);
        $templateTop->set('s', 'HEADLINE', i18n('Teaser settings'));
        $codeTop = $templateTop->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_top.html', true);

        // define the available tabs
        $tabMenu = array(
            'general' => i18n('Automatic'),
            'advanced' => i18n('Manual'),
            'manual' => i18n('Settings')
        );

        // construct the bottom code of the template
        $templateBottom = new cTemplate();
        $templateBottom->set('s', 'PATH_FRONTEND', $this->_cfgClient[$this->_client]['path']['htmlpath']);
        $templateBottom->set('s', 'ID', $this->_id);
        $templateBottom->set('s', 'PREFIX', static::PREFIX);
        $templateBottom->set('s', 'IDARTLANG', $this->_idArtLang);
        $templateBottom->set('s', 'FIELDS', "'" . implode("','", $this->_formFields) . "'");
        $templateBottom->set('s', 'SETTINGS', json_encode($this->_settings));
        $templateBottom->set('s', 'JS_CLASS_SCRIPT', $this->_cfg['path']['contenido_fullhtml'] . 'scripts/content_types/cmsTeaser.js');
        $templateBottom->set('s', 'JS_CLASS_NAME', 'Con.cContentTypeTeaser');
        $codeBottom = $templateBottom->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_abstract_tabbed_edit_bottom.html', true);

        // construct the whole template code
        $code = $this->generateViewCode();
        $code .= $this->_encodeForOutput($codeTop);
        $code .= $this->_generateTabMenuCode($tabMenu);
        $code .= $this->_encodeForOutput($codeTabs);
        $code .= $this->_generateActionCode();
        $code .= $this->_encodeForOutput($codeBottom);

        return $code;
    }

    /**
     * Gets all currenty avariable content types and their ids
     * from database and store it into class variable aCMSTypes.
     * Because this information is used multiple times, this causes a better
     * performance than gettting it seperately
     *
     * @throws cDbException
     */
    private function _initCmsTypes() {
        if (!empty($this->_cmsTypes)) {
            return;
        }

        $this->_cmsTypes = array();

        $sql = 'SELECT * FROM ' . $this->_cfg['tab']['type'] . ' ORDER BY type';
        $db = cRegistry::getDb();
        $db->query($sql);
        while ($db->nextRecord()) {
            // we do not want certain content types
            if (in_array($db->f('type'), $this->_ignoreTypes)) {
                continue;
            }
            $this->_cmsTypes[$db->f('idtype')] = $db->f('type');
        }
    }

    /**
     * Generates code for the general tab in which various settings can be made.
     *
     * @return string
     *         the code for the general tab
     * @throws cDbException
     * @throws cException
     */
    private function _generateTabGeneral() {
        // define a wrapper which contains the whole content of the general tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        // $wrapperContent[] = new cHTMLParagraph(i18n('General settings'), 'head_sub');
        $wrapperContent[] = new cHTMLLabel(i18n('Teaser title'), 'teaser_title_' . $this->_id);
        $wrapperContent[] = new cHTMLTextbox('teaser_title_' . $this->_id, conHtmlSpecialChars($this->_settings['teaser_title']), '', '', 'teaser_title_' . $this->_id);
        $wrapperContent[] = new cHTMLLabel(i18n('Source category'), 'teaser_category_' . $this->_id);
        $wrapperContent[] = buildCategorySelect('teaser_category_' . $this->_id, $this->_settings['teaser_category'], 0);
        $wrapperContent[] = new cHTMLLabel(i18n('Number of articles'), 'teaser_count_' . $this->_id);
        $wrapperContent[] = new cHTMLTextbox('teaser_count_' . $this->_id, (int) $this->_settings['teaser_count'], '', '', 'teaser_count_' . $this->_id);

        $wrapperContent[] = new cHTMLLabel(i18n("Include start article"), 'teaser_start_' . $this->_id);
        $wrapperContent[] = new cHTMLCheckbox('teaser_start_' . $this->_id, '', 'teaser_start_' . $this->_id, ($this->_settings['teaser_start'] == 'true'));

        $wrapperContent[] = new cHTMLLabel(i18n("Teaser sort"), 'teaser_sort_' . $this->_id);
        $wrapperContent[] = $this->_generateSortSelect();
        $wrapperContent[] = new cHTMLLabel(i18n("Sort order"), 'teaser_sort_order_' . $this->_id);
        $wrapperContent[] = $this->_generateSortOrderSelect();

        $wrapper->setContent($wrapperContent);
        return $wrapper->render();
    }

    /**
     * Generats a select box for setting teaser style.
     *
     * Currently four default teaser templates are supported but any number of
     * user templates can be defined as settings of type "cms_teaser" having a
     * label as name and a filename as value.
     *
     * The default templates are:
     * - Slider style (cms_teaser_slider.html)
     * - Image style (cms_teaser_image.html)
     * - Text style (cms_teaser_text.html)
     * - Blog style (cms_teaser_blog.html)
     *
     * @return string
     *         html string of select box
     * @throws cDbException
     * @throws cException
     */
    private function _generateStyleSelect() {
        $htmlSelect = new cHTMLSelectElement('teaser_style_' . $this->_id, '', 'teaser_style_' . $this->_id);

        // set please chose option element
        $htmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        // set other avariable options manually
        $htmlSelectOption = new cHTMLOptionElement(i18n("Slider style"), 'cms_teaser_slider.html', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelectOption = new cHTMLOptionElement(i18n("Image style"), 'cms_teaser_image.html', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelectOption = new cHTMLOptionElement(i18n("Text style"), 'cms_teaser_text.html', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelectOption = new cHTMLOptionElement(i18n("Blog style"), 'cms_teaser_blog.html', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $additionalOptions = getEffectiveSettingsByType('cms_teaser');
        foreach ($additionalOptions as $sLabel => $sTemplate) {
            $htmlSelectOption = new cHTMLOptionElement($sLabel, $sTemplate, false);
            $htmlSelect->appendOptionElement($htmlSelectOption);
        }

        // set default value
        $htmlSelect->setDefault($this->_settings['teaser_style']);

        return $htmlSelect->render();
    }

    /**
     * Teaser gets informations from other articles and their content typs.
     *
     * Function builds a select box in which coresponding cms type can be
     * selected after that a text box is rendered for setting id for this
     * content type to get informations from.
     *
     * This function is used three times for source defintion of headline,
     * text and teaserimage.
     *
     * @param string $selectName
     *         name of input elements
     * @param string $selected
     *         value of select box which is selected
     * @param string $value
     *         current value of text box
     *
     * @return string
     *         html string of select box
     * @throws cException
     */
    private function _generateTypeSelect($selectName, $selected, $value) {
        // make sure that the ID is at the end of the form field name
        $inputName = str_replace('_' . $this->_id, '_count_' . $this->_id, $selectName);
        // generate textbox for content type id
        $htmlInput = new cHTMLTextbox($inputName, $value, '', '', $inputName, false, '', '', 'teaser_type_count');

        // generate content type select
        $htmlSelect = new cHTMLSelectElement($selectName, '', $selectName);
        $htmlSelect->setClass('teaser_type_select');

        $htmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
        $htmlSelect->addOptionElement(0, $htmlSelectOption);

        // use $this->_cmsTypes as basis for this select box which contains all avariable content types in system
        foreach ($this->_cmsTypes as $key => $value) {
            $htmlSelectOption = new cHTMLOptionElement($value, $value, false);
            $htmlSelect->addOptionElement($key, $htmlSelectOption);
        }

        // set default value
        $htmlSelect->setDefault($selected);

        return $htmlSelect->render() . $htmlInput->render();
    }

    /**
     * Generates code for the advanced tab in which various advanced settings
     * can be made.
     *
     * @return string
     *         the code for the advanced tab
     * 
     * @throws cDbException
     * @throws cException
     */
    private function _generateTabAdvanced() {
        // define a wrapper which contains the whole content of the advanced tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        // $wrapperContent[] = new cHTMLParagraph(i18n('Manual teaser settings'), 'head_sub');
        $wrapperContent[] = new cHTMLLabel(i18n('Manual teaser'), 'teaser_manual_' . $this->_id);
        $wrapperContent[] = new cHTMLCheckbox('teaser_manual_' . $this->_id, '', 'teaser_manual_' . $this->_id, ($this->_settings['teaser_manual'] == 'true'));

        // $wrapperContent[] = new cHTMLParagraph(i18n('Add article'), 'head_sub');
        $wrapperContent[] = new cHTMLLabel(i18n('Category'), 'teaser_cat_' . $this->_id);
        $wrapperContent[] = buildCategorySelect('teaser_cat_' . $this->_id, 0, 0);
        $wrapperContent[] = new cHTMLLabel(i18n('Article'), 'teaser_art_' . $this->_id);
        $wrapperContent[] = buildArticleSelect('teaser_art_' . $this->_id, 0, 0);

        $wrapperContent[] = new cHTMLLabel(i18n('Add'), 'add_art_' . $this->_id);
        $image = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . 'images/but_art_new.gif');
        $image->setAttribute('id', 'add_art_' . $this->_id);
        $image->appendStyleDefinition('cursor', 'pointer');
        $wrapperContent[] = $image;

        $wrapperContent[] = new cHTMLParagraph(i18n('Included articles'), 'head_sub');
        $selectElement = new cHTMLSelectElement('teaser_manual_art_' . $this->_id, '', 'teaser_manual_art_' . $this->_id, false, '', '', 'manual');
        $selectElement->setAttribute('size', '4');
        $selectElement->setAttribute('multiple', 'multiple');
        // there can be one or multiple selected articles
        if (is_array($this->_settings['teaser_manual_art'])) {
            foreach ($this->_settings['teaser_manual_art'] as $index => $idArt) {
                $option = new cHTMLOptionElement($this->_getArtName($idArt), $idArt, true);
                $selectElement->addOptionElement($index, $option);
            }
        } else {
            // check if the article really exists
            $artName = $this->_getArtName($this->_settings['teaser_manual_art']);
            if ($artName != i18n('Unknown article')) {
                $option = new cHTMLOptionElement($artName, $this->_settings['teaser_manual_art'], true);
                $selectElement->addOptionElement(0, $option);
            }
        }
        $wrapperContent[] = $selectElement;

        $wrapperContent[] = new cHTMLLabel(i18n("Delete"), 'del_art_' . $this->_id);
        $image = new cHTMLImage($this->_cfg['path']['contenido_fullhtml'] . 'images/delete.gif');
        $image->setAttribute('id', 'del_art_' . $this->_id);
        $image->appendStyleDefinition('cursor', 'pointer');
        $wrapperContent[] = $image;

        $wrapper->setContent($wrapperContent);
        return $wrapper->render();
    }

    /**
     * Function which generated a select box for setting teaser sort argument.
     *
     * @return string
     *         html string of select box
     * @throws cException
     */
    private function _generateSortSelect() {
        $htmlSelect = new cHTMLSelectElement('teaser_sort_' . $this->_id, '', 'teaser_sort_' . $this->_id);

        // set please chose option element
        $htmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        // set other avariable options manually
        $htmlSelectOption = new cHTMLOptionElement(i18n("Sort sequence"), 'sortsequence', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelectOption = new cHTMLOptionElement(i18n("Creation date"), 'creationdate', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelectOption = new cHTMLOptionElement(i18n("Published date"), 'publisheddate', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelectOption = new cHTMLOptionElement(i18n("Modification date"), 'modificationdate', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        // set default value
        $htmlSelect->setDefault($this->_settings['teaser_sort']);

        return $htmlSelect->render();
    }

    /**
     * Function which generated a select box for setting teaser sort order argument.
     *
     * @return string
     *         html string of select box
     * @throws cException
     */
    private function _generateSortOrderSelect() {
        $htmlSelect = new cHTMLSelectElement('teaser_sort_order_' . $this->_id, '', 'teaser_sort_order_' . $this->_id);

        // set please chose option element
        $htmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        // set other avariable options manually
        $htmlSelectOption = new cHTMLOptionElement(i18n("Ascending"), 'asc', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelectOption = new cHTMLOptionElement(i18n("Descending"), 'desc', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        // set default value
        $htmlSelect->setDefault($this->_settings['teaser_sort_order']);

        return $htmlSelect->render();
    }

    /**
     * Function which provides select option for cropping teaser images.
     *
     * @return string
     *         html string of select box
     * @throws cException
     */
    private function _generateCropSelect() {
        $htmlSelect = new cHTMLSelectElement('teaser_image_crop_' . $this->_id, '', 'teaser_image_crop_' . $this->_id);

        // set please chose option element
        $htmlSelectOption = new cHTMLOptionElement(i18n("Please choose"), '', true);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        // set other avariable options manually
        $htmlSelectOption = new cHTMLOptionElement(i18n("Scaled"), 'false', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        $htmlSelectOption = new cHTMLOptionElement(i18n("Cropped"), 'true', false);
        $htmlSelect->appendOptionElement($htmlSelectOption);

        // set default value
        $htmlSelect->setDefault($this->_settings['teaser_image_crop']);

        return $htmlSelect->render();
    }

    /**
     * Generates code for the manual tab in which various settings for the
     * manual teaser can be made.
     *
     * @return string
     *         the code for the manual tab
     * @throws cDbException
     * @throws cException
     */
    private function _generateTabManual() {
        // define a wrapper which contains the whole content of the manual tab
        $wrapper = new cHTMLDiv();
        $wrapperContent = array();

        $wrapperContent[] = new cHTMLParagraph(i18n("Content visualisation"), 'head_sub');
        $wrapperContent[] = new cHTMLLabel(i18n("Teaser visualisation"), 'teaser_style');
        $wrapperContent[] = $this->_generateStyleSelect();
        $wrapperContent[] = new cHTMLLabel(i18n("Teaser filter"), 'teaser_filter_' . $this->_id);
        $wrapperContent[] = new cHTMLTextbox('teaser_filter_' . $this->_id, $this->_settings['teaser_filter'], '', '', 'teaser_filter_' . $this->_id);
        $wrapperContent[] = new cHTMLLabel(i18n('Character length'), 'teaser_character_limit_' . $this->_id);
        $wrapperContent[] = new cHTMLTextbox('teaser_character_limit_' . $this->_id, $this->_settings['teaser_character_limit'], '', '', 'teaser_character_limit_' . $this->_id);

        $wrapperContent[] = new cHTMLParagraph(i18n("Pictures"), 'head_sub');
        $wrapperContent[] = new cHTMLLabel(i18n('Image width'), 'teaser_image_width_' . $this->_id);
        $wrapperContent[] = new cHTMLTextbox('teaser_image_width_' . $this->_id, $this->_settings['teaser_image_width'], '', '', 'teaser_image_width_' . $this->_id);
        $wrapperContent[] = new cHTMLLabel(i18n('Image height'), 'teaser_image_height_' . $this->_id);
        $wrapperContent[] = new cHTMLTextbox('teaser_image_height_' . $this->_id, $this->_settings['teaser_image_height'], '', '', 'teaser_image_height_' . $this->_id);
        $wrapperContent[] = new cHTMLLabel(i18n('Image scale'), 'teaser_image_crop_' . $this->_id);
        $wrapperContent[] = $this->_generateCropSelect();

        $wrapperContent[] = new cHTMLParagraph(i18n("Content types"), 'head_sub');
        $wrapperContent[] = new cHTMLLabel(i18n("Headline source"), 'teaser_source_head_' . $this->_id);
        $wrapperContent[] = $this->_generateTypeSelect('teaser_source_head_' . $this->_id, $this->_settings['teaser_source_head'], $this->_settings['teaser_source_head_count']);
        $wrapperContent[] = new cHTMLLabel(i18n("Text source"), 'teaser_source_text_' . $this->_id);
        $wrapperContent[] = $this->_generateTypeSelect('teaser_source_text_' . $this->_id, $this->_settings['teaser_source_text'], $this->_settings['teaser_source_text_count']);
        $wrapperContent[] = new cHTMLLabel(i18n('Image source'), 'teaser_source_image_' . $this->_id);
        $wrapperContent[] = $this->_generateTypeSelect('teaser_source_image_' . $this->_id, $this->_settings['teaser_source_image'], $this->_settings['teaser_source_image_count']);
        $wrapperContent[] = new cHTMLLabel(i18n('Date source'), 'teaser_source_date_' . $this->_id);
        $wrapperContent[] = $this->_generateTypeSelect('teaser_source_date_' . $this->_id, $this->_settings['teaser_source_date'], $this->_settings['teaser_source_date_count']);

        $wrapper->setContent($wrapperContent);
        return $wrapper->render();
    }

    /**
     * Function retrives name of an article by its id from database.
     *
     * @param int $idArt
     *         CONTENIDO article id
     *
     * @return string
     *         name of article
     * 
     * @throws cDbException
     * @throws cException
     */
    private function _getArtName($idArt) {
        $article = new cApiArticleLanguage();
        $article->loadByArticleAndLanguageId((int) $idArt, $this->_lang);

        $title = $article->get('title');
        if ($article->isLoaded() && !empty($title)) {
            return $article->get('title');
        } else {
            return i18n('Unknown article');
        }
    }

}
