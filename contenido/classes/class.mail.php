<?php

class cMail extends PHPMailer {


    private $_isPreSendError = true;

    /**
   * Constructor
   * @param boolean $exceptions Should we throw external exceptions?
   */
  public function __construct($exceptions = false) {
    parent::__construct($exceptions);
  }


    /**
     * Log the information about sending the email.
     * @param boolean $success
     * @param Exception $exception
     */
    private function _logData($success, $exception = '') {

        $cc = $bcc = '';

        //success, header, body, $mailer, exception text
        $mailLog = new cApiMailLogCollection();
        $address = array();
        $address['from']    = $this->From;

        //extract email address
        foreach ($this->cc as $item) {
            $cc[] = $item[0];
        }
        //extract email address
        foreach ($this->bcc as $item) {
            $bcc[] = $item[0];
        }



        $address['to']         = $this->to[0][0];
        $address['cc']         = $cc;
        $address['bcc']        = $bcc;
        //get email address
        $address['reply_to']= array_keys($this->ReplyTo);
        $address['reply_to']= $this->ReplyTo[$address['reply_to'][0]][0];

        $mailLog->saveLog($success, $this->MIMEHeader, $this->MIMEBody, $this->Mailer, $address, $this->Subject, $exception);

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
            $this->_logData(false, $e->getMessage());
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
            $this->_isPreSendError = false;
            $ret = $this->PostSend();
            $this->_logData($ret, $this->ErrorInfo);
            return $ret ;
        } catch (phpmailerException $e) {
            $this->SentMIMEMessage = '';
            $this->SetError($e->getMessage());
            $this->_logData(false, $e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
    }


}
?>