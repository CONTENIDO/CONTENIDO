<?php

class cMail extends PHPMailer {
	
	/**
	 * Log the information about sending the email.
	 * @param boolean $success
	 * @param Exception $exception
	 */
	private function _logData($success , $header, $body , $mailer, Exception $exception = '') {
		
		//success, header, body, $mailer, exception text
	}
	
	public function resendEmail($header, $body, $mailer) {
		
		
		$this->MIMEHeader = $header;
		$this->MIMEBody = $body;
		$this->Mailer = $mailer;
		
		
		try {
			$ret = $this->PostSend();
			$this->_logData($ret);
			return ret ;
		} catch (phpmailerException $e) {
			$this->SentMIMEMessage = '';
			$this->SetError($e->getMessage());
			$this->_logData(false, $e);
			if ($this->exceptions) {
				throw $e;
			}
			return false;
		}
		
		
	}
	
	
	public function Send() {
		try {
			if(!$this->PreSend())  {
				$this->_logData(false);
				return false;
			}
			$ret = $this->PostSend();
			$this->_logData($ret);
			return ret ;
		} catch (phpmailerException $e) {
			$this->SentMIMEMessage = '';
			$this->SetError($e->getMessage());
			$this->_logData(false, $e);
			if ($this->exceptions) {
				throw $e;
			}
			return false;
		}
	}
	
	
}
?>