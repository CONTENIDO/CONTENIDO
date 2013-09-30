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
        $this->assertEquals(array(), cArray::trim(array()));

        // nonempty array
        $data = explode(',', 'foo , bar, baz ');
        $exp = explode(',', 'foo,bar,baz');
        $this->assertEquals($exp, cArray::trim($data));

        // nonempty array with arbitrary character
        $data = explode(',', 'foo,bar,baz');
        $exp = explode(',', 'foo,ar,az');
        $this->assertEquals($exp, cArray::trim($data, 'b'));
    }

    /**
     * Test recursive search for NULL, INTEGER, FLOAT, BOOLEAN, missing &
     * available STRING in empty array and nested array with values of all other
     * data types.
     */
    public function testSearchRecursive() {

        // empty array
        $data = array();
        $this->assertEquals(false, cArray::searchRecursive($data, NULL));
        $this->assertEquals(false, cArray::searchRecursive($data, 0));
        $this->assertEquals(false, cArray::searchRecursive($data, 0.0));
        $this->assertEquals(false, cArray::searchRecursive($data, false));
        $this->assertEquals(false, cArray::searchRecursive($data, ''));
        $this->assertEquals(false, cArray::searchRecursive($data, 'foo'));
        $this->assertEquals(false, cArray::searchRecursive($data, 'bar'));

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

        $this->assertEquals(0, cArray::searchRecursive($data, 'NULL'));
        $this->assertEquals(1, cArray::searchRecursive($data, '0'));
        // '0.0' equals '0'
        $this->assertEquals(1, cArray::searchRecursive($data, '0.0'));
        $this->assertEquals(3, cArray::searchRecursive($data, 'false'));
        $this->assertEquals(4, cArray::searchRecursive($data, 'foo'));
        $this->assertEquals(5, cArray::searchRecursive($data, 'bar'));
        // 'ba' equals 0!
        $this->assertEquals(9, cArray::searchRecursive($data, 'ba'));
        // NULL equals ''!
        $this->assertEquals(7, cArray::searchRecursive($data, NULL));
        // 0 equals 'NULL'!
        $this->assertEquals(0, cArray::searchRecursive($data, 0));
        // 0.0 equals 'NULL'!
        $this->assertEquals(0, cArray::searchRecursive($data, 0.0));
        // false equals '0'!
        $this->assertEquals(1, cArray::searchRecursive($data, false));
        $this->assertEquals(7, cArray::searchRecursive($data, ''));

        // partial search
        $this->assertEquals(0, cArray::searchRecursive($data, 'NULL', true));
        $this->assertEquals(1, cArray::searchRecursive($data, '0', true));
        $this->assertEquals(2, cArray::searchRecursive($data, '0.0', true));
        $this->assertEquals(3, cArray::searchRecursive($data, 'false', true));
        $this->assertEquals(4, cArray::searchRecursive($data, 'foo', true));
        $this->assertEquals(5, cArray::searchRecursive($data, 'bar', true));
        $this->assertEquals(5, cArray::searchRecursive($data, 'ba', true));
        // @todo ERROR: strpos(): Empty delimiter
        $this->assertEquals(4, cArray::searchRecursive($data, '', true));
        $this->assertEquals(0, cArray::searchRecursive($data, NULL, true));
        $this->assertEquals(false, cArray::searchRecursive($data, 0, true));
        $this->assertEquals(false, cArray::searchRecursive($data, 0.0, true));
        $this->assertEquals(false, cArray::searchRecursive($data, false, true));

        // strict search
        $this->assertEquals(0, cArray::searchRecursive($data, 'NULL', false, true));
        $this->assertEquals(1, cArray::searchRecursive($data, '0', false, true));
        $this->assertEquals(2, cArray::searchRecursive($data, '0.0', false, true));
        $this->assertEquals(3, cArray::searchRecursive($data, 'false', false, true));
        $this->assertEquals(4, cArray::searchRecursive($data, 'foo', false, true));
        $this->assertEquals(5, cArray::searchRecursive($data, 'bar', false, true));
        $this->assertEquals(false, cArray::searchRecursive($data, 'ba', false, true));
        $this->assertEquals(7, cArray::searchRecursive($data, '', false, true));
        $this->assertEquals(8, cArray::searchRecursive($data, NULL, false, true));
        $this->assertEquals(9, cArray::searchRecursive($data, 0, false, true));
        $this->assertEquals(10, cArray::searchRecursive($data, 0.0, false, true));
        $this->assertEquals(11, cArray::searchRecursive($data, false, false, true));
    }

    /**
     * Test sorting of characters in array according to given locale.
     *
     * @todo add further locales and further locale specific characters
     */
    public function testSortWithLocale() {
        $orig = explode(',', 'ß,ü,ö,ä,z,y,x,w,v,u,t,s,r,q,p,o,n,m,l,k,j,i,h,g,f,e,d,c,b,a');

        $us = explode(',', 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,ä,ö,ü,ß');
        $this->assertEquals($us, cArray::sortWithLocale($orig, 'us'));

        $us_EN = explode(',', 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,ä,ö,ü,ß');
        $this->assertEquals($us_EN, cArray::sortWithLocale($orig, 'us_EN'));

        $de = explode(',', 'a,ä,b,c,d,e,f,g,h,i,j,k,l,m,n,o,ö,p,q,r,s,t,u,ü,v,w,x,y,z,ß');
        $this->assertEquals($de, cArray::sortWithLocale($orig, 'de'));

        $de_DE = explode(',', 'a,ä,b,c,d,e,f,g,h,i,j,k,l,m,n,o,ö,p,q,r,s,t,u,ü,v,w,x,y,z,ß');
        $this->assertEquals($de_DE, cArray::sortWithLocale($orig, 'de_DE'));
    }

    /**
     *
     * @todo add test
     */
    public function testCsort() {
    }

    /**
     *
     * @todo add test
     */
    public function testInitializeKey() {
    }
}
