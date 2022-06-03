<?php

/**
 * This file contains tests for Contenido cPermission.
 *
 * @package          Testing
 * @subpackage       Test_Registry
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */


/**
 * Class to test cPermission.
 *
 * TODO Implement other unit tests for cPermission.
 *
 * @package          Testing
 * @subpackage       Test_Permission
 */
class cPermissionTest extends cTestingTestCase
{

    /**
     * Test cPermission#permissionToArray.
     *
     * @return void
     */
    public function testPermissionToArray()
    {
        $result = cPermission::permissionToArray(null);
        $this->assertEquals([], $result);

        $result = cPermission::permissionToArray(2);
        $this->assertEquals([], $result);

        $result = cPermission::permissionToArray('');
        $this->assertEquals([], $result);

        $result = cPermission::permissionToArray('lang[1]');
        $this->assertEquals(['lang[1]'], $result);

        $result = cPermission::permissionToArray(['lang[1]']);
        $this->assertEquals(['lang[1]'], $result);

        $result = cPermission::permissionToArray('lang[1],client[2]');
        $this->assertEquals(['lang[1]', 'client[2]'], $result);

        $result = cPermission::permissionToArray(['lang[1]', 'client[2]']);
        $this->assertEquals(['lang[1]', 'client[2]'], $result);
    }

    /**
     * Test cPermission#checkLanguagePermission.
     *
     * @return void
     */
    public function testCheckLanguagePermission()
    {
        $languageId = 1;

        $result = cPermission::checkLanguagePermission($languageId, '');
        $this->assertEquals(false, $result);

        $result = cPermission::checkLanguagePermission($languageId, []);
        $this->assertEquals(false, $result);

        $result = cPermission::checkLanguagePermission($languageId, 'lang[2]');
        $this->assertEquals(false, $result);

        $result = cPermission::checkLanguagePermission($languageId, ['lang[2]']);
        $this->assertEquals(false, $result);

        $result = cPermission::checkLanguagePermission($languageId, 'lang[2],lang[3]');
        $this->assertEquals(false, $result);

        $result = cPermission::checkLanguagePermission($languageId, ['lang[2]', 'lang[3]']);
        $this->assertEquals(false, $result);

        $result = cPermission::checkLanguagePermission($languageId, 'lang[1]');
        $this->assertEquals(true, $result);

        $result = cPermission::checkLanguagePermission($languageId, ['lang[1]']);
        $this->assertEquals(true, $result);
    }

    /**
     * Test cPermission#checkClientPermission.
     *
     * @return void
     */
    public function testCheckClientPermission()
    {
        $clientId = 1;

        $result = cPermission::checkClientPermission($clientId, '');
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientPermission($clientId, []);
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientPermission($clientId, 'client[2]');
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientPermission($clientId, ['client[2]']);
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientPermission($clientId, 'client[2],client[3]');
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientPermission($clientId, ['client[2]', 'client[3]']);
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientPermission($clientId, 'client[1]');
        $this->assertEquals(true, $result);

        $result = cPermission::checkClientPermission($clientId, ['client[1]']);
        $this->assertEquals(true, $result);
    }


    /**
     * Test cPermission#checkClientAndLanguagePermission.
     *
     * @return void
     */
    public function testCheckClientAndLanguagePermission()
    {
        $clientId = 1;
        $languageId = 2;

        $result = cPermission::checkClientAndLanguagePermission($clientId, $languageId, '');
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientAndLanguagePermission($clientId, $languageId, []);
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientAndLanguagePermission($clientId, $languageId, 'client[2],lang[4]');
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientAndLanguagePermission($clientId, $languageId, ['client[2]', 'lang[4]']);
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientAndLanguagePermission($clientId, $languageId, 'client[1],lang[4]');
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientAndLanguagePermission($clientId, $languageId, ['client[1]', 'client[3]']);
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientAndLanguagePermission($clientId, $languageId, 'client[1],lang[2]');
        $this->assertEquals(true, $result);

        $result = cPermission::checkClientAndLanguagePermission($clientId, $languageId, ['client[1]', 'lang[2]']);
        $this->assertEquals(true, $result);
    }

    /**
     * Test cPermission#checkClientAdminPermission.
     *
     * @return void
     */
    public function testCheckClientAdminPermission()
    {
        $clientId = 1;

        $result = cPermission::checkClientAdminPermission($clientId, '');
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientAdminPermission($clientId, []);
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientAdminPermission($clientId, 'admin[2]');
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientAdminPermission($clientId, ['admin[2]']);
        $this->assertEquals(false, $result);

        $result = cPermission::checkClientAdminPermission($clientId, 'admin[1]');
        $this->assertEquals(true, $result);

        $result = cPermission::checkClientAdminPermission($clientId, ['admin[1]']);
        $this->assertEquals(true, $result);
    }

    /**
     * Test cPermission#checkAdminPermission.
     *
     * @return void
     */
    public function testCheckAdminPermission()
    {
        $result = cPermission::checkAdminPermission('');
        $this->assertEquals(false, $result);

        $result = cPermission::checkAdminPermission([]);
        $this->assertEquals(false, $result);

        $result = cPermission::checkAdminPermission('sysadmin', true);
        $this->assertEquals(false, $result);

        $result = cPermission::checkAdminPermission(['sysadmin'], true);
        $this->assertEquals(false, $result);

        $result = cPermission::checkAdminPermission('admin[1]');
        $this->assertEquals(true, $result);

        $result = cPermission::checkAdminPermission(['admin[1]']);
        $this->assertEquals(true, $result);

        $result = cPermission::checkAdminPermission('sysadmin');
        $this->assertEquals(true, $result);

        $result = cPermission::checkAdminPermission(['sysadmin']);
        $this->assertEquals(true, $result);
    }

    /**
     * Test cPermission#checkSysadminPermission.
     *
     * @return void
     */
    public function testCheckSysadminPermission()
    {
        $result = cPermission::checkSysadminPermission('');
        $this->assertEquals(false, $result);

        $result = cPermission::checkSysadminPermission([]);
        $this->assertEquals(false, $result);

        $result = cPermission::checkSysadminPermission('admin[1]');
        $this->assertEquals(false, $result);

        $result = cPermission::checkSysadminPermission(['admin[1]']);
        $this->assertEquals(false, $result);

        $result = cPermission::checkSysadminPermission('sysadmin');
        $this->assertEquals(true, $result);

        $result = cPermission::checkSysadminPermission(['sysadmin']);
        $this->assertEquals(true, $result);
    }

    /**
     * Test cPermission#checkPermission.
     *
     * @return void
     */
    public function testCheckPermission()
    {
        $haystackPerm = 'lang[1],client[2]';

        $result = cPermission::checkPermission($haystackPerm, '');
        $this->assertEquals(true, $result);

        $result = cPermission::checkPermission($haystackPerm, []);
        $this->assertEquals(true, $result);

        $result = cPermission::checkPermission($haystackPerm, 'lang[4]');
        $this->assertEquals(false, $result);

        $result = cPermission::checkPermission($haystackPerm, ['lang[4]']);
        $this->assertEquals(false, $result);

        $result = cPermission::checkPermission($haystackPerm, 'lang[4],client[2]');
        $this->assertEquals(false, $result);

        $result = cPermission::checkPermission($haystackPerm, ['lang[4]', 'client[2]']);
        $this->assertEquals(false, $result);

        $result = cPermission::checkPermission($haystackPerm, 'lang[1],client[2]');
        $this->assertEquals(true, $result);

        $result = cPermission::checkPermission($haystackPerm, ['lang[1]', 'client[2]']);
        $this->assertEquals(true, $result);
    }

    /**
     * Test cPermission#isAdmin with a cApiUser instance.
     *
     * @return void
     */
    public function testIsAdminApiUser()
    {
        $permission = new cPermission();

        $cApiUserStub = $this->createApiUserStub('client[1]');
        $this->assertEquals(false, $permission->isAdmin($cApiUserStub));

        $cApiUserStub = $this->createApiUserStub('client[1],lang[1]');
        $this->assertEquals(false, $permission->isAdmin($cApiUserStub));

        $cApiUserStub = $this->createApiUserStub('admin[1]');
        $this->assertEquals(true, $permission->isAdmin($cApiUserStub));

        $cApiUserStub = $this->createApiUserStub('sysadmin');
        $this->assertEquals(true, $permission->isAdmin($cApiUserStub));

        $cApiUserStub = $this->createApiUserStub('sysadmin');
        $this->assertEquals(false, $permission->isAdmin($cApiUserStub, true));
    }

    /**
     * Creates a mock object of cApiUser.
     *
     * @param $methodReturnValue
     * @return cApiUser|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createApiUserStub($methodReturnValue)
    {
        $stub = $this->getMockBuilder(cApiUser::class)
            ->disableOriginalConstructor()
            ->getMock();

        $stub->method('getEffectiveUserPerms')
            ->willReturn($methodReturnValue);

        return $stub;
    }

}
