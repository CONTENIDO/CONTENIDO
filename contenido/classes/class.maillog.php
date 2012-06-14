<?php
	class cApiMailLog extends ItemCollection {
		
		public function __construct($mId = false) {
			global $cfg;
			parent::__construct($cfg['tab']['mail_log'], $mId);
			
			if ($mId !== false) {
				$this->loadByPrimaryKey($mId);
			}
		}
		
		
	}
?>