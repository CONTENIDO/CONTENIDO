<?php

/**
 * @author     claus.schunk@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * idcat 13: teaser category with 4 articles
 */
class cArticleCollectorTest extends cTestingTestCase
{
    /**
     * Default options that are set when no other options are given.
     *
     * @var array
     */
    private $_defaultOptions;

    /**
     * @var int
     */
    private $_client;

    /**
     * @var int
     */
    private $_lang;

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
     * @throws cDbException
     * @throws cTestingException
     */
    protected function setUp(): void
    {
        $this->_client = cSecurity::toInteger(cRegistry::getClientId());
        $this->_lang = cSecurity::toInteger(cRegistry::getLanguageId());

        // default options that are set
        $this->_defaultOptions = [
            'categories'  => [],
            'lang'        => $this->_lang,
            'client'      => $this->_client,
            'start'       => false,
            'startonly'   => false,
            'offline'     => false,
            'offlineonly' => false,
            'order'       => 'created',
            'artspecs'    => [],
            'direction'   => 'DESC',
            'limit'       => 0,
            'offset'      => 0,
        ];
        ksort($this->_defaultOptions);

        $this->_db = cRegistry::getDb();

        $sqlStatements = [];
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
    protected function tearDown(): void
    {
        // No need for tear down
    }

    /**
     * Returns the sorted _options property from article collector instance.
     * We need to sort the array in order to get a valid assertSame() result.
     *
     * @param cArticleCollector $articleCollector
     * @return mixed
     */
    protected function _getArticleCollectorOptions(cArticleCollector $articleCollector)
    {
        $options =  $this->_readAttribute($articleCollector, '_options');
        ksort($options);
        return $options;
    }

    /**
     * check member after constructor call
     */
    public function testConstruct()
    {
        // test empty options
        $this->_aColl = new cArticleCollector([]);
        $this->assertSame($this->_defaultOptions, $this->_getArticleCollectorOptions($this->_aColl));

        // test option idcat
        $this->_aColl      = new cArticleCollector(
            [
                'idcat' => 10,
            ]
        );

        $ar = $this->_defaultOptions;
        $ar['idcat'] = 10;
        $ar['categories'] = [10];
        ksort($ar);
        $this->assertSame($ar, $this->_getArticleCollectorOptions($this->_aColl));

        // test option start
        $this->_aColl      = new cArticleCollector(
            [
                'start' => false,
            ]
        );
        $ar = $this->_defaultOptions;
        $ar['start'] = false;
        ksort($ar);
        $this->assertSame($ar, $this->_getArticleCollectorOptions($this->_aColl));

        // test options idcat & limit
        $this->_aColl      = new cArticleCollector(
            [
                'idcat' => 10,
                'limit' => 10,
            ]
        );
        $ar = $this->_defaultOptions;
        $ar['idcat'] = 10;
        $ar['categories'] = [10];
        $ar['limit'] = 10;
        ksort($ar);
        $this->assertSame($ar, $this->_getArticleCollectorOptions($this->_aColl));

        // test options idcat, limit & start
        $this->_aColl      = new cArticleCollector(
            [
                'idcat' => 10,
                'limit' => 10,
                'start' => true,
            ]
        );
        $ar = $this->_defaultOptions;
        $ar['idcat'] = 10;
        $ar['categories'] = [10];
        $ar['limit'] = 10;
        $ar['start'] = true;
        ksort($ar);
        $this->assertSame($ar, $this->_getArticleCollectorOptions($this->_aColl));

        // test options idcat, limit, start & startonly
        $this->_aColl      = new cArticleCollector(
            [
                'idcat'     => 10,
                'limit'     => 10,
                'start'     => true,
                'startonly' => true,
            ]
        );
        $ar = $this->_defaultOptions;
        $ar['idcat'] = 10;
        $ar['categories'] = [10];
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        ksort($ar);
        $this->assertSame($ar, $this->_getArticleCollectorOptions($this->_aColl));

        // test options idcat, limit, start, startonly & offline
        $this->_aColl      = new cArticleCollector(
            [
                'idcat'     => 10,
                'limit'     => 10,
                'start'     => true,
                'startonly' => true,
                'offline'   => true,
            ]
        );
        $ar = $this->_defaultOptions;
        $ar['idcat'] = 10;
        $ar['categories'] = [10];
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        ksort($ar);
        $this->assertSame($ar, $this->_getArticleCollectorOptions($this->_aColl));

        // test options idcat, limit, start, startonly, offline & offlineonly
        $this->_aColl      = new cArticleCollector(
            [
                'idcat'       => 10,
                'limit'       => 10,
                'start'       => true,
                'startonly'   => true,
                'offline'     => true,
                'offlineonly' => true,
            ]
        );
        $ar = $this->_defaultOptions;
        $ar['idcat'] = 10;
        $ar['categories'] = [10];
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['offlineonly'] = true;
        ksort($ar);
        $this->assertSame($ar, $this->_getArticleCollectorOptions($this->_aColl));

        // test options idcat, limit, start, startonly, offline, offlineonly &
        // direction
        $this->_aColl      = new cArticleCollector(
            [
                'idcat'       => 10,
                'limit'       => 10,
                'start'       => true,
                'startonly'   => true,
                'offline'     => true,
                'offlineonly' => true,
                'direction'   => 'ASC',
            ]
        );
        $ar = $this->_defaultOptions;
        $ar['idcat'] = 10;
        $ar['categories'] = [10];
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['offlineonly'] = true;
        $ar['direction'] = 'ASC';
        ksort($ar);
        $this->assertSame($ar, $this->_getArticleCollectorOptions($this->_aColl));

        // test options idcat, limit, start, startonly, offline, offlineonly,
        // direction & order (publisheddate)
        $this->_aColl      = new cArticleCollector(
            [
                'idcat'       => 10,
                'limit'       => 10,
                'start'       => true,
                'startonly'   => true,
                'offline'     => true,
                'offlineonly' => true,
                'direction'   => 'ASC',
                'order'       => 'publisheddate',
            ]
        );
        $ar = $this->_defaultOptions;
        $ar['idcat'] = 10;
        $ar['categories'] = [10];
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['offlineonly'] = true;
        $ar['direction'] = 'ASC';
        $ar['order'] = 'published';
        ksort($ar);
        $this->assertSame($ar, $this->_getArticleCollectorOptions($this->_aColl));

        // test options idcat, limit, start, startonly, offline, offlineonly,
        // direction & order (sortsequence)
        $this->_aColl      = new cArticleCollector(
            [
                'idcat'       => 10,
                'limit'       => 10,
                'start'       => true,
                'startonly'   => true,
                'offline'     => true,
                'offlineonly' => true,
                'direction'   => 'ASC',
                'order'       => 'sortsequence',
            ]
        );
        $ar = $this->_defaultOptions;
        $ar['idcat'] = 10;
        $ar['categories'] = [10];
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['offlineonly'] = true;
        $ar['direction'] = 'ASC';
        $ar['order'] = 'artsort';
        ksort($ar);
        $this->assertSame($ar, $this->_getArticleCollectorOptions($this->_aColl));

        // test options idcat, limit, start, startonly, offline, offlineonly,
        // direction & order (modificationdate)
        $this->_aColl      = new cArticleCollector(
            [
                'idcat'       => 10,
                'limit'       => 10,
                'start'       => true,
                'startonly'   => true,
                'offline'     => true,
                'offlineonly' => true,
                'direction'   => 'ASC',
                'order'       => 'modificationdate',
            ]
        );
        $ar = $this->_defaultOptions;
        $ar['idcat'] = 10;
        $ar['categories'] = [10];
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['offline'] = true;
        $ar['offlineonly'] = true;
        $ar['direction'] = 'ASC';
        $ar['order'] = 'lastmodified';
        ksort($ar);
        $this->assertSame($ar, $this->_getArticleCollectorOptions($this->_aColl));

        // test options idcat, limit, start, startonly, offline, offlineonly,
        // direction & order (creationdate)
        $this->_aColl      = new cArticleCollector(
            [
                'idcat'       => 10,
                'limit'       => 10,
                'start'       => true,
                'startonly'   => true,
                'direction'   => 'ASC',
                'order'       => 'creationdate',
            ]
        );
        $ar = $this->_defaultOptions;
        $ar['idcat'] = 10;
        $ar['categories'] = [10];
        $ar['limit'] = 10;
        $ar['start'] = true;
        $ar['startonly'] = true;
        $ar['direction'] = 'ASC';
        $ar['order'] = 'created';
        ksort($ar);
        $this->assertSame($ar, $this->_getArticleCollectorOptions($this->_aColl));
    }

    /**
     * test empty options
     */
    public function testSetOptionsEmpty()
    {
        $act = [];
        $exp = array_merge($this->_defaultOptions, []);
        $this->_aColl->setOptions($act);
        ksort($exp);
        $this->assertSame($exp, $this->_getArticleCollectorOptions($this->_aColl));
    }

    /**
     * test option idcat
     */
    public function testSetOptionsIdcat()
    {
        $act = [
            'idcat' => 1,
        ];
        $exp = array_merge(
            $this->_defaultOptions,
            [
                'idcat'      => 1,
                'categories' => [
                    1,
                ],
            ]
        );
        $this->_aColl->setOptions($act);

        // Sort $exp & $act, and serialize them for comparison, `array_diff` can't handle multidimensional arrays!
        ksort($exp);
        $act = $this->_readAttribute($this->_aColl, '_options');
        ksort($act);
        $diff = strcmp(json_encode($exp), json_encode($act));

        $this->assertEmpty($diff);
    }

    /**
     * test option categories
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsCategories()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option lang
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsLang()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option client
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsClient()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option start
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsStart()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option startonly
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsStartonly()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option offline
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsOffline()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option offlineonly
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsOfflineonly()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option order
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsOrder()
    {
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
    public function testSetOptionsArtspecs()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option direction
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsDirection()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * test option limit
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetOptionsLimit()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * check loaded articles
     *
     * @throws cDbException
     */
    public function testLoadArticles()
    {
        // articles including start articles
        $this->_db->query('SELECT * FROM test_art_lang WHERE idlang = 1 AND online = 1');
        $ret          = $this->_db->affectedRows();
        $this->_aColl = new cArticleCollector(
            [
                'start' => true,
            ]
        );

        // articles without start articles
        $this->assertSame($ret, $this->_aColl->count());
        $this->_db->query(
            'SELECT * FROM test_art_lang WHERE idlang = 1 AND online = 1 AND idartlang NOT IN (SELECT startidartlang FROM test_cat_lang WHERE startidartlang>0)'
        );
        $ret          = $this->_db->affectedRows();
        $this->_aColl = new cArticleCollector(
            [
                'start' => false,
            ]
        );
        $this->assertSame($ret, $this->_aColl->count());

        // offline articles
        $this->_db->query('SELECT * FROM test_art_lang WHERE idlang = 1 AND online = 0');
        $ret = $this->_db->affectedRows();

        $this->_aColl = new cArticleCollector(
            [
                'offlineonly' => true,
            ]
        );

        $this->assertSame($ret, $this->_aColl->count());
    }

    /**
     * get start article out of given categories
     */
    public function testStartArticle()
    {
        $this->_aColl = new cArticleCollector(
            [
                'idcat' => 10,
            ]
        );

        $this->assertSame('31', $this->_aColl->startArticle()->get('idartlang'));
        $this->assertSame(0, $this->_aColl->count());

        $this->_aColl = new cArticleCollector(
            [
                'idcat' => 10,
            ]
        );

        $this->assertSame('31', $this->_aColl->startArticle()->get('idartlang'));

        $this->_aColl = new cArticleCollector([]);

        $this->expectException(cBadMethodCallException::class);
        $this->_aColl->startArticle();
    }

    /**
     * test next article
     */
    public function testNextArticle()
    {
        $this->_aColl      = new cArticleCollector(
            [
                'idcat'       => 10,
                'limit'       => 10,
                'start'       => true,
                'startonly'   => true,
                'direction'   => 'ASC',
                'order'       => 'creationdate',
            ]
        );
        $article = $this->_aColl->nextArticle();
        $this->assertInstanceOf('cApiArticleLanguage', $article);

        $this->assertSame(false, $this->_aColl->nextArticle());
    }

    /**
     * set result page
     */
    public function testSetResultPerPage()
    {
        $this->_aColl = new cArticleCollector(
            [
                'start' => true,
            ]
        );
        $this->assertSame(51, $this->_aColl->count());
        $this->_aColl->setResultPerPage(10);
        $this->_aColl->setPage(2);
        $this->_aColl->setResultPerPage(0);
    }

    /**
     *
     * @todo This test has not been implemented yet.
     */
    public function testSetPage()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * call seek over article size
     */
    public function testSeek()
    {
        $this->_aColl = new cArticleCollector(
            [
                'idcat' => 10,
                'start' => true,
            ]
        );
        $this->expectException(cOutOfBoundsException::class);
        $this->_aColl->seek(1);
    }

    /**
     * rewind position to position 0
     */
    public function testRewind()
    {
        // idcat 13 Teaser cat with 4 articles
        $this->_aColl = new cArticleCollector(
            [
                'idcat' => 13,
                'start' => true,
            ]
        );
        $this->assertSame(4, $this->_aColl->count());

        $this->assertSame(0, $this->_aColl->key());
        $this->_aColl->next();
        $this->assertSame(1, $this->_aColl->key());
        $this->_aColl->rewind();
        $this->assertSame(0, $this->_aColl->key());
    }

    /**
     * Test current
     */
    public function testCurrent()
    {
        $this->_aColl = new cArticleCollector(
            [
                'idcat' => 10,
                'start' => true,
            ]
        );
        $this->assertInstanceOf('cApiArticleLanguage', $this->_aColl->current());

        $this->_aColl->next();
        $this->assertNull($this->_aColl->current());
    }

    /**
     * check iterations and valid entries
     */
    public function testKey()
    {
        $this->_aColl = new cArticleCollector(
            [
                'idcat' => 13,
                'start' => true,
            ]
        );
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
        $this->assertSame(null, $this->_aColl->next());
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
    public function testNext()
    {
        $this->assertSame(0, $this->_readAttribute($this->_aColl, '_currentPosition'));
        $this->_aColl->next();
        $this->assertSame(1, $this->_readAttribute($this->_aColl, '_currentPosition'));
    }

    /**
     * Test valid article entries
     */
    public function testValid()
    {
        $this->_aColl = new cArticleCollector(
            [
                'start' => true,
            ]
        );
        $this->assertSame(51, $this->_aColl->count());
        $this->assertSame(true, $this->_aColl->valid());

        $this->_aColl = new cArticleCollector(
            [
                'idcat' => 1,
            ]
        );
        $this->assertSame(false, $this->_aColl->valid());
    }

    /**
     * Test count of empty collector.
     */
    public function testCountEmpty()
    {
        $this->_aColl = new cArticleCollector(
            [
                'idcat' => 1,
            ]
        );
        $this->assertSame(0, $this->_aColl->count());
    }

    /**
     * test count
     */
    public function testCount()
    {
        $this->_aColl      = new cArticleCollector(
            [
                'idcat'       => 10,
                'limit'       => 10,
                'start'       => true,
                'startonly'   => true,
                'direction'   => 'ASC',
                'order'       => 'creationdate',
            ]
        );
        $this->assertSame(1, $this->_aColl->count());
    }

}

