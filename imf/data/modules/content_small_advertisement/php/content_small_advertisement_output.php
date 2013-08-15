<?PHP
/**
 * description: small_advertisements
 *
 * @package Module
 * @subpackage content_small_advertisement
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
class SmallAdvertisement {

    private $_tpl = NULL;

    private $_dB = NULL;

    private $_subIdcats = NULL;

    private $_categories = NULL;

    private $_idartlang = NULL;

    private $_catName = NULL;

    private $_state = NULL;

    public function __construct() {
        $this->_dB = cRegistry::getDb();
        $this->_tpl = Contenido_SmartyWrapper::getInstance();
        $this->_state = intval(getEffectiveSetting("smalladvertisement", "online_state"));
        $this->receiveData();
        $this->getCategories($this->getSubCategories(6));
        $this->_tpl->assign('categories', $this->_categories);
        $this->render();
    }

    /**
     * This function generates a new article and sets content if the form was
     * submitted.
     */
    protected function receiveData() {
        if (isset($_GET['slist']) && isset($_GET['catlist']) && isset($_GET['text']) && $_GET['text'] != NULL && isset($_GET['name']) && $_GET['name'] != NULL && isset($_GET['email']) && $_GET['email'] != NULL && isset($_GET['title']) && $_GET['title'] != NULL) {
            $this->_setCategoryName();
            $this->newArticle();
            $this->saveContent();
            if($this->_state == 0){
                $this->sendModeratorMail();
            }
        } else {
            $this->_tpl->assign('error', 'Bitte alle mit * gekennzeichneten Felder ausfüllen.');
        }
    }

    protected function _setCategoryName() {
        try {
            $col = new cApiCategoryLanguageCollection();
            $categoryName = $col->getFieldsByWhereClause(array(
                'name'
            ), 'idcat=' . cSecurity::escapeString($_REQUEST['catlist']));
            $this->_catName = $categoryName[0]['name'];
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function sendModeratorMail() {
        try {
            $from = getEffectiveSetting("smalladvertisement", "mailfrom");
            $to = getEffectiveSetting("smalladvertisement", "mailto");
            $subject = 'Neue Kleinanzeige';
            $body = '<p>Eine neue Kleinanzeige mit folgenden Daten wurde erstellt:</p>';
            $body .= '<p>Titel: ' . cSecurity::escapeString($_REQUEST['title']) . '</p>';
            $body .= '<p>Typ: ' . cSecurity::escapeString($_REQUEST['slist']) . '</p>';
            $body .= '<p>Kategorie: ' . $this->_catName . '</p>';
            $body .= '<p>Name: ' . cSecurity::escapeString($_REQUEST['name']) . '</p>';
            $body .= '<p>E-Mail: ' . cSecurity::escapeString($_REQUEST['email']) . '</p>';
            $body .= '<p>Text: ' . cSecurity::escapeString($_REQUEST['text']) . '</p>';
            $body .= '<p>Bitte prüfen Sie die Anzeige im CONTENIDO Backend und geben Sie sie anschließend zur Veröffentlichung frei.</p>'; 
            
            $mailer = new cMailer();

            $mailer->sendMail($from, $to, $subject, $body, null, null, null, false, 'text/html');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    protected function mergeAssoziativ(array $ar, $str) {
        $con = array();
        for ($i = 0; $i < count($ar); $i++) {
            $con[] = $ar[$i][$str];
        }
        return $con;
    }

    /**
     * this function creates a new article
     */
    protected function newArticle() {
        $icat = cSecurity::escapeString($_REQUEST['catlist']);

        $articleName = $_REQUEST['slist'] . '-' . $this->_catName . '-' . $_REQUEST['name'];

        $col = new cApiArticleLanguage(124);

        $timestamp = date("Y-m-d", time()) . ' ' . date("H:i:s", time());

        $idart = conEditFirstTime($icat, array($icat), '', 0, 162, '', $col->get('idlang'), $articleName, NULL, 0, $timestamp, 'sysadmin', 'sysadmin', $this->_state, NULL, NULL, 1);

        $this->_idartlang = $this->getIdartLang($idart);

        $col = new cApiArticleLanguage($this->_idartlang);
        $col->set('idtplcfg', '162');
        // hotfix for seo urls -> use random value to get unique urls
        $col->set('urlname', 'kleinanzeige' . mt_rand());
        $col->store();
    }

    protected function getIdartLang($idart) {
        $con = new cApiArticleLanguageCollection();
        $check = $con->getFieldsByWhereClause(array(
            'idartlang'
        ), 'idart=' . $idart);

        return $check[0]['idartlang'];
    }

    /**
     * this function saves content for an advertisement article
     */
    protected function saveContent() {
        try {
            conSaveContentEntry($this->_idartlang, "CMS_HTMLHEAD", "1", cSecurity::escapeString($_REQUEST['title']));
            conSaveContentEntry($this->_idartlang, "CMS_HTML", "2", cSecurity::escapeString($_REQUEST['slist']));
            conSaveContentEntry($this->_idartlang, "CMS_HTML", "3", cSecurity::escapeString($_REQUEST['name']));
            conSaveContentEntry($this->_idartlang, "CMS_HTML", "4", cSecurity::escapeString($_REQUEST['email']));
            conSaveContentEntry($this->_idartlang, "CMS_HTML", "5", cSecurity::escapeString($_REQUEST['text']));
            if ($this->_state == 0) {
                $this->_tpl->assign('infotext', 'Ihre Kleinanzeige wurde gespeichert. Diese muss nun von einem Administrator freigeschaltet werden.');
            }
            else{
                $this->_tpl->assign('infotext', 'Ihre Kleinanzeige wurde erfolgreich gespeichert.');
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    protected function getCategories(array $idcats) {
        $ret = array();
        $tmpCategories = array();
        foreach ($idcats as $key => $idcat) {

            $col = new cApiCategoryLanguageCollection();
            $tmpCategories[] = $this->mergeAssoziativ($col->getFieldsByWhereClause(array(
                'idcatlang'
            ), 'idcat=' . $idcat), 'idcatlang');
        }

        foreach ($tmpCategories as $key => $category) {

            // filter unwished categories
            if ($category[0] != '64' && $category[0] != '66' && $category[0] != '65' && $category[0] != '94') {

                $col = new cApiCategoryLanguage($category[0]);
                $cat = new AdvertisementCategory($col->getField('idcat'), $col->getField('name'));
                $this->_categories[] = $cat;
            }
        }
    }

    protected function getSubCategories($idcat) {
        $col = new cApiCategoryCollection();
        return $col->getAllChildCategoryIds($idcat);
    }

    protected function render() {
        $this->_tpl->display('get.tpl');
    }

}

$class = new SmallAdvertisement();

/**
 * helper class to store informations in an object
 */
class AdvertisementCategory {

    protected $_category = NULL;

    protected $_name = NULL;

    public function __construct($cat, $name) {
        $this->_category = $cat;
        $this->_name = $name;
    }

    public function getCategory() {
        return $this->_category;
    }

    public function getName() {
        return $this->_name;                                                                                             
    }

}
?>


