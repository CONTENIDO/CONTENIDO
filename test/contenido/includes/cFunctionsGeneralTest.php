<?php

declare(strict_types=1);

/**
 * This file contains tests for general CONTENIDO functions.
 *
 * @package    Testing
 * @subpackage Polyfill
 * @author     marcus.gnass
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

use PHPUnit\Framework\Attributes\DataProvider;

class cFunctionsGeneralTest extends cTestingTestCase
{
    public function setUp(): void
    {
        cInclude('includes', 'functions.general.php');;
    }

    public static function isAjaxRequestProvider(): array
    {
        return self::makeRequestMethodProviderData('XMLHttpRequest');
    }

    #[DataProvider('isAjaxRequestProvider')]
    public function testIsAjaxRequest($input, $output)
    {
        $backup = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
        $_SERVER['HTTP_X_REQUESTED_WITH'] = $input;
        $this->assertEquals($output, \cIsAjaxRequest());
        $_SERVER['HTTP_X_REQUESTED_WITH'] = $backup;
    }

    public static function isGetRequestProvider(): array
    {
        return self::makeRequestMethodProviderData('GET');
    }

    #[DataProvider('isGetRequestProvider')]
    public function testIsGetRequest($input, $output)
    {
        $backup = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = $input;
        $this->assertEquals($output, \cIsGetRequest());
        $_SERVER['REQUEST_METHOD'] = $backup;
    }

    public static function isPostRequestProvider(): array
    {
        return self::makeRequestMethodProviderData('POST');
    }

    #[DataProvider('isPostRequestProvider')]
    public function testIsPostRequest($input, $output)
    {
        $backup = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = $input;
        $this->assertEquals($output, \cIsPostRequest());
        $_SERVER['REQUEST_METHOD'] = $backup;
    }

    public static function isHeadRequestProvider(): array
    {
        return self::makeRequestMethodProviderData('HEAD');
    }

    #[DataProvider('isHeadRequestProvider')]
    public function testIsHeadRequest($input, $output)
    {
        $backup = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = $input;
        $this->assertEquals($output, \cIsHeadRequest());
        $_SERVER['REQUEST_METHOD'] = $backup;
    }

    public static function isPutRequestProvider(): array
    {
        return self::makeRequestMethodProviderData('PUT');
    }

    #[DataProvider('isPutRequestProvider')]
    public function testIsPutRequest($input, $output)
    {
        $backup = $_SERVER['REQUEST_METHOD'] ?? null;
        $_SERVER['REQUEST_METHOD'] = $input;
        $this->assertEquals($output, \cIsPutRequest());
        $_SERVER['REQUEST_METHOD'] = $backup;
    }

    public static function isHttpsRequestProvider(): array
    {
        return [
            'HTTPS empty' => ['HTTPS', '', false],
            'HTTPS empty (0)' => ['HTTPS', 0, false],
            'HTTPS off' => ['HTTPS', 'off', false],
            'HTTPS on' => ['HTTPS', 'on', true],

            'REQUEST_SCHEME empty' => ['REQUEST_SCHEME', '', false],
            'REQUEST_SCHEME empty (0)' => ['REQUEST_SCHEME', 0, false],
            'REQUEST_SCHEME https' => ['REQUEST_SCHEME', 'https', true],

            'HTTP_X_FORWARDED_PROTO empty' => ['HTTP_X_FORWARDED_PROTO', '', false],
            'HTTP_X_FORWARDED_PROTO empty (0)' => ['HTTP_X_FORWARDED_PROTO', 0, false],
            'HTTP_X_FORWARDED_PROTO http://foo.bar' => ['HTTP_X_FORWARDED_PROTO', 'http://foo.bar', false],
            'HTTP_X_FORWARDED_PROTO https://foo.bar' => ['HTTP_X_FORWARDED_PROTO', 'https://foo.bar', true],

            'HTTP_X_FORWARDED_SSL empty' => ['HTTP_X_FORWARDED_SSL', '', false],
            'HTTP_X_FORWARDED_SSL empty (0)' => ['HTTP_X_FORWARDED_SSL', 0, false],
            'HTTP_X_FORWARDED_SSL on' => ['HTTP_X_FORWARDED_SSL', 'on', true],
        ];
    }

    #[DataProvider('isHttpsRequestProvider')]
    public function testIsHttpsRequest($key, $input, $output)
    {
        $backup = $_SERVER[$key] ?? null;
        $_SERVER[$key] = $input;
        $this->assertEquals($output, \cIsHttpsRequest());
        $_SERVER[$key] = $backup;
    }

    public static function makeRequestMethodProviderData(string $method): array
    {
        return [
            'null' => [null, false],
            'empty' => ['', false],
            'foobar' => ['foobar', false],
            $method => [$method, true],
        ];
    }

}
