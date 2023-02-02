<?php

/**
 * This file contains the PifaFormCollection & PifaForm class.
 *
 * @package    Plugin
 * @subpackage FormAssistant
 * @author     Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright  four for business AG
 * @link       http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * PIFA form item collection class.
 * It's a kind of model.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 * @method PifaForm createNewItem
 * @method PifaForm|bool next
 */
class PifaFormCollection extends ItemCollection {
    /**
     * Create an instance.
     *
     * @param mixed $where clause to be used to load items or false
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function __construct($where = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct(cRegistry::getDbTableName('pifa_form'), 'idform');
        $this->_setItemClass('PifaForm');
        if (false !== $where) {
            $this->select($where);
        }
    }

    /**
     * Get forms according to given params.
     *
     * @param int $client
     * @param int $lang
     *
     * @return PifaFormCollection|bool
     * @throws cDbException
     */
    private static function _getBy($client, $lang) {

        // conditions to be used for reading items
        $conditions = [];

        // consider $client
        $client = cSecurity::toInteger($client);
        if (0 < $client) {
            $conditions[] = '`idclient`=' . $client;
        }

        // consider $lang
        $lang = cSecurity::toInteger($lang);
        if (0 < $lang) {
            $conditions[] = '`idlang`=' . $lang;
        }

        // get items
        $forms = new PifaFormCollection();
        $succ = $forms->select(implode(' AND ', $conditions));

        // throw exception if forms coud not be read
        // Its not a good idea to throw an exception in this case,
        // cause this would lead to an error message if no forms
        // were created yet.
        // if (false === $succ) {
        // throw new PifaException('forms could not be read');
        // }
        // better return false in this case
        if (false === $succ) {
            return false;
        }

        return $forms;
    }

    /**
     * Get forms of given client in any language.
     *
     * @param int $client
     *
     * @return PifaFormCollection
     * @throws PifaException if forms could not be read
     * @throws cDbException
     */
    public static function getByClient($client) {
        if (0 >= cSecurity::toInteger($client)) {
            $msg = Pifa::i18n('MISSING_CLIENT');
            throw new PifaException($msg);
        }

        return self::_getBy($client, 0);
    }

    /**
     * Get forms of any client in given language.
     *
     * @param int $lang
     *
     * @return PifaFormCollection
     * @throws PifaException if forms could not be read
     * @throws cDbException
     */
    public static function getByLang($lang) {
        if (0 >= cSecurity::toInteger($lang)) {
            $msg = Pifa::i18n('MISSING_LANG');
            throw new PifaException($msg);
        }

        return self::_getBy(0, $lang);
    }

    /**
     * Get forms of given client in given language.
     *
     * @param int $client
     * @param int $lang
     *
     * @return PifaFormCollection
     * @throws PifaException if forms could not be read
     * @throws cDbException
     */
    public static function getByClientAndLang($client, $lang) {
        if (0 >= cSecurity::toInteger($client)) {
            $msg = Pifa::i18n('MISSING_CLIENT');
            throw new PifaException($msg);
        }

        if (0 >= cSecurity::toInteger($lang)) {
            $msg = Pifa::i18n('MISSING_LANG');
            throw new PifaException($msg);
        }

        return self::_getBy($client, $lang);
    }

}

/**
 * PIFA form item class.
 * It's a kind of model.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaForm extends Item {

    /**
     * aggregated collection of this form fields
     *
     * @var array
     */
    private $_fields = NULL;

    /**
     * array of errors with field names as keys and error messages as values
     *
     * @var array
     */
    private $_errors = [];

    /**
     * @var int lastInsertedId
     */
    private $_lastInsertedId = NULL;

    /**
     * Create an instance.
     *
     * @param mixed $id ID of item to be loaded or false
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($id = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct(cRegistry::getDbTableName('pifa_form'), 'idform');
        $this->setFilters([], []);
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * @return array
     */
    public function getErrors() {
        return $this->_errors;
    }

    /**
     * @param array $_errors
     */
    public function setErrors($_errors) {
        $this->_errors = $_errors;
    }

    /**
     * Read this forms fields from database and aggregate them.
     */
    public function loadFields() {
        $col = new PifaFieldCollection();
        $col->setWhere('PifaFieldCollection.idform', $this->get('idform'));
        $col->setOrder('PifaFieldCollection.field_rank');
        $col->query();
        $this->_fields = [];
        while (false !== $pifaField = $col->next()) {
            $this->_fields[] = clone $pifaField;
        }
    }

    /**
     * Returns aggregated list of PIFA fields.
     * If no fields are aggregated, this forms fields are read from database and
     * aggregated.
     *
     * @return PifaField[]
     */
    public function getFields() {
        if (NULL === $this->_fields) {
            $this->loadFields();
        }

        return $this->_fields;
    }

    /**
     * @return int
     */
    public function getLastInsertedId() {
        return $this->_lastInsertedId;
    }

    /**
     * @param int $_lastInsertedId
     */
    public function setLastInsertedId($_lastInsertedId) {
        $this->_lastInsertedId = $_lastInsertedId;
    }

    /**
     * Returns an array containing current values of all fields of this form
     * where the fields column name is used as key.
     *
     * @return array
     */
    public function getValues() {
        $values = [];
        foreach ($this->getFields() as $pifaField) {
            // ommit fields which are not stored in database
            try {
                $isStored = NULL !== $pifaField->getDbDataType();
            } catch (PifaException $e) {
                $isStored = false;
            }
            if (false === $isStored) {
                continue;
            }
            $values[$pifaField->get('column_name')] = $pifaField->getValue();
        }

        return $values;
    }

    /**
     * Sets values for this form fields.
     *
     * The given data array is searched for keys corresponding to this form
     * field names. Other values are omitted. This method is meant to be called
     * with the $_GET or $_POST superglobal variables. Validation is performed
     * according to the specifications defined for each form field.
     *
     * @param array|null $values
     * @param bool $clear if missing values should be interpreted as NULL
     */
    public function setValues(array $values = NULL, $clear = false) {
        if (NULL === $values) {
            return;
        }

        foreach ($this->getFields() as $pifaField) {
            $columnName = $pifaField->get('column_name');
            if (array_key_exists($columnName, $values)) {
                $value = $values[$columnName];
                $pifaField->setValue($value);
            } elseif (true === $clear) {
                $pifaField->setValue(NULL);
            }
        }
    }

    /**
     * Returns an array containing uploaded files of all fields of this form
     * where the fields column name is used as key.
     *
     * @return array:mixed
     */
    public function getFiles() {
        $files = [];
        foreach ($this->getFields() as $pifaField) {
            // omit fields that are not an INPUTFILE
            if (PifaField::INPUTFILE !== cSecurity::toInteger($pifaField->get('field_type'))) {
                continue;
            }
            $files[$pifaField->get('column_name')] = $pifaField->getFile();
        }

        return $files;
    }

    /**
     * Sets uploaded file(s) for appropriate form fields.
     *
     * @param array $files super global files array
     */
    public function setFiles(array $files = NULL) {
        if (NULL === $files) {
            return;
        }

        foreach ($this->getFields() as $pifaField) {
            // omit fields that are not an INPUTFILE
            if (PifaField::INPUTFILE !== cSecurity::toInteger($pifaField->get('field_type'))) {
                continue;
            }
            $columnName = $pifaField->get('column_name');
            if (array_key_exists($columnName, $files)) {
                $file = $files[$columnName];
                $pifaField->setFile($file);
                // store original name of uploaded file as value!
                $pifaField->setValue($file['name']);
            }
        }
    }

    /**
     * Getter for protected prop.
     *
     * @return string
     */
    public function getLastError() {
        return $this->lasterror;
    }

    /**
     */
    public function fromForm() {
        // get data from source depending on method
        switch (cString::toUpperCase($this->get('method'))) {
            case 'GET':
                $this->setValues($_GET);
                break;
            case 'POST':
                $this->setValues($_POST);
                if (isset($_FILES)) {
                    $this->setFiles($_FILES);
                }
                break;
        }
    }

    /**
     * Returns HTML for this form that should be displayed in frontend.
     *
     * @param array|null $opt to determine form attributes
     * @return string
     */
    public function toHtml(array $opt = NULL) {
        // get form attribute values
        $opt = array_merge([
            // or whatever
            'name' => 'pifa-form',
            'action' => 'main.php',
            'method' => $this->get('method'),
            'class' => 'pifa-form jqtransform'
        ], $opt);
        $idform = $this->get('idform');
        $headline = '';
        if (isset($opt['headline']) && cString::getStringLength($opt['headline']) > 0) {
            $headline = '<h1 class="pifa-headline">' . $opt['headline'] . '</h1>';
        }

        // build form
        $htmlForm = new cHTMLForm($opt['name'], $opt['action'], $opt['method'], $opt['class']);

        // set ID (workaround: remove ID first!)
        $htmlForm->removeAttribute('id')->setID('pifa-form-' . $idform);

        // add hidden input field with idform in order to be able to distinguish
        // several forms on a single page when one of them is submitted
        $htmlForm->appendContent("<input type=\"hidden\" name=\"idform\" value=\"$idform\">");

        // add fields
        foreach ($this->getFields() as $pifaField) {
            // enable file upload
            if (PifaField::INPUTFILE === cSecurity::toInteger($pifaField->get('field_type'))) {
                $htmlForm->setAttribute('enctype', 'multipart/form-data');
            }
            $errors = $this->getErrors();
            $htmlField = $pifaField->toHtml($errors);
            if (NULL !== $htmlField) {
                $htmlForm->appendContent($htmlField);
            }
        }
        $htmlForm->appendContent("\n");

        return $headline . $htmlForm->render();
    }

    /**
     * Loops all fields and checks their value for being obligatory
     * and conforming to the fields rule.
     *
     * @throws PifaValidationException if at least one field was invalid
     */
    public function validate() {
        // validate all fields
        $errors = [];
        foreach ($this->getFields() as $pifaField) {
            try {
                $pifaField->validate();
            } catch (PifaValidationException $e) {
                // $errors = array_merge($errors, $e->getErrors());
                foreach ($e->getErrors() as $idfield => $error) {
                    $errors[$idfield] = $error;
                }
            }
        }

        // if some fields were invalid
        if (0 < count($errors)) {
            // throw a single PifaValidationException with infos for all invalid
            // fields
            throw new PifaValidationException($errors);
        }
    }

    /**
     * Stores the loaded and modified item to the database.
     *
     * In contrast to its parent method this store() method returns true even if
     * there were no modiifed values and thus no statement was executed. This
     * helps in handling database errors.
     *
     * @todo Check if method store() should be implemented for PifaField too.
     * @return bool
     */
    public function store() {
        if (is_null($this->modifiedValues)) {
            return true;
        } else {
            return parent::store();
        }
    }

    /**
     * Stores values of each field of this form in defined data table.
     * For fields of type INPUT_FILE the uploaded file is stored in the
     * FileSystem (in $cfg['path']['contenido_cache'] . 'form_assistant/').
     *
     * @throws PifaDatabaseException if values could not be stored
     * @throws PifaException
     * @throws cDbException
     */
    public function storeData() {
        $cfg = cRegistry::getConfig();

        // get values for all defined fields
        $values = $this->getValues();

        // make arrays of values storable
        foreach ($values as $column => $value) {
            if (is_array($value)) {
                $values[$column] = implode(',', $value);
            }
        }

        // get DB
        $db = cRegistry::getDb();

        // build insert statement
        $sql = $db->buildInsert($this->get('data_table'), $values);

        if (NULL === $db->connect()) {
            $msg = Pifa::i18n('DATABASE_CONNECT_ERROR');
            throw new PifaDatabaseException($msg);
        }
        if (0 === cString::getStringLength(trim($sql))) {
            $msg = Pifa::i18n('SQL_BUILD_ERROR');
            throw new PifaDatabaseException($msg);
        }

        // insert new row
        if (false === $db->query($sql)) {
            $msg = Pifa::i18n('VALUE_STORE_ERROR');
            throw new PifaDatabaseException($msg);
        }

        // get last insert id
        $lastInsertedId = $db->getLastInsertedId();

        $this->setLastInsertedId($lastInsertedId);

        // store files
        $files = $this->getFiles();
        foreach ($this->getFiles() as $column => $file) {
            if (!is_array($file)) {
                continue;
            }
            $tmpName = $file['tmp_name'];
            // if no file was submitted tmp_name is an empty string
            if (0 === cString::getStringLength($tmpName)) {
                continue;
            }
            $destPath = $cfg['path']['contenido_cache'] . 'form_assistant/';
            // CON-1566 create folder (create() checks if it exists!)
            if (!cDirHandler::create($destPath)) {
                $msg = Pifa::i18n('FOLDER_CREATE_ERROR');
                throw new PifaException($msg);
            }
            $destName = $this->get('data_table') . '_' . $lastInsertedId . '_' . $column;
            $destName = preg_replace('/[^a-z0-9_]+/i', '_', $destName);
            if (false === move_uploaded_file($tmpName, $destPath . $destName)) {
                $msg = Pifa::i18n('FILE_STORE_ERROR');
                throw new PifaException($msg);
            }
        }
    }

    /**
     * @param array $opt
     *
     * @throws PifaException
     * @throws PifaMailException
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function toMailRecipient(array $opt) {
        if (0 == cString::getStringLength(trim($opt['from']))) {
            $msg = Pifa::i18n('MISSING_SENDER_ADDRESS');
            throw new PifaMailException($msg);
        }
        if (0 == cString::getStringLength(trim($opt['fromName']))) {
            $msg = Pifa::i18n('MISSING_SENDER_NAME');
            throw new PifaMailException($msg);
        }
        if (0 == cString::getStringLength(trim($opt['to']))) {
            $msg = Pifa::i18n('MISSING_RECIPIENT_ADDRESS');
            throw new PifaMailException($msg);
        }
        if (0 == cString::getStringLength(trim($opt['subject']))) {
            $msg = Pifa::i18n('MISSING_SUBJECT');
            throw new PifaMailException($msg);
        }
        if (0 == cString::getStringLength(trim($opt['body']))) {
            $msg = Pifa::i18n('MISSING_EMAIL_BODY');
            throw new PifaMailException($msg);
        }

        // cMailer

        try {
            $mailer = new cMailer();
            $message = Swift_Message::newInstance($opt['subject'], $opt['body'], 'text/plain', $opt['charSet']);

            // add attachments by names
            if (array_key_exists('attachmentNames', $opt)) {
                if (is_array($opt['attachmentNames'])) {
                    $values = $this->getValues();
                    foreach ($opt['attachmentNames'] as $column => $path) {
                        if (!file_exists($path)) {
                            continue;
                        }
                        $attachment = Swift_Attachment::fromPath($path);
                        $filename = $values[$column];
                        $attachment->setFilename($filename);
                        $message->attach($attachment);
                    }
                }
            }

            // add attachments by string
            if (array_key_exists('attachmentStrings', $opt)) {
                if (is_array($opt['attachmentStrings'])) {
                    foreach ($opt['attachmentStrings'] as $filename => $string) {
                        // TODO mime type should be configurable
                        $attachment = Swift_Attachment::newInstance($string, $filename, 'text/csv');
                        $message->attach($attachment);
                    }
                }
            }

            // add sender
            $message->addFrom($opt['from'], $opt['fromName']);

            // add recipient
            $to = explode(',', $opt['to']);
            $message->setTo(array_combine($to, $to));

            if (array_key_exists('replyTo', $opt) && !empty($opt['replyTo'])) {
                $message->setReplyTo($opt['replyTo']);
            }
        } catch (Exception $e) {
            throw new PifaException($e->getMessage());
        }
        // send mail
        if (!$mailer->send($message)) {
			$msg = mi18n("PIFA_MAIL_ERROR_SUFFIX");
            throw new PifaMailException($msg);
        }
    }

    /**
     * Returns an array containing this forms stored data.
     *
     * @return array
     * @throws PifaException
     * @throws cDbException
     */
    public function getData() {
        if (!$this->isLoaded()) {
            $msg = Pifa::i18n('FORM_LOAD_ERROR');
            throw new PifaException($msg);
        }

        $db = cRegistry::getDb();

        // get table name and check if it exists
        $tableName = $this->get('data_table');
        if (!$this->existsTable($tableName, false)) {
            $msg = Pifa::i18n('MISSING_TABLE_ERROR');
            throw new PifaException($msg);
        }

        // build SQL
        $sql = "-- PifaForm->getData()
            SELECT
                *
            FROM
                `$tableName`
            ;";

        try {
            $succ = $db->query($sql);
        } catch (cDbException $e) {
            $succ = false;
        }

        if (false === $succ) {
            return [];
        }

        if (0 === $db->numRows()) {
            return [];
        }

        try {
            $data = [];
            while ($db->nextRecord()) {
                $data[] = $db->toArray();
            }
        } catch (cDbException $e) {
            $data = [];
        }

        return $data;
    }

    /**
     * Echoes a CSV file containing all of this forms stored data.
     * That for proper headers are sent, that add the created file as attachment
     * for easier download.
     *
     * @param string $optionally
     *
     * @return string
     * @throws PifaException if table does not exist
     * @throws cDbException
     */
    public function getDataAsCsv($optionally = 'OPTIONALLY') {
        $cfg = cRegistry::getConfig();

        if (in_array($cfg['db']['connection']['host'], [
            '127.0.0.1',
            'localhost'
        ])) {
            // This solution is cool, but won't work, due to the fact that in
            // our database server is not the web server.
            // $out = $this->_getCsvFromLocalDatabaseServer();

            // there seems to be a problem using _getCsvFromLocalDatabaseServer
            // so _getCsvFromRemoteDatabaseServer is used in every case
            $out = $this->_getCsvFromRemoteDatabaseServer();
        } else {
            $out = $this->_getCsvFromRemoteDatabaseServer();
        }

        // return payload
        return $out;
    }

    /**
     * @param string $optionally
     *
     * @return bool|string
     * @throws PifaException if table does not exist
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    private function _getCsvFromLocalDatabaseServer($optionally = 'OPTIONALLY') {
        // assert form is loaded
        if (!$this->isLoaded()) {
            $msg = Pifa::i18n('FORM_LOAD_ERROR');
            throw new PifaException($msg);
        }

        // get table name and check if it exists
        $tableName = $this->get('data_table');
        if (!$this->existsTable($tableName, false)) {
            $msg = Pifa::i18n('MISSING_TABLE_ERROR');
            throw new PifaException($msg);
        }

        // assert $optionally to be either 'OPTIONALLY' or ''
        if ('OPTIONALLY' !== $optionally) {
            $optionally = '';
        }

        // create temp file
        $cfg = cRegistry::getConfig();
        $filename = tempnam($cfg['path']['contenido_cache'], 'PIFA_');
        unlink($filename);

        // build SQL
        $sql = "-- PifaForm->_getCsvFromLocalDatabaseServer()
            SELECT
                *
            INTO OUTFILE
                '$filename'
            FIELDS TERMINATED BY
                ','
            $optionally ENCLOSED BY
                '\"'
            ESCAPED BY
                '\\\\'
            LINES TERMINATED BY
                '\\n'
            FROM
                `$tableName`
            ;";

        // execute SQL
        cRegistry::getDb()->query($sql);

        // get content
        $out = cFileHandler::read($filename);

        // delete temp file
        unlink($filename);

        return $out;
    }

    /**
     * @todo use fputcsv()
     * @return string
     * @throws PifaException if table does not exist
     * @throws cDbException
     */
    private function _getCsvFromRemoteDatabaseServer() {
        // get column names in correct order
        $columns = [];
        // always append the records ID
        array_push($columns, 'id');
        // append the records timestamp if defined for form
        if (true === (bool) $this->get('with_timestamp')) {
            array_push($columns, 'pifa_timestamp');
        }
        foreach ($this->getFields() as $index => $pifaField) {
            // CON-2169 filter empty values
            if (cString::getStringLength(trim($pifaField->get('column_name'))) > 0) {
                $columns[] = $pifaField->get('column_name');
            }
        }

        $out = '';

        // add header row
        foreach ($columns as $index => $columnName) {
            if (0 < $index) {
                $out .= ';';
            }
            $out .= $columnName;
        }

        function pifa_form_get_literal_line_endings($value) {
            $value = str_replace("\n", '\n', $value);
            $value = str_replace("\r", '\r', $value);
            $value = "\"$value\"";
            return $value;
        }

        // add data rows
        foreach ($this->getData() as $row) {
            // replace \n & \r by it's literal representation
            $row = array_map('pifa_form_get_literal_line_endings', $row);
            // append value
            foreach ($columns as $index => $columnName) {
                $out .= 0 === $index? "\n" : ';';
                $out .= $row[$columnName];
            }
        }

        return $out;
    }

    /**
     * This method returns the current data as CSV file.
     * This file usually contains two rows, one header and one value line.
     * If $oneRowPerField is set to true the CSV-file is mirrored so that each
     * line contains the fields header and then its value.
     * An assoc array of $additionalFields can be given which will be appended
     * to the current values of this form.
     * (CON-1648)The CSV is created using a temporary file in the systems (not
     * CONTENIDOs) TEMP folder.
     *
     * @param bool $oneRowPerField
     * @param array $additionalFields
     * @return string
     */
    public function getCsv($oneRowPerField = false, array $additionalFields = NULL) {
        // get values to be converted into CSV
        $data = $this->getValues();

        // add additional fields if given
        if (NULL !== $additionalFields) {
            $data = array_merge($data, $additionalFields);
        }

        // initializing toCsv variable (CON-2051)
        $toCsv = '';

        // convert array values to CSV values
        $data = array_map(function($in) {
            return implode(',', $in);;
        }, $data);

        // optionally rearrange/mirror array
        if (!$oneRowPerField) {
            $data = [
                array_keys($data),
                array_values($data)
            ];
        }

        // == create CSV (CON-1648)
        $csv = '';
        // write all lines of data as CSV into tmp file
        $total = 0;
        if (false !== $tmpfile = tmpfile()) {
            foreach ($data as $line) {
                $length = fputcsv($tmpfile, $data, ';', '"');
                if (false !== $length) {
                    $total += $length;
                }
            }
        }
        // read CSV from tmp file and delete it
        if (0 < $total) {
            $csv = (string) fread($tmpfile, $length);
            fclose($tmpfile);
        }

        return $csv;
    }

    /**
     * @param string $tableName
     * @param bool   $bySchema
     *
     * @return bool
     * @throws PifaException if existence of table could not be determined
     * @throws cDbException
     * @see http://www.electrictoolbox.com/check-if-mysql-table-exists/
     */
    public function existsTable($tableName, $bySchema = false) {
        $cfg = cRegistry::getConfig();

        // prepare statement
        if (true === $bySchema) {
            // using the information schema
            $sql = "-- PifaForm->existsTable()
                SELECT
                    *
                FROM
                    `information_schema.tables`
                WHERE
                    table_schema = '" . $cfg['db']['connection']['database'] . "'
                    AND table_name = '$tableName'
                ;";
        } else {
            // using show tables
            $sql = "-- PifaForm->existsTable()
                SHOW TABLES
                LIKE
                    '$tableName';
                ;";
        }

        // check table
        $db = cRegistry::getDb();
        if (false === $db->query($sql)) {
            $msg = Pifa::i18n('TABLE_CHECK_ERROR');
            $msg = sprintf($msg, $db->getErrorMessage());
            throw new PifaException($msg);
        }

        return (bool) (0 !== $db->numRows());
    }

    /**
     * Create data table for form if it does not already exist.
     * If there are any fields defined for this form, their columns will be
     * created too! N.b. these fields don't have to be aggregated yet. They will
     * be read from database if this form does not aggregate them yet.
     *
     * @param bool $withTimestamp if table should include column for timestamp
     *
     * @throws PifaException if table could not be created
     * @throws cDbException
     */
    public function createTable($withTimestamp) {
        if (!$this->isLoaded()) {
            $msg = Pifa::i18n('FORM_LOAD_ERROR');
            throw new PifaException($msg);
        }

        // get & check table name
        $tableName = $this->get('data_table');
        if ($this->existsTable($tableName)) {
            $msg = Pifa::i18n('TABLE_EXISTS_ERROR');
            $msg = sprintf($msg, $tableName);
            throw new PifaException($msg);
        }

        // prepare column definitions
        $createDefinitions = [];
        array_push($createDefinitions, "id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'primary key'");
        if ($withTimestamp) {
            array_push($createDefinitions, "pifa_timestamp TIMESTAMP NOT NULL COMMENT 'automatic PIFA timestamp'");
        }
        // read fields from DB if none are found!
        if (NULL === $this->_fields) {
            $this->loadFields();
        }
        foreach ($this->_fields as $pifaField) {
            $columnName = $pifaField->get('column_name');
            // skip fields w/o column
            if (0 === cString::getStringLength(trim($columnName))) {
                continue;
            }
            $dataType = $pifaField->getDbDataType();
            array_push($createDefinitions, "`$columnName` $dataType");
        }
        $createDefinitions = join(',', $createDefinitions);

        // prepare statement
        $sql = "-- PifaForm->createTable()
            CREATE TABLE
                -- IF NOT EXISTS
                `$tableName`
            ($createDefinitions)
            ENGINE=MyISAM
            DEFAULT CHARSET=utf8
            ;";

        // create table
        $db = cRegistry::getDb();
        if (false === $db->query($sql)) {
            $msg = Pifa::i18n('TABLE_CREATE_ERROR');
            throw new PifaException($msg);
        }
    }

    /**
     * Alter data table.
     * Renames data table if name has changed and adds or drops column for
     * timestamp if setting has changed.
     *
     * HINT: passing the old values is correct!
     * The new values have already been stored inside the pifaForm object!
     *
     * @param string $oldTableName
     * @param bool   $oldWithTimestamp
     *
     * @throws PifaException if form is not loaded
     * @throws cDbException
     */
    public function alterTable($oldTableName, $oldWithTimestamp) {
        if (!$this->isLoaded()) {
            $msg = Pifa::i18n('FORM_LOAD_ERROR');
            throw new PifaException($msg);
        }

        // get & check table name
        $tableName = $this->get('data_table');

        // rename data table if name has changed
        if ($oldTableName !== $tableName) {
            if ($this->existsTable($tableName)) {
                $this->set('data_table', $oldTableName);
            } else {
                $sql = "-- PifaForm->alterTable()
                    RENAME TABLE
                        `$oldTableName`
                    TO
                        `$tableName`
                    ;";
                cRegistry::getDb()->query($sql);
            }
        }

        // adds or drop column for timestamp if setting has changed.
        $withTimestamp = $this->get('with_timestamp');
        if ($oldWithTimestamp != $withTimestamp) {
            if ($withTimestamp) {
                $sql = "-- PifaForm->alterTable()
                    ALTER TABLE
                        `$tableName`
                    ADD
                        `pifa_timestamp`
                    TIMESTAMP
                    NOT NULL
                    COMMENT
                        'automatic PIFA timestamp'
                    AFTER id
                    ;";
            } else {
                $sql = "-- PifaForm->alterTable()
                    ALTER TABLE
                        `$tableName`
                    DROP
                        `pifa_timestamp`
                    ;";
            }
            cRegistry::getDb()->query($sql);
        }
    }

    /**
     * @param PifaField $pifaField
     * @param string    $oldColumnName
     *
     * @throws PifaException if field is not loaded
     * @throws cDbException
     */
    public function storeColumn(PifaField $pifaField, $oldColumnName) {
        if (!$this->isLoaded()) {
            $msg = Pifa::i18n('FORM_LOAD_ERROR');
            throw new PifaException($msg);
        }
        if (!$pifaField->isLoaded()) {
            $msg = Pifa::i18n('FIELD_LOAD_ERROR');
            throw new PifaException($msg);
        }

        $columnName = $pifaField->get('column_name');
        $dataType = $pifaField->getDbDataType();

        if (0 === cString::getStringLength(trim($oldColumnName))) {
            if (0 === cString::getStringLength(trim($columnName))) {
                // PASS
            } else {
                $this->addColumn($columnName, $dataType);
            }
        } else {
            if (0 === cString::getStringLength(trim($columnName))) {
                $this->dropColumn($oldColumnName);
            } else {
                $this->changeColumn($columnName, $dataType, $oldColumnName);
            }
        }
    }

    /**
     * rename column if name has changed
     *
     * @param string $columnName
     * @param string $dataType
     * @param string $oldColumnName
     *
     * @throws PifaException if column could not be changed
     * @throws cDbException
     */
    public function changeColumn($columnName, $dataType, $oldColumnName) {
        $tableName = $this->get('data_table');

        if ($oldColumnName === $columnName) {
            return;
        }
        if (true === $this->_existsColumn($columnName)) {
            $msg = Pifa::i18n('COLUMN_EXISTS_ERROR');
            $msg = sprintf($msg, $columnName);
            throw new PifaException($msg);
        }
        if (NULL === $dataType) {
            return;
        }

        $sql = "-- PifaForm->changeColumn()
            ALTER TABLE
                `$tableName`
            CHANGE
                `$oldColumnName`
                `$columnName` $dataType
            ;";

        $db = cRegistry::getDb();
        if (false === $db->query($sql)) {
            $msg = Pifa::i18n('COLUMN_ALTER_ERROR');
            throw new PifaException($msg);
        }
    }

    /**
     * Adds a column for the current field to the table of the current form.
     *
     * @param string $columnName
     *
     * @throws PifaException if column already exists
     * @throws cDbException
     */
    public function dropColumn($columnName) {
        $tableName = $this->get('data_table');
        if (false === $this->_existsColumn($columnName)) {
            $msg = Pifa::i18n('COLUMN_EXISTS_ERROR');
            $msg = sprintf($msg, $columnName);
            throw new PifaException($msg);
        }

        $sql = "-- PifaForm->dropColumn()
            ALTER TABLE
                `$tableName`
            DROP
                `$columnName`
            ;";

        $db = cRegistry::getDb();
        if (false === $db->query($sql)) {
            $msg = Pifa::i18n('COLUMN_DROP_ERROR');
            throw new PifaException($msg);
        }
    }

    /**
     * Adds a column for the current field to the table of the current form.
     *
     * @param string $columnName
     * @param string $dataType
     *
     * @throws PifaException if field is not loaded
     * @throws cDbException
     */
    public function addColumn($columnName, $dataType) {
        $tableName = $this->get('data_table');
        if (true === $this->_existsColumn($columnName)) {
            $msg = Pifa::i18n('COLUMN_EXISTS_ERROR');
            $msg = sprintf($msg, $columnName);
            throw new PifaException($msg);
        }
        if (NULL === $dataType) {
            return;
        }

        $sql = "-- PifaForm->addColumn()
               ALTER TABLE
                   `$tableName`
               ADD
                   `$columnName` $dataType
            ;";

        $db = cRegistry::getDb();
        if (false === $db->query($sql)) {
            $msg = Pifa::i18n('COLUMN_ADD_ERROR');
            throw new PifaException($msg);
        }
    }

    /**
     * @param string $columnName
     *
     * @return boolean
     * @throws PifaException if columns could not be read
     * @throws cDbException
     */
    protected function _existsColumn($columnName) {
        $tableName = $this->get('data_table');
        $sql = "-- PifaForm->_existsColumn()
            SHOW FIELDS FROM
                `$tableName`
            ;";

        $db = cRegistry::getDb();
        if (false === $db->query($sql)) {
            $msg = Pifa::i18n('COLUMNS_LOAD_ERROR');
            throw new PifaException($msg);
        }

        // Field, Type, Null, Key, Default, Extra
        while (false !== $db->nextRecord()) {
            $field = $db->toArray();
            if (cString::toLowerCase($field['Field']) == cString::toLowerCase($columnName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deletes this form with all its fields and stored data.
     * The forms data table is also dropped.
     *
     * @throws PifaException
     * @throws cDbException
     */
    public function delete() {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();

        if (!$this->isLoaded()) {
            $msg = Pifa::i18n('FORM_LOAD_ERROR');
            throw new PifaException($msg);
        }

        // delete form
        $sql = "-- PifaForm->delete()
            DELETE FROM
                `" . cRegistry::getDbTableName('pifa_form') . "`
            WHERE
                idform = " . cSecurity::toInteger($this->get('idform')) . "
            ;";
        if (false === $db->query($sql)) {
            $msg = Pifa::i18n('FORM_DELETE_ERROR');
            throw new PifaException($msg);
        }

        // delete fields
        $sql = "-- PifaForm->delete()
            DELETE FROM
                `" . cRegistry::getDbTableName('pifa_field') . "`
            WHERE
                idform = " . cSecurity::toInteger($this->get('idform')) . "
            ;";
        if (false === $db->query($sql)) {
            $msg = Pifa::i18n('FIELDS_DELETE_ERROR');
            throw new PifaException($msg);
        }

        // drop data
        if (0 < cString::getStringLength(trim($this->get('data_table')))) {
            $sql = "-- PifaForm->delete()
                DROP TABLE IF EXISTS
                    `" . cSecurity::toString($this->get('data_table')) . "`
                ;";
            if (false === $db->query($sql)) {
                $msg = Pifa::i18n('TABLE_DROP_ERROR');
                throw new PifaException($msg);
            }
        }
    }

    /**
     * Delete this form all selected data.
     *
     * @param array $iddatas
     *
     * @return bool
     * @throws PifaException
     */
    public function deleteData(array $iddatas) {
        $db = cRegistry::getDb();

        if (!$this->isLoaded()) {
            $msg = Pifa::i18n('FORM_LOAD_ERROR');
            throw new PifaException($msg);
        }

        // delete datas
        $sql = "-- PifaForm->deleteData()
            DELETE FROM
                `" . cSecurity::toString($this->get('data_table')). "`
            WHERE
                id in (" . implode(',', $iddatas) . ")
            ;";

        try {
            $succ = $db->query($sql);
        } catch (cDbException $e) {
            $succ = false;
        }

        if (false === $succ) {
            $msg = Pifa::i18n('DATAS_DELETE_ERROR');
            throw new PifaException($msg);
        } else {
            return true;
        }
    }

    /**
     * @deprecated use $this->get('data_table') instead
     */
    public function getTableName() {
        return $this->get('data_table');
    }

}