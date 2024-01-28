<?php

/**
 * This file contains tests for Contenido cContentTypeAbstractTest.
 *
 * @package    Testing
 * @subpackage Test_Content_Types
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

/**
 * Content type helper class to test some features.
 *
 * @package    Testing
 * @subpackage Test_Chains
 */
class cContentTypeA_Test extends cContentTypeAbstract
{
    const OUTPUT = '
        <h1>Title</h1>
        <div>Some content</div>
    ';

    public function __construct($rawSettings, $id, array $contentTypes)
    {
        parent::__construct($rawSettings, $id, $contentTypes);
    }

    public function generateViewCode(): string
    {
        $code = '<?php
            $obj = new %s(\'%s\', %s, %s);
            echo $obj->buildCode();
        ?>';

        $code = $this->_wrapPhpViewCode($code);

        return sprintf($code, get_class($this), $this->_rawSettings, $this->_id, '[]');
    }

    public function generateEditCode(): string
    {
        // Implement generateEditCode() method.
    }

    public function buildCode(): string
    {
        return self::OUTPUT;
    }

}

/**
 * Class to test cContentTypeAbstractTest.
 *
 * @package    Testing
 * @subpackage Test_Content_Types
 */
class cContentTypeAbstractTest extends cTestingTestCase
{

    private $_code;

    protected function setUp(): void
    {
        $code = 'echo "foobar";';
        $this->_code = "(function (){ ob_start(); $code \$output = ob_get_contents(); ob_end_clean(); return \$output; })()";
    }

    /**
     * Test {@see cContentTypeAbstract::isWrappedContentTypeCodePhp()}
     */
    public function testIiWrappedContentTypeCodePhp()
    {
        // Test without whitespace in string concatenation
        $code = '"./*[CONTENT_TYPE]*/' . $this->_code . '/*[/CONTENT_TYPE]*/."';
        $result = cContentTypeAbstract::isWrappedContentTypeCodePhp($code);
        $this->assertEquals(true, $result);

        // Test with whitespace in string concatenation
        $code =  '" . /*[CONTENT_TYPE]*/' . $this->_code . '/*[/CONTENT_TYPE]*/ . "';
        $result = cContentTypeAbstract::isWrappedContentTypeCodePhp($code);
        $this->assertEquals(true, $result);

        // Test with multiple whitespaces in string concatenation
        $code =  '"  .  /*[CONTENT_TYPE]*/' . $code . '/*[/CONTENT_TYPE]*/  .  "';
        $result = cContentTypeAbstract::isWrappedContentTypeCodePhp($code);
        $this->assertEquals(true, $result);

        // Test with multiple whitespaces in string concatenation
        $code =  '"  .  /*[CONTENT_TYPE]*/' . $code . '/*[/CONTENT_TYPE]*/  .  "';
        $result = cContentTypeAbstract::isWrappedContentTypeCodePhp($code);
        $this->assertEquals(true, $result);

        // Test with multi-line code
        $code = '" . /*[CONTENT_TYPE]*/ (function(){
                ob_start();
                echo "foobar";
                echo "foobar";
                $output = ob_get_contents();
                ob_end_clean();
                return $output;
        })() /*[/CONTENT_TYPE]*/ . "';
        $result = cContentTypeAbstract::isWrappedContentTypeCodePhp($code);
        $this->assertEquals(true, $result);
    }

    /**
     * Test {@see cContentTypeAbstract::generateViewCode()} with echoing
     * container placeholder replacement.
     * @return void
     */
    public function testGenerateViewCodeEcho()
    {
        $contentType = new cContentTypeA_Test(null, 123, []);
        $viewCode = $contentType->generateViewCode();
        $moduleCode = '<?php
            echo "CMS_VALUE[123]";
        ?>';

        $output = $this->getEvaluatedModuleCode($moduleCode,'CMS_VALUE[123]', $viewCode);
        $moduleOutput = $this->cleanUpOutputString($output);
        $contentTypeOutput = $this->cleanUpOutputString(cContentTypeA_Test::OUTPUT);
        $this->assertEquals($contentTypeOutput, $moduleOutput);
    }

    /**
     * Test {@see cContentTypeAbstract::generateViewCode()} with variable
     * assignment and then echoing the container placeholder replacement.
     * @return void
     */
    public function testGenerateViewCodeAssignment()
    {
        $contentType = new cContentTypeA_Test(null, 123, []);
        $viewCode = $contentType->generateViewCode();
        $moduleCode = '<?php
            $myVar = "CMS_VALUE[123]";
            echo $myVar;
        ?>';

        $output = $this->getEvaluatedModuleCode($moduleCode,'CMS_VALUE[123]', $viewCode);
        $moduleOutput = $this->cleanUpOutputString($output);
        $contentTypeOutput = $this->cleanUpOutputString(cContentTypeA_Test::OUTPUT);
        $this->assertEquals($contentTypeOutput, $moduleOutput);
    }

    /**
     * Test {@see cContentTypeAbstract::generateViewCode()} with variable (array)
     * assignment and then echoing the container placeholder replacement.
     * @return void
     */
    public function testGenerateViewCodeAssignmentArray()
    {
        $contentType = new cContentTypeA_Test(null, 123, []);
        $viewCode = $contentType->generateViewCode();
        $moduleCode = '<?php
            $myVar = [
                "code" => "CMS_VALUE[123]"
            ];
            echo $myVar["code"];
        ?>';

        $output = $this->getEvaluatedModuleCode($moduleCode,'CMS_VALUE[123]', $viewCode);
        $moduleOutput = $this->cleanUpOutputString($output);
        $contentTypeOutput = $this->cleanUpOutputString(cContentTypeA_Test::OUTPUT);
        $this->assertEquals($contentTypeOutput, $moduleOutput);
    }



    /**
     * Test {@see cContentTypeAbstract::generateViewCode()} with variable (object)
     * assignment and then echoing the container placeholder replacement.
     * @return void
     */
    public function testGenerateViewCodeAssignmentObject()
    {
        $contentType = new cContentTypeA_Test(null, 123, []);
        $viewCode = $contentType->generateViewCode();
        $moduleCode = '<?php
            $myVar = new stdClass();
            $myVar->code = "CMS_VALUE[123]";
            echo $myVar->code;
        ?>';

        $output = $this->getEvaluatedModuleCode($moduleCode,'CMS_VALUE[123]', $viewCode);
        $moduleOutput = $this->cleanUpOutputString($output);
        $contentTypeOutput = $this->cleanUpOutputString(cContentTypeA_Test::OUTPUT);
        $this->assertEquals($contentTypeOutput, $moduleOutput);
    }

    /**
     * Replaces the container placeholder in module code with the view code and evaluates the result.
     *
     * @param string $moduleCode
     * @param string $search
     * @param string $viewCode
     * @return false|string
     */
    protected function getEvaluatedModuleCode(string $moduleCode, string $search, string $viewCode)
    {
        $moduleCode = str_replace($search, $viewCode, $moduleCode);
        ob_start();
        @eval("?>\n" . $moduleCode . "\n<?php\n");
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function cleanUpOutputString(string $string)
    {
        return (string) preg_replace('!\s+!', ' ', $string);
    }

}
