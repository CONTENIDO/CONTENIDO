<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 *
 * @author marcus.gnass
 */
class PifaFormCollection extends ItemCollection {

    /**
     *
     * @param mixed $where clause to be used to load items or false
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
     * @throws PifaException if forms could not be read
     * @return PifaFormCollection
     */
    private static function _getBy($client, $lang) {

        // conditions to be used for reading items
        $conditions = array();

        // consider $client
        $client = cSecurity::toInteger($client);
        if (0 < $client) {
            $conditions[] = 'idclient=' . $client;
        }

        // consider $lang
        $lang = cSecurity::toInteger($lang);
        if (0 < $lang) {
            $conditions[] = 'idlang=' . $lang;
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
     * @throws PifaException if $client is not greater 0
     * @throws PifaException if forms could not be read
     * @return PifaFormCollection
     */
    public static function getByClient($client) {
        if (0 >= cSecurity::toInteger($client)) {
            throw new PifaException('$client is not greater 0');
        }

        return self::_getBy($client, 0);
    }

    /**
     * Get forms of any client in given language.
     *
     * @param int $lang
     * @throws PifaException if $lang is not greater 0
     * @throws PifaException if forms could not be read
     * @return PifaFormCollection
     */
    public static function getByLang($lang) {
        if (0 >= cSecurity::toInteger($lang)) {
            throw new PifaException('$lang is not greater 0');
        }

        return self::_getBy(0, $lang);
    }

    /**
     * Get forms of given client in given language.
     *
     * @param int $client
     * @param int $lang
     * @throws PifaException if $client is not greater 0
     * @throws PifaException if $lang is not greater 0
     * @throws PifaException if forms could not be read
     * @return PifaFormCollection
     */
    public static function getByClientAndLang($client, $lang) {
        if (0 >= cSecurity::toInteger($client)) {
            throw new PifaException('$client is not greater 0');
        }

        if (0 >= cSecurity::toInteger($lang)) {
            throw new PifaException('$lang is not greater 0');
        }

        return self::_getBy($client, $lang);
    }
}

/**
 * contains meta data of PIFA forms
 *
 * @author marcus.gnass
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
    private $_errors = array();

    /**
     *
     * @var int lastInsertedId
     */
    private $_lastInsertedId = NULL;

    /**
     *
     * @param mixed $id ID of item to be loaded or false
     */
    public function __construct($id = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct(cRegistry::getDbTableName('pifa_form'), 'idform');
        $this->setFilters(array(), array());
        if (false !== $id) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     *
     * @return array
     */
    public function getErrors() {
        return $this->_errors;
    }

    /**
     *
     * @param array $_errors
     */
    public function setErrors($_errors) {
        $this->_errors = $_errors;
    }

    /**
     *
     * @return array:PifaField
     */
    public function getFields() {
        if (NULL === $this->_fields) {
            $col = new PifaFieldCollection();
            $col->setWhere('PifaFieldCollection.idform', $this->get('idform'));
            $col->setOrder('PifaFieldCollection.field_rank');
            $col->query();
            $this->_fields = array();
            while (false !== $pifaField = $col->next()) {
                $this->_fields[] = clone $pifaField;
            }
        }

        return $this->_fields;
    }

    /**
     *
     * @return $_lastInsertedId
     */
    public function getLastInsertedId() {
        return $this->_lastInsertedId;
    }

    /**
     *
     * @param int $_lastInsertedId
     */
    public function setLastInsertedId($_lastInsertedId) {
        $this->_lastInsertedId = $_lastInsertedId;
    }

    /**
     * Returns an array containing values of all fields of this form where the
     * fields column name is used as key.
     *
     * @return array
     */
    public function getValues() {
        $values = array();
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
     * according to the specifications defined for aech form field.
     *
     * @param array $data
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
            } else if (true === $clear) {
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
        $files = array();
        foreach ($this->getFields() as $pifaField) {
            // ommit fields that are not an INPUTFILE
            if (PifaField::INPUTFILE !== cSecurity::toInteger($pifaField->get('field_type'))) {
                continue;
            }
            $files[$pifaField->get('column_name')] = $pifaField->getFile();
        }

        return $files;
    }

    /**
     * Sets uploaded file(s) for appropriate form fields.
     */
    public function setFiles(array $files = NULL) {
        if (NULL === $files) {
            return;
        }

        foreach ($this->getFields() as $pifaField) {
            // ommit fields that are not an INPUTFILE
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
     */
    public function getLastError() {
        return $this->lasterror;
    }

    /**
     */
    public function fromForm() {

        // get data from source depending on method
        switch (strtoupper($this->get('method'))) {
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
     * @param array $opt to determine form attributes
     * @return string
     */
    public function toHtml(array $opt = NULL) {

        // get form attribute values
        $opt = array_merge(array(
            // or whatever
            'name' => 'pifa-form',
            'action' => 'main.php',
            'method' => $this->get('method'),
            'class' => 'pifa-form jqtransform'
        ), $opt);
        $idform = $this->get('idform');

        // build form
        $htmlForm = new cHTMLForm($opt['name'], $opt['action'], $opt['method'], $opt['class']);
        // set ID (workaround: remove ID first!)
        $htmlForm->removeAttribute('id')->setID('pifa-form-' . $idform);

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

        return $htmlForm->render();
    }

    /**
     * Loops all fields and checks their value for being obligatory
     * and conforming to the fields rule.
     *
     * @throws PifaValidationException if at least one field was invalid
     */
    public function validate() {

        // validate all fields
        $errors = array();
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
     * @todo check if this could be usefull for PifaField too.
     *
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
            throw new PifaDatabaseException('could not connect to database');
        }
        if (0 === strlen(trim($sql))) {
            throw new PifaDatabaseException('could not build SQL');
        }

        // insert new row
        $success = $db->query($sql);

        if (false === $success) {
            throw new PifaDatabaseException('values could not be stored');
        }

        // get last insert id
        $lastInsertedId = $db->getLastInsertedId($this->get('data_table'));

        $this->setLastInsertedId($lastInsertedId);

        // store files
        $files = $this->getFiles();
        foreach ($this->getFiles() as $column => $file) {
            if (!is_array($file)) {
                continue;
            }
            // if no file was submitted tmp_name is an empty string
            if (0 === strlen($file['tmp_name'])) {
                continue;
            }
            $tmpName = $file['tmp_name'];
            $destPath = $cfg['path']['contenido_cache'] . 'form_assistant/';
            $destName = $this->get('data_table') . '_' . $lastInsertedId . '_' . $column;
            $destName = preg_replace('/[^a-z0-9_]+/i', '_', $destName);
            $success = move_uploaded_file($tmpName, $destPath . $destName);
            if (false === $success) {
                throw new PifaException('file could not be stored');
            }
        }
    }

    /**
     *
     * @param array $opt
     */
    public function toMailRecipient(array $opt) {
        if (0 == strlen(trim($opt['from']))) {
            throw new PifaMailException('missing sender address');
        }
        if (0 == strlen(trim($opt['fromName']))) {
            throw new PifaMailException('missing sender name');
        }
        if (0 == strlen(trim($opt['to']))) {
            throw new PifaMailException('missing recipient address');
        }
        if (0 == strlen(trim($opt['subject']))) {
            throw new PifaMailException('missing subject');
        }
        if (0 == strlen(trim($opt['body']))) {
            throw new PifaMailException('missing mail body');
        }

        // cMailer

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
                    // TODO mime type should be configurale
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

        // send mail
        if (!$mailer->send($message)) {
            throw new PifaMailException('could not send mail');
        }
    }

    /**
     *
     * @throws PifaException if form is not loaded
     * @throws PifaException if table does not exist
     * @return array
     */
    public function getData() {
        if (!$this->isLoaded()) {
            throw new PifaException('form is not loaded');
        }

        $db = cRegistry::getDb();

        // get table name and check if it exists
        $tableName = $this->get('data_table');
        if (!$this->existsTable($tableName, false)) {
            throw new PifaException('table does not exist');
        }

        // build SQL
        $sql = "-- PifaForm->getData()
            SELECT
                *
            FROM
                `$tableName`
            ;";

        if (false === $db->query($sql)) {
            return array();
        }

        if (0 === $db->num_rows()) {
            return array();
        }

        $data = array();
        while ($db->nextRecord()) {
            $data[] = $db->toArray();
        }

        return $data;
    }

    /**
     * Echoes a CSV file containing all of this forms stored data.
     * Thatfor proper headers are sent, that add the created file as attachment
     * for easier download.
     *
     * @param string $optionally
     * @throws PifaException if form is not loaded
     * @throws PifaException if table does not exist
     */
    public function getDataAsCsv($optionally = 'OPTIONALLY') {
        $cfg = cRegistry::getConfig();

        if (in_array($cfg['db']['connection']['host'], array(
            '127.0.0.1',
            'localhost'
        ))) {
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
     *
     * @param string $optionally
     * @throws PifaException if form is not loaded
     * @throws PifaException if table does not exist
     */
    private function _getCsvFromLocalDatabaseServer($optionally = 'OPTIONALLY') {

        // assert form is loaded
        if (!$this->isLoaded()) {
            throw new PifaException('form is not loaded');
        }

        // get table name and check if it exists
        $tableName = $this->get('data_table');
        if (!$this->existsTable($tableName, false)) {
            throw new PifaException('table does not exist');
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
     * TODO use fputcsv()
     *
     * @throws PifaException if form is not loaded
     * @throws PifaException if table does not exist
     */
    private function _getCsvFromRemoteDatabaseServer() {

        // get column names in correct order
        $columns = array();
        // always append the records ID
        array_push($columns, 'id');
        // append the records timestamp if defined for form
        if (true === (bool) $this->get('with_timestamp')) {
            array_push($columns, 'pifa_timestamp');
        }
        foreach ($this->getFields() as $index => $pifaField) {
            $columns[] = $pifaField->get('column_name');
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
     * th the current values of this form.
     *
     * @param bool $oneRowPerField
     */
    public function getCsv($oneRowPerField = false, array $additionalFields = NULL) {

        // get values to be converted into CSV
        $data = $this->getValues();

        // add additional fields if given
        if (NULL !== $additionalFields) {
            array_merge($data, $additionalFields);
        }

        $out = '';
        if (true === $oneRowPerField) {

            // one line for each field containing its header and value
            foreach ($data as $key => $value) {
                if (0 < strlen($out)) {
                    $out .= "\n";
                }
                $value = str_replace("\n", '\n', $value);
                $value = str_replace("\r", '\r', $value);
                $value = "\"$value\"";
                $out .= "$key;$value";
            }
        } else {

            // one line for headers and another for values
            $header = $values = '';
            foreach ($data as $key => $value) {
                if (0 < strlen($header)) {
                    $header .= ';';
                    $values .= ';';
                }
                $header .= $key;
                $value = str_replace("\n", '\n', $value);
                $value = str_replace("\r", '\r', $value);
                $value = "\"$value\"";
                $values .= $value;
            }
            $out = "$header\n$values";
        }

        return $out;
    }

    /**
     *
     * @throws PifaException if existance of table could not be determined
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
            throw new PifaException('existance of table could not be determined ' + $db->getErrorMessage());
        }

        return (bool) (0 !== $db->num_rows());
    }

    /**
     *
     * @throws PifaException if form is not loaded
     * @throws PifaException if existance of table could not be determined
     * @throws PifaException if table already exists
     * @throws PifaException if table could not be created
     */
    public function createTable($withTimestamp) {
        if (!$this->isLoaded()) {
            throw new PifaException('form is not loaded');
        }

        // get & check table name
        $tableName = $this->get('data_table');
        if ($this->existsTable($tableName)) {
            throw new PifaException('table ' . $tableName . ' already exists');
        }

        // prepare column definitions
        $createDefinitions = array();
        $createDefinitions[] = "id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'primary key'";
        if ($withTimestamp) {
            $createDefinitions[] = "pifa_timestamp TIMESTAMP NOT NULL COMMENT 'automatic PIFA timestamp'";
        }
        $createDefinitions = join(',', $createDefinitions);

        // prepare statement
        $sql = "-- PifaForm->createTable()
            CREATE TABLE IF NOT EXISTS
                `$tableName`
            ($createDefinitions)
            ENGINE=MyISAM
            DEFAULT CHARSET=utf8
            ;";

        // create table
        $db = cRegistry::getDb();
        if (false === $db->query($sql)) {
            throw new PifaException('table could not be created');
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
     * @param bool $oldWithTimestamp
     * @throws PifaException if form is not loaded
     */
    public function alterTable($oldTableName, $oldWithTimestamp) {
        if (!$this->isLoaded()) {
            throw new PifaException('form is not loaded');
        }

        // get & check table name
        $tableName = $this->get('data_table');

        // rename data table if name has changed
        if ($oldTableName !== $tableName) {
            if ($this->existsTable($tableName)) {
                throw new PifaException('table ' . $tableName . ' already exists');
            }

            $sql = "-- PifaForm->alterTable()
                RENAME TABLE
                    `$oldTableName`
                TO
                    `$tableName`
                ;";
            cRegistry::getDb()->query($sql);
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
     *
     * @param PifaField $pifaField
     * @param string $oldColumnName
     * @throws PifaException if form is not loaded
     * @throws PifaException if field is not loaded
     */
    public function storeColumn(PifaField $pifaField, $oldColumnName) {
        if (!$this->isLoaded()) {
            throw new PifaException('form is not loaded');
        }
        if (!$pifaField->isLoaded()) {
            throw new PifaException('field is not loaded');
        }

        $columnName = $pifaField->get('column_name');
        $dataType = $pifaField->getDbDataType();

        if (0 === strlen(trim($oldColumnName))) {
            if (0 === strlen(trim($columnName))) {
                // PASS
            } else {
                $this->addColumn($columnName, $dataType);
            }
        } else {
            if (0 === strlen(trim($columnName))) {
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
     * @throws PifaException if form is not loaded
     * @throws PifaException if field is not loaded
     */
    public function changeColumn($columnName, $dataType, $oldColumnName) {
        $tableName = $this->get('data_table');
        if ($oldColumnName === $columnName) {
            return;
        }
        if (true === $this->_existsColumn($columnName)) {
            throw new PifaException("column $columnName already exists");
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
            throw new PifaException('column could not be changed');
        }
    }

    /**
     * Adds a column for the current field to the table of the current form.
     *
     * @param string $columnName
     * @throws PifaException if field is not loaded
     */
    public function dropColumn($columnName) {
        $tableName = $this->get('data_table');
        if (false === $this->_existsColumn($columnName)) {
            throw new PifaException("column $columnName already exists");
        }

        $sql = "-- PifaForm->dropColumn()
            ALTER TABLE
                `$tableName`
            DROP
                `$columnName`
            ;";

        $db = cRegistry::getDb();
        if (false === $db->query($sql)) {
            throw new PifaException('column could not be dropped');
        }
    }

    /**
     * Adds a column for the current field to the table of the current form.
     *
     * @param string $columnName
     * @param string $dataType
     * @throws PifaException if field is not loaded
     */
    public function addColumn($columnName, $dataType) {
        $tableName = $this->get('data_table');
        if (true === $this->_existsColumn($columnName)) {
            throw new PifaException("column $columnName already exists");
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
            throw new PifaException('column could not be added');
        }
    }

    /**
     *
     * @param string $columnName
     * @throws PifaException if columns could not be read
     * @return boolean
     */
    protected function _existsColumn($columnName) {
        $tableName = $this->get('data_table');
        $sql = "-- PifaForm->_existsColumn()
            SHOW FIELDS FROM
                `$tableName`
            ;";

        $db = cRegistry::getDb();
        if (false === $db->query($sql)) {
            throw new PifaException('columns could not be read');
        }

        // Field, Type, Null, Key, Default, Extra
        while (false !== $db->nextRecord()) {
            $field = $db->toArray();
            if (strtolower($field['Field']) == strtolower($columnName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deletes this form with all its fields and stored data.
     * The forms data table is also dropped.
     */
    public function delete() {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();

        if (!$this->isLoaded()) {
            throw new PifaException('form is not loaded');
        }

        // delete form
        $sql = "-- PifaForm->delete()
            DELETE FROM
                `" . cRegistry::getDbTableName('pifa_form') . "`
            WHERE
                idform = " . cSecurity::toInteger($this->get('idform')) . "
            ;";
        if (false === $db->query($sql)) {
            throw new PifaException('form could not be deleted');
        }

        // delete fields
        $sql = "-- PifaForm->delete()
            DELETE FROM
                `" . cRegistry::getDbTableName('pifa_field') . "`
            WHERE
                idform = " . cSecurity::toInteger($this->get('idform')) . "
            ;";
        if (false === $db->query($sql)) {
            throw new PifaException('fields could not be deleted');
        }

        // drop data
        if (0 < strlen(trim($this->get('data_table')))) {
            $sql = "-- PifaForm->delete()
                DROP TABLE IF EXISTS
                    `" . cSecurity::toString($this->get('data_table')) . "`
                ;";
            if (false === $db->query($sql)) {
                throw new PifaException('data table could not be dropped');
            }
        }
    }

    /**
     *
     * @deprecated use $this->get('data_table') instead
     */
    public function getTableName() {
        return $this->get('data_table');
    }
}
