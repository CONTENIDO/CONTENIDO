<?php

/**
 * This file contains the SQL template parser class.
 *
 * @package    Core
 * @subpackage Database
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * SQL template parser class.
 *
 * Replaces predefined or custom placeholder against their values in SQL string
 * or files containing SQL.
 *
 *
 * @package    Core
 * @subpackage Database
 */
class cSqlTemplate
{

    // Placeholders
    const PREFIX_PLACEHOLDER = '!PREFIX!';
    const CHARSET_PLACEHOLDER = '!CHARSET!';
    const ENGINE_PLACEHOLDER = '!ENGINE!';
    const COLLATION_PLACEHOLDER = '!COLLATION!';

    /**
     * @var cDb database instance
     */
    private $_db;

    /**
     * @var array Assoziative replacements array to use in SQL templates.
     */
    private $_replacements = [];

    /**
     * Constructor.
     *
     * @param cDb|null $db Database instance.
     */
    public function __construct(cDb $db = null)
    {
        $this->_db = $db ?? cRegistry::getDb();
        $this->reset();
    }

    /**
     * Resets the replacements array to their initial values.
     *
     * @return void
     */
    public function reset()
    {
        // Get configuration from global scope to have its latest version
        $cfg = cRegistry::getConfig();
        // Define predefined replacements
        $this->setReplacements([
            self::CHARSET_PLACEHOLDER => $cfg['db']['connection']['charset'] ?? CON_DB_CHARSET,
            self::PREFIX_PLACEHOLDER => $cfg['sql']['sqlprefix'] ?? CON_DB_PREFIX,
            self::ENGINE_PLACEHOLDER => $cfg['db']['engine'] ?? CON_DB_ENGINE,
            self::COLLATION_PLACEHOLDER => $cfg['db']['collation'] ?? CON_DB_COLLATION,
        ]);
    }

    /**
     * Returns the database instance.
     *
     * @return cDb
     */
    public function getDb(): cDb
    {
        return $this->_db;
    }

    /**
     * Sets the replacements, overwrites existing replacements.
     *
     * @param array $replacements Assoziative replacements array.
     * @return void
     */
    public function setReplacements(array $replacements = [])
    {
        $this->_replacements = $replacements;
    }

    /**
     * Adds new replacements to the existing replacements.
     * Existing replacements with the same key are overwritten.
     *
     * @param array $replacements Assoziative replacements array.
     * @return void
     */
    public function addReplacements(array $replacements)
    {
        if (!empty($replacements)) {
            $this->_replacements = array_merge($this->_replacements, $replacements);
        }
    }

    /**
     * Returns the replacements.
     *
     * @return array Assoziative replacements array.
     */
    public function getReplacements(): array
    {
        return $this->_replacements;
    }

    /**
     * Returns a specific placeholder value.
     *
     * @param string $key The key (placeholder) of the placeholder value to get.
     * @return string|null The placeholder value as string or null.
     */
    public function getPlaceholderValue(string $key)
    {
        return $this->_replacements[$key] ?? null;
    }

    /**
     * Parses a SQL statement template.
     *
     * Invokes following CEC functions:
     * - Contenido.SqlTemplate.BeforeParse: Before parsing the SQL template.
     * - Contenido.SqlTemplate.AfterParse: After parsing the SQL template.
     *
     * @param string $text The SQL template text to parse.
     * @param array $replacements Additional replacements to use.
     * @return string The parsed SQL template text.
     */
    public function parse(string $text, array $replacements = []): string
    {
        $this->addReplacements($replacements);

        // CEC for template pre-processing
        $text = cApiCecHook::executeAndReturn('Contenido.SqlTemplate.BeforeParse', $text, $this);

        // Escape values
        $values = array_map(function ($value) {
            return is_string($value) ? $this->getDb()->escape($value) : $value;
        }, array_values($this->_replacements));

        $text = str_replace(array_keys($this->_replacements), $values, $text);

        // CEC for template post-processing
        $text = cApiCecHook::executeAndReturn('Contenido.SqlTemplate.AfterParse', $text, $this);

        $this->reset();

        return $text;
    }

    /**
     * Parses a SQL statement template file.
     *
     * @param string $file The SQL template file to parse.
     * @param array $replacements Additional replacements to use.
     * @return string The parsed SQL template file content.
     * @throws cInvalidArgumentException
     */
    public function parseFile(string $file, array $replacements = []): string
    {
        $content = cFileHandler::read($file);
        return self::parse($content, $replacements);
    }

}
