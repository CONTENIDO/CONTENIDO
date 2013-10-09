<?PHP
/**
 *
 * @version SVN Revision $Rev:$
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
require_once 'sqlStatements.php';
class cArticleCollectorTest extends PHPUnit_Framework_TestCase {

    protected $_db = null;

    protected $_aColl = null;

    public function setUp() {
        $this->_db = cRegistry::getDb();
        $sql = SqlStatement::getDeleteStatement(array(
            'con_art_lang_test',
            'con_cat_art_test',
            'con_cat_lang_test',
            'con_cat_test',
            'con_art_test'
        ));
        $this->_db->query($sql);
        global $cfg;
        $cfg['tab']['art_lang'] = 'con_art_lang_test';
        $cfg['tab']['cat_art'] = 'con_cat_art_test';
        $cfg['tab']['cat_lang'] = 'con_cat_lang_test';
        $cfg['tab']['cat'] = 'con_cat_test';
        $cfg['tab']['art'] = 'con_art_test';

        $this->_aColl = new cApiArticleCollection();
        $this->_db = cRegistry::getDb();

        // con_art_lang_test
        $this->_db->query(SqlStatement::getCreateConArtLangTest());
        $this->_db->query(SqlStatement::getInsertConArtLangTest());

        // con_cat_art_test
        $this->_db->query(SqlStatement::getCreateConCatArtTest());
        $this->_db->query(SqlStatement::getInsertConCatArtTest());

        // con_cat_lang_test
        $this->_db->query(SqlStatement::getCreateConCatLangTest());
        $this->_db->query(SqlStatement::getInsertConCatLangTest());

        // con_cat_test
        $this->_db->query(SqlStatement::getCreateConCatTest());
        $this->_db->query(SqlStatement::getInsertConCatTest());

        // con_art_test
        $this->_db->query(SqlStatement::getCreateConArtTest());
        $this->_db->query(SqlStatement::getInsertConArtTest());
    }

    public function tearDown() {
        $this->_db = cRegistry::getDb();
        $sql = SqlStatement::getDeleteStatement(array(
            'con_art_lang_test',
            'con_cat_art_test',
            'con_cat_lang_test',
            'con_cat_test',
            'con_art_test'
        ));
        $this->_db->query($sql);
    }

    public function testConstruct() {
        $ar = array();
        $this->_aColl = new cArticleCollector(array());
        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));

        $ar['idcat'] = 10;
        $ar['categories'] = array(
            10
        );
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['start'] = false;
        $ar['startonly'] = false;
        $ar['offline'] = false;
        $ar['offlineonly'] = false;
        $ar['order'] = 'created';
        $ar['artspecs'] = array();
        $ar['direction'] = 'DESC';
        $ar['limit'] = 0;

        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10
        ));
        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));

        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10,
            'limit' => 10
        ));
        $ar = array();
        $ar['idcat'] = 10;
        $ar['limit'] = 10;
        $ar['categories'] = array(
            10
        );
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['start'] = false;
        $ar['startonly'] = false;
        $ar['offline'] = false;
        $ar['offlineonly'] = false;
        $ar['order'] = 'created';
        $ar['artspecs'] = array();
        $ar['direction'] = 'DESC';
        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));

        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10,
            'limit' => 10,
            'start' => true
        ));
        $ar = array();
        $ar['idcat'] = 10;
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['categories'] = array(
            10
        );
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['startonly'] = false;
        $ar['offline'] = false;
        $ar['offlineonly'] = false;
        $ar['order'] = 'created';
        $ar['artspecs'] = array();
        $ar['direction'] = 'DESC';
        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));

        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10,
            'limit' => 10,
            'start' => true,
            'startonly' => true
        ));
        $ar = array();
        $ar['idcat'] = 10;
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['categories'] = array(
            10
        );
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['offline'] = false;
        $ar['offlineonly'] = false;
        $ar['order'] = 'created';
        $ar['artspecs'] = array();
        $ar['direction'] = 'DESC';
        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));

        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10,
            'limit' => 10,
            'start' => true,
            'startonly' => true,
            'offline' => true
        ));
        $ar = array();
        $ar['idcat'] = 10;
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['categories'] = array(
            10
        );
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['offlineonly'] = false;
        $ar['order'] = 'created';
        $ar['artspecs'] = array();
        $ar['direction'] = 'DESC';
        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));

        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10,
            'limit' => 10,
            'start' => true,
            'startonly' => true,
            'offline' => true,
            'offlineonly' => true
        ));
        $ar = array();
        $ar['idcat'] = 10;
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['offlineonly'] = true;
        $ar['categories'] = array(
            10
        );
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['order'] = 'created';
        $ar['artspecs'] = array();
        $ar['direction'] = 'DESC';
        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));

        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10,
            'limit' => 10,
            'start' => true,
            'startonly' => true,
            'offline' => true,
            'offlineonly' => true,
            'direction' => 'ASC'
        ));
        $ar = array();
        $ar['idcat'] = 10;
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['offlineonly'] = true;
        $ar['direction'] = 'ASC';
        $ar['categories'] = array(
            10
        );
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['order'] = 'created';
        $ar['artspecs'] = array();

        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));
        // check order -----
        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10,
            'limit' => 10,
            'start' => true,
            'startonly' => true,
            'offline' => true,
            'offlineonly' => true,
            'direction' => 'ASC',
            'order' => 'publisheddate'
        ));
        $ar = array();
        $ar['idcat'] = 10;
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['offlineonly'] = true;
        $ar['direction'] = 'ASC';
        $ar['order'] = 'published';
        $ar['categories'] = array(
            10
        );
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['artspecs'] = array();

        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));

        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10,
            'limit' => 10,
            'start' => true,
            'startonly' => true,
            'offline' => true,
            'offlineonly' => true,
            'direction' => 'ASC',
            'order' => 'sortsequence'
        ));
        $ar = array();
        $ar['idcat'] = 10;
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['offlineonly'] = true;
        $ar['direction'] = 'ASC';
        $ar['order'] = 'artsort';
        $ar['categories'] = array(
            10
        );
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['artspecs'] = array();

        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));

        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10,
            'limit' => 10,
            'start' => true,
            'startonly' => true,
            'offline' => true,
            'offlineonly' => true,
            'direction' => 'ASC',
            'order' => 'modificationdate'
        ));
        $ar = array();
        $ar['idcat'] = 10;
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['offlineonly'] = true;
        $ar['direction'] = 'ASC';
        $ar['order'] = 'lastmodified';
        $ar['categories'] = array(
            10
        );
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['artspecs'] = array();

        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));

        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10,
            'limit' => 10,
            'start' => true,
            'startonly' => true,
            'offline' => true,
            'offlineonly' => true,
            'direction' => 'ASC',
            'order' => 'creationdate'
        ));
        $ar = array();
        $ar['idcat'] = 10;
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['offlineonly'] = true;
        $ar['direction'] = 'ASC';
        $ar['order'] = 'created';
        $ar['categories'] = array(
            10
        );
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['artspecs'] = array();

        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));
        // ------check order

        $this->assertSame(1, $this->_aColl->count());
        $this->_aColl->nextArticle();
        $this->assertSame(false, $this->_aColl->nextArticle());

        // $this->_db->query('SELECT * FROM con_cat_lang_test WHERE
        // startidartlang=0;');
        // $i = 0;
        // while($this->_db->next_record()){
        // $i++;
        // }

        // var_dump($i);

        // var_dump($this->_aColl->count());
    }

    public function testNextArticle() {
        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10,
            'limit' => 10,
            'start' => true,
            'startonly' => true,
            'offline' => true,
            'offlineonly' => true,
            'direction' => 'ASC',
            'order' => 'creationdate'
        ));
        $ar = array();
        $ar['idcat'] = 10;
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['offlineonly'] = true;
        $ar['direction'] = 'ASC';
        $ar['order'] = 'created';
        $ar['categories'] = array(
            10
        );
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['artspecs'] = array();

        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));
        // ------check order

        $this->assertSame(1, $this->_aColl->count());
        $this->_aColl->nextArticle();
        $this->assertSame(false, $this->_aColl->nextArticle());
    }

}
?>