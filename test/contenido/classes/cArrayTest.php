<?php

/**
 * This file contains tests for the class cArray.
 *
 * @package    Testing
 * @subpackage Util
 * @author     marcus.gnass
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * This class tests the static class methods of the cArray util class.
 *
 * @author     marcus.gnass
 */
class cArrayTest extends cTestingTestCase
{
    /**
     *
     * @var array
     */
    private $_orig;

    /**
     */
    protected function setUp(): void
    {
        // data for sortWithLocale
        $this->_orig = explode(',', 'ß,ü,ö,ä,z,y,x,w,v,u,t,s,r,q,p,o,n,m,l,k,j,i,h,g,f,e,d,c,b,a');
    }

    /**
     * Test trimming of items in empty array, nonempty array and nonempty array
     * with arbitrary character.
     */
    public function testTrim()
    {
        // empty array
        $this->assertSame([], cArray::trim([]));

        // nonempty array
        $data = explode(',', 'foo , bar, baz ');
        $exp  = explode(',', 'foo,bar,baz');
        $this->assertSame($exp, cArray::trim($data));

        // nonempty array with arbitrary character
        $data = explode(',', 'foo,bar,baz');
        $exp  = explode(',', 'foo,ar,az');
        $this->assertSame($exp, cArray::trim($data, 'b'));
    }

    /**
     * Test recursive search for NULL, INTEGER, FLOAT, BOOLEAN, missing &
     * available STRING in empty array and nested array with values of all other
     * data types.
     */
    public function testSearchRecursive()
    {
        // empty array
        $data = [];
        $this->assertSame(false, cArray::searchRecursive($data, null));
        $this->assertSame(false, cArray::searchRecursive($data, 0));
        $this->assertSame(false, cArray::searchRecursive($data, 0.0));
        $this->assertSame(false, cArray::searchRecursive($data, false));
        $this->assertSame(false, cArray::searchRecursive($data, ''));
        $this->assertSame(false, cArray::searchRecursive($data, 'foo'));
        $this->assertSame(false, cArray::searchRecursive($data, 'bar'));

        // nonempty nested array
        $data = [
            [
                'NULL',
                '0',
                '0.0',
                'false',
                'foo',
                'bar',
                'baz',
                '',
                null,
                0,
                0.0,
                false,
            ],
            'NULL',
            '0',
            '0.0',
            'false',
            'foo',
            'bar',
            'baz',
            '',
            null,
            0,
            0.0,
            false,
        ];

        $this->assertSame(0, cArray::searchRecursive($data, 'NULL'));
        $this->assertSame(1, cArray::searchRecursive($data, '0'));
        // '0.0' equals '0'
        $this->assertSame(1, cArray::searchRecursive($data, '0.0'));
        $this->assertSame(3, cArray::searchRecursive($data, 'false'));
        $this->assertSame(4, cArray::searchRecursive($data, 'foo'));
        $this->assertSame(5, cArray::searchRecursive($data, 'bar'));
        // 'ba' equals 0!
        $this->assertSame(9, cArray::searchRecursive($data, 'ba'));
        // NULL equals ''!
        $this->assertSame(7, cArray::searchRecursive($data, null));
        // 0 equals 'NULL'!
        $this->assertSame(0, cArray::searchRecursive($data, 0));
        // 0.0 equals 'NULL'!
        $this->assertSame(0, cArray::searchRecursive($data, 0.0));
        // false equals '0'!
        $this->assertSame(1, cArray::searchRecursive($data, false));
        $this->assertSame(7, cArray::searchRecursive($data, ''));

        // partial search
        $this->assertSame(0, cArray::searchRecursive($data, 'NULL', true));
        $this->assertSame(1, cArray::searchRecursive($data, '0', true));
        $this->assertSame(2, cArray::searchRecursive($data, '0.0', true));
        $this->assertSame(3, cArray::searchRecursive($data, 'false', true));
        $this->assertSame(4, cArray::searchRecursive($data, 'foo', true));
        $this->assertSame(5, cArray::searchRecursive($data, 'bar', true));
        $this->assertSame(5, cArray::searchRecursive($data, 'ba', true));

        // @todo ERROR: strpos(): Empty delimiter
        $this->assertSame(false, cArray::searchRecursive($data, '', true));
        $this->assertSame(false, cArray::searchRecursive($data, null, true));
        $this->assertSame(1, cArray::searchRecursive($data, 0, true));
        $this->assertSame(1, cArray::searchRecursive($data, 0.0, true));
        $this->assertSame(false, cArray::searchRecursive($data, false, true));

        // strict search
        $this->assertSame(0, cArray::searchRecursive($data, 'NULL', false, true));
        $this->assertSame(1, cArray::searchRecursive($data, '0', false, true));
        $this->assertSame(2, cArray::searchRecursive($data, '0.0', false, true));
        $this->assertSame(3, cArray::searchRecursive($data, 'false', false, true));
        $this->assertSame(4, cArray::searchRecursive($data, 'foo', false, true));
        $this->assertSame(5, cArray::searchRecursive($data, 'bar', false, true));
        $this->assertSame(false, cArray::searchRecursive($data, 'ba', false, true));
        $this->assertSame(7, cArray::searchRecursive($data, '', false, true));
        $this->assertSame(8, cArray::searchRecursive($data, null, false, true));
        $this->assertSame(9, cArray::searchRecursive($data, 0, false, true));
        $this->assertSame(10, cArray::searchRecursive($data, 0.0, false, true));
        $this->assertSame(11, cArray::searchRecursive($data, false, false, true));
    }

    /**
     * Test sorting of characters in array according to given locale.
     *
     * @todo add further locales and further locale specific characters
     */
    public function testSortWithLocaleUs()
    {
        $us = explode(',', 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,ß,ä,ö,ü');
        $this->assertSame($us, cArray::sortWithLocale($this->_orig, 'us'));
    }

    /**
     * Test sorting of characters in array according to given locale.
     *
     * @todo add further locales and further locale specific characters
     */
    public function testSortWithLocaleUsEn()
    {
        $us_EN = explode(',', 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,ß,ä,ö,ü');
        $this->assertSame($us_EN, cArray::sortWithLocale($this->_orig, 'us_EN'));
    }

    /**
     * Test sorting of characters in array according to given locale.
     *
     * @todo add further locales and further locale specific characters
     */
    public function testSortWithLocaleDe()
    {
        $de = explode(',', 'a,ä,b,c,d,e,f,g,h,i,j,k,l,m,n,o,ö,p,q,r,s,t,u,ü,v,w,x,y,z,ß');
        $this->assertSame($de, cArray::sortWithLocale($this->_orig, 'de'));
    }

    /**
     * Test sorting of characters in array according to given locale.
     *
     * @todo add further locales and further locale specific characters
     */
    public function testSortWithLocaleDeDe()
    {
        $de_DE = explode(',', 'a,ä,b,c,d,e,f,g,h,i,j,k,l,m,n,o,ö,p,q,r,s,t,u,ü,v,w,x,y,z,ß');
        $this->assertSame($de_DE, cArray::sortWithLocale($this->_orig, 'de_DE'));
    }

    /**
     * $new_array = cArray::csort($array [, 'col1' [, SORT_FLAG]+]);
     * SORT_FLAG = [SORT_ASC|SORT_DESC|SORT_REGULAR|SORT_NUMERIC|SORT_STRING]
     */
    public function testCsort()
    {
        // source data
        $tr_31_we  = [
            'name' => 'Trautmann',
            'age'  => '31',
            'town' => 'Weitengesäß',
        ];
        $zi_23_ba  = [
            'name' => 'Ziegler',
            'age'  => '23',
            'town' => 'Bad Nauheim',
        ];
        $gn_142_of = [
            'name' => 'Gnaß',
            'age'  => '142',
            'town' => 'Offenbach am Main',
        ];
        $src       = [
            $tr_31_we,
            $zi_23_ba,
            $gn_142_of,
        ];

        // name ASC
        $exp = [
            $gn_142_of,
            $tr_31_we,
            $zi_23_ba,
        ];
        $this->assertSame($exp, cArray::csort($src, 'name', SORT_ASC));
        // name DESC
        $exp = [
            $zi_23_ba,
            $tr_31_we,
            $gn_142_of,
        ];
        $this->assertSame($exp, cArray::csort($src, 'name', SORT_DESC));
        // name REGULAR
        $exp = [
            $gn_142_of,
            $tr_31_we,
            $zi_23_ba,
        ];
        $this->assertSame($exp, cArray::csort($src, 'name', SORT_REGULAR));
        // // name NUMERIC
        // $exp = array($gn_142_of, $tr_31_we, $zi_23_ba);
        // $this->assertSame($exp, cArray::csort($src, 'name', SORT_NUMERIC));
        // name STRING
        $exp = [
            $gn_142_of,
            $tr_31_we,
            $zi_23_ba,
        ];
        $this->assertSame($exp, cArray::csort($src, 'name', SORT_STRING));

        // ////////////////////////////////////////////////////////////////////

        // age ASC (implicit numeric)
        $exp = [
            $zi_23_ba,
            $tr_31_we,
            $gn_142_of,
        ];
        $this->assertSame($exp, cArray::csort($src, 'age', SORT_ASC));
        // age DESC (implicit numeric)
        $exp = [
            $gn_142_of,
            $tr_31_we,
            $zi_23_ba,
        ];
        $this->assertSame($exp, cArray::csort($src, 'age', SORT_DESC));
        // age REGULAR (implicit numeric)
        $exp = [
            $zi_23_ba,
            $tr_31_we,
            $gn_142_of,
        ];
        $this->assertSame($exp, cArray::csort($src, 'age', SORT_REGULAR));
        // age NUMERIC
        $exp = [
            $zi_23_ba,
            $tr_31_we,
            $gn_142_of,
        ];
        $this->assertSame($exp, cArray::csort($src, 'age', SORT_NUMERIC));
        // age STRING
        $exp = [
            $gn_142_of,
            $zi_23_ba,
            $tr_31_we,
        ];
        $this->assertSame($exp, cArray::csort($src, 'age', SORT_STRING));

        // ////////////////////////////////////////////////////////////////////

        // town ASC
        $exp = [
            $zi_23_ba,
            $gn_142_of,
            $tr_31_we,
        ];
        $this->assertSame($exp, cArray::csort($src, 'town', SORT_ASC));
        // town DESC
        $exp = [
            $tr_31_we,
            $gn_142_of,
            $zi_23_ba,
        ];
        $this->assertSame($exp, cArray::csort($src, 'town', SORT_DESC));
        // town REGULAR
        $exp = [
            $zi_23_ba,
            $gn_142_of,
            $tr_31_we,
        ];
        $this->assertSame($exp, cArray::csort($src, 'town', SORT_REGULAR));
        // // town NUMERIC
        // $exp = array($zi_23_ba, $gn_142_of, $tr_31_we);
        // $this->assertSame($exp, cArray::csort($src, 'town', SORT_NUMERIC));
        // town STRING
        $exp = [
            $zi_23_ba,
            $gn_142_of,
            $tr_31_we,
        ];
        $this->assertSame($exp, cArray::csort($src, 'town', SORT_STRING));

        // empty array
        $data = [];
        $this->assertSame($data, cArray::csort($data, 'town', SORT_STRING));

        // non array
        $data = '';
        $this->assertSame($data, cArray::csort($data, 'town', SORT_STRING));

        $data = 1;
        $this->assertSame($data, cArray::csort($data, 'town', SORT_STRING));

        $data = null;
        $this->assertSame($data, cArray::csort($data, 'town', SORT_STRING));
   }

    /**
     * Tests initializing of array values in empty and nonempty array, with
     * existant and nonexistant keys and default or custom default values.
     */
    public function testInitializeKey()
    {
        // test empty array w/ nonexistant key and default default
        $actual = [];
        cArray::initializeKey($actual, 'key');
        $this->assertCount(1, $actual);
        $this->assertArrayHasKey('key', $actual);
        $this->assertSame('', $actual['key']);

        // test empty array w/ nonexistant key and custom default
        $actual = [];
        cArray::initializeKey($actual, 'key', 'custom');
        $this->assertCount(1, $actual);
        $this->assertArrayHasKey('key', $actual);
        $this->assertSame('custom', $actual['key']);

        // test empty array w/ existant key and default default => IMPOSSIBLE
        // test empty array w/ existant key and custom default => IMPOSSIBLE

        // test nonempty array w/ nonexistant key and default default
        $actual = [
            'foo' => 'bar',
        ];
        cArray::initializeKey($actual, 'key');
        $this->assertCount(2, $actual);
        $this->assertArrayHasKey('key', $actual);
        $this->assertSame('', $actual['key']);
        $this->assertArrayHasKey('foo', $actual);
        $this->assertSame('bar', $actual['foo']);

        // test nonempty array w/ nonexistant key and custom default
        $actual = [
            'foo' => 'bar',
        ];
        cArray::initializeKey($actual, 'key', 'custom');
        $this->assertCount(2, $actual);
        $this->assertArrayHasKey('key', $actual);
        $this->assertSame('custom', $actual['key']);
        $this->assertArrayHasKey('foo', $actual);
        $this->assertSame('bar', $actual['foo']);

        // test nonempty array w/ nonexistant key and default default
        $actual = [
            'foo' => 'bar',
        ];
        cArray::initializeKey($actual, 'key');
        $this->assertCount(2, $actual);
        $this->assertArrayHasKey('key', $actual);
        $this->assertSame('', $actual['key']);
        $this->assertArrayHasKey('foo', $actual);
        $this->assertSame('bar', $actual['foo']);

        // test nonempty array w/ nonexistant key and custom default
        $actual = [
            'foo' => 'bar',
        ];
        cArray::initializeKey($actual, 'key', 'custom');
        $this->assertCount(2, $actual);
        $this->assertArrayHasKey('key', $actual);
        $this->assertSame('custom', $actual['key']);
        $this->assertArrayHasKey('foo', $actual);
        $this->assertSame('bar', $actual['foo']);

        // test nonempty array w/ existant key and default default
        $actual = [
            'key' => 'old',
        ];
        cArray::initializeKey($actual, 'key');
        $this->assertCount(1, $actual);
        $this->assertArrayHasKey('key', $actual);
        $this->assertSame('old', $actual['key']);

        // test nonempty array w/ nonexistant key and custom default
        $actual = [
            'key' => 'old',
        ];
        cArray::initializeKey($actual, 'key', 'custom');
        $this->assertCount(1, $actual);
        $this->assertArrayHasKey('key', $actual);
        $this->assertSame('old', $actual['key']);
    }
}
