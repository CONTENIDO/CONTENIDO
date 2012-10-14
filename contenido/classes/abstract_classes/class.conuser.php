<?php
/**
 * @deprecated Please use cApiUser instead [2012-02-23]
 */
abstract class ConUser_Abstract implements iConUser {
    public function __construct($aCfg, $oDb = null, $sUserId = null) {
        cDeprecated("Deprecated class. Please use cApiUser instead");
    }

    public function getUserId () {
        cDeprecated("Deprecated class. Please use cApiUser instead");
    }

    public function setUserId ($sUserId) {
        cDeprecated("Deprecated class. Please use cApiUser instead");
    }

    public function generateUserId () {
        cDeprecated("Deprecated class. Please use cApiUser instead");
    }

    public function getUserName () {
        cDeprecated("Deprecated class. Please use cApiUser instead");
    }

    public function setUserName ($sUserName) {
        cDeprecated("Deprecated class. Please use cApiUser instead");
    }

    public function setPassword ($sPassword) {
        cDeprecated("Deprecated class. Please use cApiUser instead");
    }

    protected function getPassword () {
        cDeprecated("Deprecated class. Please use cApiUser instead");
    }
}
?>