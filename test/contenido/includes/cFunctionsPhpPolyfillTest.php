<?php

/**
 * This file contains tests for PHP polyfills.
 *
 * @package    Testing
 * @subpackage Polyfill
 * @author     Murat Purc
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * This class tests the PHP polyfills.
 */
class cFunctionsPhpPolyfillTest extends cTestingTestCase
{

    /**
     * Test is_iterable
     */
    public function testIsIterable()
    {
        // false checks
        $this->assertSame(false, is_iterable(null));
        $this->assertSame(false, is_iterable(true));
        $this->assertSame(false, is_iterable(1));
        $this->assertSame(false, is_iterable(new \stdClass()));

        // true checks
        $this->assertSame(true, is_iterable([1, 2, 3]));
        $this->assertSame(true, is_iterable(new \ArrayObject()));
        $this->assertSame(true, is_iterable(new \ArrayIterator()));
        $this->assertSame(true, is_iterable((function () {
            yield 1;
        })()));
    }

    /**
     * Test is_countable
     */
    public function testIsCountable()
    {
        // false checks
        $this->assertSame(false, is_countable(null));
        $this->assertSame(false, is_countable(true));
        $this->assertSame(false, is_countable(1));
        $this->assertSame(false, is_countable(new \stdClass()));
        $this->assertSame(false, is_countable((function () {
            yield 1;
        })()));

        // true checks
        $this->assertSame(true, is_countable([1, 2, 3]));
        $this->assertSame(true, is_countable(new \ArrayObject()));
        $this->assertSame(true, is_countable(new \ArrayIterator()));
        $this->assertSame(true, is_countable(new \SimpleXMLElement('<foobar/>')));
    }

}
