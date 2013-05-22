<?php

 /**
  *  This module is used to start the automatic keyword generation progress
  *  and SHOULD NOT be used permanently because of performance reasons.
  *
  *
  *
  * @package Module
  * @subpackage AutoIndexing
  * @version SVN Revision $Rev:$
  * @author claus.schunk
  * @copyright four for business AG
  * @link http://www.4fb.de
  */
 defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

 cInclude('frontend', 'classes/class.kvh.auto_indexing.php');

AutoIndexing::start();
?>