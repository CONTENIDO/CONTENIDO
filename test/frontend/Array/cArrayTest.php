<?php

/**
 * This file contains tests for the class cArray.
 *
 * @package Testing
 * @subpackage Util
 * @version SVN Revision $Rev:$
 *
 * @author marcus.gnass
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * This class tests the static class methods uf the cArray util class.
 *
 * @author marcus.gnass
 */
class cApiCecRegistryTest extends PHPUnit_Framework_TestCase {

    /**
     * Test trimming of items in empty array, nonempty array and nonempty array
     * with arbitrary character.
     */
    public function testTrim() {

        // empty array
        $this->assertSame(array(), cArray::trim(array()));

        // nonempty array
        $data = explode(',', 'foo , bar, baz ');
        $exp = explode(',', 'foo,bar,baz');
        $this->assertSame($exp, cArray::trim($data));

        // nonempty array with arbitrary character
        $data = explode(',', 'foo,bar,baz');
        $exp = explode(',', 'foo,ar,az');
        $this->assertSame($exp, cArray::trim($data, 'b'));
    }

    /**
     * Test recursive search for NULL, INTEGER, FLOAT, BOOLEAN, missing &
     * available STRING in empty array and nested array with values of all other
     * data types.
     */
    public function testSearchRecursive() {

        // empty array
        $data = array();
        $this->assertSame(false, cArray::searchRecursive($data, NULL));
        $this->assertSame(false, cArray::searchRecursive($data, 0));
        $this->assertSame(false, cArray::searchRecursive($data, 0.0));
        $this->assertSame(false, cArray::searchRecursive($data, false));
        $this->assertSame(false, cArray::searchRecursive($data, ''));
        $this->assertSame(false, cArray::searchRecursive($data, 'foo'));
        $this->assertSame(false, cArray::searchRecursive($data, 'bar'));

        // nonempty nested array
        $data = array(
            array(
                'NULL',
                '0',
                '0.0',
                'false',
                'foo',
                'bar',
                'baz',
                '',
                NULL,
                0,
                0.0,
                false
            ),
            'NULL',
            '0',
            '0.0',
            'false',
            'foo',
            'bar',
            'baz',
            '',
            NULL,
            0,
            0.0,
            false
        );

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
        $this->assertSame(7, cArray::searchRecursive($data, NULL));
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
        $this->assertSame(4, cArray::searchRecursive($data, '', true));
        $this->assertSame(0, cArray::searchRecursive($data, NULL, true));
        $this->assertSame(false, cArray::searchRecursive($data, 0, true));
        $this->assertSame(false, cArray::searchRecursive($data, 0.0, true));
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
        $this->assertSame(8, cArray::searchRecursive($data, NULL, false, true));
        $this->assertSame(9, cArray::searchRecursive($data, 0, false, true));
        $this->assertSame(10, cArray::searchRecursive($data, 0.0, false, true));
        $this->assertSame(11, cArray::searchRecursive($data, false, false, true));
    }

    /**
     * Test sorting of characters in array according to given locale.
     *
     * @todo add further locales and further locale specific characters
     */
    public function testSortWithLocale() {
        $orig = explode(',', 'ß,ü,ö,ä,z,y,x,w,v,u,t,s,r,q,p,o,n,m,l,k,j,i,h,g,f,e,d,c,b,a');

        $us = explode(',', 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,ä,ö,ü,ß');
        $this->assertSame($us, cArray::sortWithLocale($orig, 'us'));

        $us_EN = explode(',', 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,ä,ö,ü,ß');
        $this->assertSame($us_EN, cArray::sortWithLocale($orig, 'us_EN'));

        $de = explode(',', 'a,ä,b,c,d,e,f,g,h,i,j,k,l,m,n,o,ö,p,q,r,s,t,u,ü,v,w,x,y,z,ß');
        $this->assertSame($de, cArray::sortWithLocale($orig, 'de'));

        $de_DE = explode(',', 'a,ä,b,c,d,e,f,g,h,i,j,k,l,m,n,o,ö,p,q,r,s,t,u,ü,v,w,x,y,z,ß');
        $this->assertSame($de_DE, cArray::sortWithLocale($orig, 'de_DE'));
    }

    /**
     * $new_array = cArray::csort($array [, 'col1' [, SORT_FLAG]+]);
     * SORT_FLAG = [SORT_ASC|SORT_DESC|SORT_REGULAR|SORT_NUMERIC|SORT_STRING]
     *
     * @todo add test
     */
    public function testCsort() {

        // $de_DE = explode(',', '');
        // $this->assertSame($de_DE, cArray::csort($orig));

        // $array = cArray::csort($array, 'town','age', SORT_DESC, 'name');
    }

    /**
     * Tests initializing of array values in empty and nonempty array, with
     * existant and nonexistant keys and default or custom default values.
     *
     * @todo add test
     */
    public function testInitializeKey() {

        // test empty array w/ nonexistant key and default default
        $actual = array();
        cArray::initializeKey($actual, 'key');
        $this->assertSame(1, count($actual));
        $this->arrayHasKey('key');
        $this->assertSame('', $actual['key']);

        // test empty array w/ nonexistant key and custom default
        $actual = array();
        cArray::initializeKey($actual, 'key', 'custom');
        $this->assertSame(1, count($actual));
        $this->arrayHasKey('key');
        $this->assertSame('custom', $actual['key']);

        // test empty array w/ existant key and default default => IMPOSSIBLE
        // test empty array w/ existant key and custom default => IMPOSSIBLE

        // test nonempty array w/ nonexistant key and default default
        $actual = array(
            'foo' => 'bar'
        );
        cArray::initializeKey($actual, 'key');
        $this->assertSame(2, count($actual));
        $this->arrayHasKey('key');
        $this->assertSame('', $actual['key']);
        $this->arrayHasKey('foo');
        $this->assertSame('bar', $actual['foo']);

        // test nonempty array w/ nonexistant key and custom default
        $actual = array(
            'foo' => 'bar'
        );
        cArray::initializeKey($actual, 'key', 'custom');
        $this->assertSame(2, count($actual));
        $this->arrayHasKey('key');
        $this->assertSame('custom', $actual['key']);
        $this->arrayHasKey('foo');
        $this->assertSame('bar', $actual['foo']);

        // test nonempty array w/ nonexistant key and default default
        $actual = array(
            'foo' => 'bar'
        );
        cArray::initializeKey($actual, 'key');
        $this->assertSame(2, count($actual));
        $this->arrayHasKey('key');
        $this->assertSame('', $actual['key']);
        $this->arrayHasKey('foo');
        $this->assertSame('bar', $actual['foo']);

        // test nonempty array w/ nonexistant key and custom default
        $actual = array(
            'foo' => 'bar'
        );
        cArray::initializeKey($actual, 'key', 'custom');
        $this->assertSame(2, count($actual));
        $this->arrayHasKey('key');
        $this->assertSame('custom', $actual['key']);
        $this->arrayHasKey('foo');
        $this->assertSame('bar', $actual['foo']);

        // test nonempty array w/ existant key and default default
        $actual = array(
            'key' => 'old'
        );
        cArray::initializeKey($actual, 'key');
        $this->assertSame(1, count($actual));
        $this->arrayHasKey('key');
        $this->assertSame('old', $actual['key']);

        // test nonempty array w/ nonexistant key and custom default
        $actual = array(
            'key' => 'old'
        );
        cArray::initializeKey($actual, 'key', 'custom');
        $this->assertSame(1, count($actual));
        $this->arrayHasKey('key');
        $this->assertSame('old', $actual['key']);
    }
}
