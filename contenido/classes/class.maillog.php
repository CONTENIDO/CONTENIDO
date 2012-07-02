<?php

    class cApiMailLog extends Item  {

        public function __construct($mId = false)
        {
            global $cfg;
            parent::__construct($cfg['tab']['mail_log'], 'idmail');

        }
    }

    class cApiMailLogCollection extends ItemCollection{

        private $_idclient = '';

        private $_idlang = '';

        protected $mailLogDirectory = '';
        protected $mailLogEnding = 'mail';

        public function __construct($mId = false) {
            global $cfg, $client, $lang;

            $this->mailLogDirectory = $this->_cronlogDirectory = $cfg['path']['contenido_maillog'] . '/';
            $this->_idclient = $client;
            $this->_idlang = $lang;

            parent::__construct($cfg['tab']['mail_log'], 'idmail');

            if ($mId !== false) {
                $this->loadByPrimaryKey($mId);
            }
            $this->_setItemClass('cApiMailLog');
        }

        /**
         * Delete body and head files of email.
         * @param cApiMailLog $oItem
         */
        public function deleteFiles(cApiMailLog $oItem) {

            $fileName = md5($oItem->getField('idmail'). $oItem->getField('created'));
            unlink($this->mailLogDirectory . 'body_'. $fileName .'.'. $this->mailLogEnding);
            unlink($this->mailLogDirectory . 'head_'. $fileName .'.'. $this->mailLogEnding);
        }

        /**
         * Get the body content of email
         * @param cApiMailLog $oItem
         * @return string content of the file of body
         */
        public function getBody(cApiMailLog $oItem){
            $fileName = md5($oItem->getField('idmail'). $oItem->getField('created'));
            return file_get_contents($this->mailLogDirectory . 'body_'. $fileName .'.'. $this->mailLogEnding);
        }


        /**
         * Get the head content of email
         * @param cApiMailLog $oItem
         * @return string content of the file of head
         */
        public function getHeader(cApiMailLog $oItem) {

            $fileName = md5($oItem->getField('idmail'). $oItem->getField('created'));
            return file_get_contents($this->mailLogDirectory . 'header_'. $fileName .'.'. $this->mailLogEnding);
        }

        /**
         * Save Email log
         *
         * @param unknown_type $success
         * @param unknown_type $header
         * @param unknown_type $body
         * @param unknown_type $mailer
         * @param unknown_type $address
         * @param unknown_type $subject
         * @param unknown_type $exception
         */
        public function saveLog($success, $header, $body, $mailer, $address, $subject,  $exception = '') {

            $item = parent::createNewItem();
            $success = ($success == true);
            $item->set('success', $success);
            $item->set('mailer', $mailer);
            $item->set('idclient', $this->_idclient);
            $item->set('idlang', $this->_idlang);
            $date =  date('Y-m-d H:i:s', time());
            $item->set('from', $address['from']);
            $item->set('to', $address['to']);
            $item->set('cc', implode('+', $address['cc']));
            $item->set('bcc', implode('+', $address['bcc']));
            $item->set('reply_to', $address['reply_to']);
            $item->set('created', $date, false);
            $item->set('subject', $subject);
            $item->set('exception', $exception);
            $fileName = md5($item->getField('idmail'). $date);

            if (file_put_contents($this->mailLogDirectory . 'body_'. $fileName .'.'. $this->mailLogEnding, $body) === false) {
                return false;
            }

            if (file_put_contents($this->mailLogDirectory . 'header_'.$fileName .'.'. $this->mailLogEnding, $header) === false) {
                return false;
            }




            $item->store();

            return true;


        }


    }
?>