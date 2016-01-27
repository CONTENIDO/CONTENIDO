<?PHP
/**
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * idcat 13: teaser category with 4 articles
 */
class cArticleCollectorTest extends cTestingTestCase {

    /**
     * Default options that are set when no other options are given.
     *
     * @var array
     */
    private $_defaultOptions;

    /**
     *
     * @var cDb
     */
    protected $_db = null;

    /**
     *
     * @var cArticleCollector
     */
    protected $_aColl = null;

    /**
     */
    public function setUp() {

        // default options that are set
        $this->_defaultOptions = array(
            'categories' => array(),
            'lang' => '1',
            'client' => '1',
            'start' => false,
            'startonly' => false,
            'offline' => false,
            'offlineonly' => false,
            'order' => 'created',
            'artspecs' => array(),
            'direction' => 'DESC',
            'limit' => 0
        );

        $this->_db = cRegistry::getDb();

        $sqlStatements = array();
        $sqlStatements = array_merge($sqlStatements, $this->_fetchSqlFileContent('art'));
        $sqlStatements = array_merge($sqlStatements, $this->_fetchSqlFileContent('art_lang'));
        $sqlStatements = array_merge($sqlStatements, $this->_fetchSqlFileContent('cat'));
        $sqlStatements = array_merge($sqlStatements, $this->_fetchSqlFileContent('cat_art'));
        $sqlStatements = array_merge($sqlStatements, $this->_fetchSqlFileContent('cat_lang'));

        foreach ($sqlStatements as $sqlStatement) {
            $this->_db->query($sqlStatement);
        }

        $this->_aColl = new cArticleCollector();
    }

    /**
     */
    public function tearDown() {
        // No need for tear down
    }

    /**
     * check member after constructor call
     */
    public function testConstruct() {

        // test empty options
        $this->_aColl = new cArticleCollector(array());
        $ar = array();
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // test option idcat
        $this->_aColl = new cArticleCollector(array(
            'idcat' => 10
        ));
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
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // test option start
        $this->_aColl = new cArticleCollector(array(
            'start' => false
        ));
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
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // test options idcat & limit
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
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // test options idcat, limit & start
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
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // test options idcat, limit, start & startonly
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
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // test options idcat, limit, start, startonly & offline
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
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // test options idcat, limit, start, startonly, offline & offlineonly
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
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // test options idcat, limit, start, startonly, offline, offlineonly &
        // direction
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
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // test options idcat, limit, start, startonly, offline, offlineonly,
        // direction & order (publisheddate)
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
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // test options idcat, limit, start, startonly, offline, offlineonly,
        // direction & order (sortsequence)
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
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // test options idcat, limit, start, startonly, offline, offlineonly,
        // direction & order (modificationdate)
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
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // test options idcat, limit, start, startonly, offline, offlineonly,
        // direction & order (creationdate)
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
        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));

        // ------check order
        $this->assertSame(1, $this->_aColl->count());

        $this->_aColl->nextArticle();
        $this->assertSame(false, $this->_aColl->nextArticle());
    }

    /**
     * test empty options
     */
    public function testSetOptionsEmpty() {
        $act = array();
        $exp = array_merge($this->_defaultOptions, array());
        $this->_aColl->setOptions($act);
        $this->assertSame($exp, $this->_readAttribute($this->_aColl, '_options'));
    }

    /**
     * test option idcat
     */
    public function testSetOptionsIdcat() {
        $act = array(
            'idcat' => 1
        );
        $exp = array_merge($this->_defaultOptions, array(
            'idcat' => 1,
            'categories' => array(
                1
            )
        ));
        $this->_aColl->setOptions($act);
        $diff = array_diff($exp, $this->_readAttribute($this->_aColl, '_options'));
        $this->assertEmpty($diff);
    }

    /**
     * test option categories
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsCategories() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option lang
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsLang() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option client
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsClient() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option start
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsStart() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option startonly
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsStartonly() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option offline
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsOffline() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option offlineonly
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsOfflineonly() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option order
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsOrder() {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // case 'sortsequence':
        // $options['order'] = 'artsort';
        // case 'modificationdate':
        // $options['order'] = 'lastmodified';
        // case 'publisheddate':
        // $options['order'] = 'published';
        // case 'creationdate':
        // default:
        // $options['order'] = 'created';
    }

    /**
     * test option artspecs
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsArtspecs() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option direction
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsDirection() {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option limit
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsLimit() {
        $this->markTestIncomplete('This test has not been implemented yet.');
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
     * get start article out of given categorie
     * @expectedException cBadMethodCallException
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

        $this->_aColl = new cArticleCollector(array());

        $this->_aColl->startArticle();
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

        $this->assertSame($ar, $this->_readAttribute($this->_aColl, '_options'));
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
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetPage() {
        $this->markTestIncomplete('This test has not been implemented yet.');
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
    }

    /**
     * rewind position to position 0
     */
    public function testRewind() {
        // idcat 13 Teaser cat with 4 articles
        $this->_aColl = new cArticleCollector(array(
            'idcat' => 13,
            'start' => true
        ));
        $this->assertSame(4, $this->_aColl->count());

        $this->assertSame(0, $this->_aColl->key());
        $this->_aColl->next();
        $this->assertSame(1, $this->_aColl->key());
        $this->_aColl->rewind();
        $this->assertSame(0, $this->_aColl->key());
    }

    /**
     *
     * @todo This test has not been implemented yet.
     */
    public function testCurrent() {
        $this->markTestIncomplete('This test has not been implemented yet.');
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

    /**
     * Test method "next" of the iterator implamentation.
     */
    public function testNext() {
        $this->assertSame(0, $this->_readAttribute($this->_aColl, '_currentPosition'));
        $this->_aColl->next();
        $this->assertSame(1, $this->_readAttribute($this->_aColl, '_currentPosition'));
    }

    /**
     * Test valid article entries
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
     * Test count of empty collector.
     */
    public function testCountEmpty() {
        $this->assertSame(0, $this->_aColl->count());
    }

    /**
     *
     * @todo This test has not been implemented yet.
     */
    public function testCount() {
        $this->_aColl->loadArticles();
        $this->assertSame(0, $this->_aColl->count());
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

}

?>