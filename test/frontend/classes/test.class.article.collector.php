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

        $this->_aColl = new cArticleCollector();
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

    /**
     * check member after constructor call
     */
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

        $ar = array();
        $this->_aColl = new cArticleCollector(array());
        $this->assertSame($ar, PHPUnit_Framework_Assert::readAttribute($this->_aColl, '_options'));

        $ar = array();
        $ar['start'] = false;
        $ar['categories'] = array();
        $ar['lang'] = cRegistry::getLanguageId();
        $ar['client'] = cRegistry::getClientId();
        $ar['startonly'] = false;
        $ar['offline'] = false;
        $ar['offlineonly'] = false;
        $ar['order'] = 'created';
        $ar['artspecs'] = array();
        $ar['direction'] = 'DESC';
        $ar['limit'] = 0;

        $this->_aColl = new cArticleCollector(array(
            'start' => false
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
    }

    /**
     * test next article
     */
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

    /**
     * set result page
     */
    public function testSetResultPerPage() {
        $this->_aColl = new cArticleCollector(array(
            'start' => true
        ));
        $this->assertSame(51, $this->_aColl->count());
        $this->_aColl->setResultPerPage(10);
        $this->_aColl->setPage(2);
        $this->_aColl->setResultPerPage(0);
    }

    /**
     * check valid article entries
     */
    public function testValid() {
        $this->_aColl = new cArticleCollector(array(
            'start' => true
        ));
        $this->assertSame(51, $this->_aColl->count());
        $this->assertSame(true, $this->_aColl->valid());
        $this->_aColl = new cArticleCollector(array());
        $this->assertSame(false, $this->_aColl->valid());
    }

    /**
     * check loaded articles
     */
    public function testLoadArticles() {

        // articles including start articles
        $this->_db->query('SELECT * FROM con_art_lang_test WHERE idlang = 1 AND online = 1');
        $ret = $this->_db->affectedRows();
        $this->_aColl = new cArticleCollector(array(
            'start' => true
        ));

        // articles without start articles
        $this->assertSame($ret, $this->_aColl->count());
        $this->_db->query('SELECT * FROM con_art_lang_test WHERE idlang = 1 AND online = 1 AND idartlang NOT IN (SELECT startidartlang FROM con_cat_lang_test WHERE startidartlang>0)');
        $ret = $this->_db->affectedRows();
        $this->_aColl = new cArticleCollector(array(
            'start' => false
        ));
        $this->assertSame($ret, $this->_aColl->count());

        // offline articles
        $this->_db->query('SELECT * FROM con_art_lang_test WHERE idlang = 1 AND online = 0');
        $ret = $this->_db->affectedRows();

        $ret = $this->_db->affectedRows();
        $this->_aColl = new cArticleCollector(array(
            'offlineonly' => true
        ));

        $this->assertSame($ret, $this->_aColl->count());
    }

    /**
     * call seek over article size
     * @expectedException cOutOfBoundsException
     */
    public function testSeek() {
        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10,
            'start' => true
        ));
        $this->assertSame(1, $this->_aColl->count());

        var_dump($this->_aColl->seek(3));
    }

    /**
     * rewind position
     */
    public function testRewind() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * get start article out of given categorie
     */
    public function testStartArticle() {
        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10
        ));

        $this->assertSame('31', $this->_aColl->startArticle()->get('idartlang'));
        $this->assertSame(0, $this->_aColl->count());

        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10
        ));

        $this->assertSame('31', $this->_aColl->startArticle()->get('idartlang'));
    }

    /**
     * check iterations and valid entries
     */
    public function testKey() {
        $this->_aColl = new cArticleCollector(array(
            'idcat' => 13,
            'start' => true
        ));
        $this->assertSame(4, $this->_aColl->count());

        $this->assertSame(0, $this->_aColl->key());
        $this->_aColl->next();
        $this->assertSame(1, $this->_aColl->key());
        $this->_aColl->next();
        $this->assertSame(2, $this->_aColl->key());
        $this->_aColl->next();
        $this->assertSame(3, $this->_aColl->key());
        $this->_aColl->next();
        $this->assertSame(4, $this->_aColl->key());
        // check more iterations over found article limit
        $this->assertSame(NULL, $this->_aColl->next());
        $this->assertSame(5, $this->_aColl->key());
        for ($i = 6; $i < 100; $i++) {
            $this->_aColl->next();
            $this->assertSame($i, $this->_aColl->key());
            $this->assertSame(false, $this->_aColl->valid());
        }
    }

}
?>