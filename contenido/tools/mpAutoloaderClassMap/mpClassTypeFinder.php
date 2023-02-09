<?php
/**
 * Contains class type token finder.
 *
 * @category   Development
 * @package    mpAutoloaderClassMap
 * @author     Murat Purc <murat@purc.de>
 * @copyright  Copyright (c) 2009-2010 Murat Purc (https://www.purc.de)
 * @license    https://www.gnu.org/licenses/gpl-2.0.html - GNU General Public License, version 2
 */


/**
 * Class to find class type tokens
 *
 * @category  Development
 * @package   mpAutoloaderClassMap
 * @author    Murat Purc <murat@purc.de>
 */
class mpClassTypeFinder
{
    /**
     * List of directories to ignore (note: is case-insensitive)
     * @var  array
     */
    protected $_excludeDirs = ['.svn', '.cvs'];

    /**
     * List of files to ignore, regex pattern is also accepted (note: is case insensitive)
     * @var  array
     */
    protected $_excludeFiles = ['/^~*.\.php$/', '/^~*.\.inc$/'];

    /**
     * List of file extensions to parse (note: is case-insensitive)
     * @var  array
     */
    protected $_extensionsToParse = ['.php', '.inc'];

    /**
     * Flag to enable debugging, all messages will be collected in property _debugMessages,
     * if enabled
     * @var  bool
     */
    protected $_enableDebug = false;

    /**
     * List of debugging messages, will e filled, if debugging is active
     * @var  array
     */
    protected $_debugMessages = [];


    /**
     * Initializes class with passed options
     *
     * @param   array  $options  Associative options array as follows:
     *                           - excludeDirs: (array)  List of directories to exclude, optional.
     *                               Default values are '.svn' and '.cvs'.
     *                           - excludeFiles: (array)  List of files to exclude, optional.
     *                               Default values are '/^~*.\.php$/' and '/^~*.\.inc$/'.
     *                           - extensionsToParse: (array)  List of file extensions to parse, optional.
     *                               Default values are '.php' and '.inc'.
     *                           - enableDebug: (bool)  Flag to enable debugging, optional.
     *                               Default value is false.
     */
    public function __construct(array $options= [])
    {
        if (isset($options['excludeDirs']) && is_array($options['excludeDirs'])) {
            $this->setExcludeDirs($options['excludeDirs']);
        }
        if (isset($options['excludeFiles']) && is_array($options['excludeFiles'])) {
            $this->setExcludeFiles($options['excludeFiles']);
        }
        if (isset($options['extensionsToParse']) && is_array($options['extensionsToParse'])) {
            $this->setExtensionsToParse($options['extensionsToParse']);
        }
        if (isset($options['enableDebug']) && is_bool($options['enableDebug'])) {
            $this->_enableDebug = $options['enableDebug'];
        }
    }


    /**
     * Sets directories to exclude
     *
     * @param   array  $excludeDirs
     * @return  void
     */
    public function setExcludeDirs(array $excludeDirs)
    {
        $this->_excludeDirs = $excludeDirs;
    }


    /**
     * Returns list of directories to exclude
     *
     * @return  array
     */
    public function getExcludeDirs(): array
    {
        return $this->_excludeDirs;
    }


    /**
     * Sets files to exclude
     *
     * @param   array  $excludeFiles  Feasible values are
     *                                - temp.php (single file name)
     *                                - ~*.php (with * wildcard)
     *                                  Will be replaced against regex '/^~.*\.php$/'
     */
    public function setExcludeFiles(array $excludeFiles)
    {
        foreach ($excludeFiles as $pos => $entry) {
            if (strpos($entry, '*') !== false) {
                $entry = '/^' . str_replace('*', '.*', preg_quote($entry)) . '$/';
                $excludeFiles[$pos] = $entry;
            }
        }
        $this->_excludeFiles = $excludeFiles;
    }


    /**
     * Returns list of files to exclude
     *
     * @return  array
     */
    public function getExcludeFiles(): array
    {
        return $this->_excludeFiles;
    }


    /**
     * Sets file extensions to parse
     *
     * @param   array  $extensionsToParse
     */
    public function setExtensionsToParse(array $extensionsToParse)
    {
        $this->_extensionsToParse = $extensionsToParse;
    }


    /**
     * Returns list of file extension to parse
     *
     * @return  array
     */
    public function getExtensionsToParse(): array
    {
        return $this->_extensionsToParse;
    }


    /**
     * Detects all available class type tokens in found files inside passed directory.
     *
     * @param SplFileInfo  $fileInfo
     * @param bool $recursive Flag to parse directory recursive
     * @return array|NULL Either an associative array where the key is the class
     *                    type token and the value is the path or NULL.
     */
    public function findInDir(SplFileInfo $fileInfo, bool $recursive = true)
    {
        if (!$fileInfo->isDir() || !$fileInfo->isReadable()) {
            $this->_debug('findInDir: Invalid/Not readable directory ' . $fileInfo->getPathname());
            return NULL;
        }
        $this->_debug('findInDir: Processing dir ' . $fileInfo->getPathname() . ' (realpath: ' . $fileInfo->getRealPath() . ')');

        $classTypeTokens = [];

        $iterator = $this->_getDirIterator($fileInfo, $recursive);

        foreach ($iterator as $file) {
            if ($this->_isFileToProcess($file)) {
                if ($foundTokens = $this->findInFile($file)) {
                     $classTypeTokens = array_merge($classTypeTokens, $foundTokens);
                }
            }
        }

        return (count($classTypeTokens) > 0) ? $classTypeTokens : NULL;
    }


    /**
     * Detects all available class type tokens in passed file
     *
     * @param SplFileInfo $fileInfo
     * @return array|NULL Either an associative array where the key is the class
     *                    type token and the value is the path or NULL.
     */
    public function findInFile(SplFileInfo $fileInfo)
    {
        if (!$fileInfo->isFile() || !$fileInfo->isReadable()) {
            $this->_debug('findInFile: Invalid/Not readable file ' . $fileInfo->getPathname());
            return NULL;
        }
        $this->_debug('findInFile: Processing file ' . $fileInfo->getPathname() . ' (realpath: ' . $fileInfo->getRealPath() . ')');

        $classTypeTokens = [];

        $tokens  = token_get_all(file_get_contents($fileInfo->getRealPath()));
        $prevTokenFound = false;
        foreach ($tokens as $p => $token) {
            if ($token[0] == T_INTERFACE) {
                $this->_debug('findInFile: T_INTERFACE token found (token pos ' . $p . ')');
                $prevTokenFound = true;
            // } elseif ($token[0] == T_ABSTRACT) {
            //     $this->_debug('findInFile: T_ABSTRACT token found (token pos ' . $p . ')');
            //     $prevTokenFound = true;
            } elseif ($token[0] == T_CLASS) {
                $this->_debug('findInFile: T_CLASS token found (token pos ' . $p . ')');
                $prevTokenFound = true;
            } elseif ($token[0] == T_TRAIT) {
                $this->_debug('findInFile: T_TRAIT token found (token pos ' . $p . ')');
                $prevTokenFound = true;
            }
            if ($prevTokenFound && $token[0] !== T_STRING) {
                continue;
            } elseif ($prevTokenFound && $token[0] == T_STRING) {
                $classTypeTokens[$token[1]] = $this->_normalizePathSeparator($fileInfo->getRealPath());
                $prevTokenFound = false;
            }
        }

        return (count($classTypeTokens) > 0) ? $classTypeTokens : NULL;
    }


    /**
     * Returns list of debug messages
     *
     * @return  array
     */
    public function getDebugMessages(): array
    {
        return $this->_debugMessages;
    }


    /**
     * Returns debug messages in a formatted way.
     *
     * @param string $delimiter Delimiter between each message
     * @param string $wrap String with %s type specifier used to wrap all
     *                     messages
     * @return  string  Formatted string
     * @throws cInvalidArgumentException if the given wrap does not contain %s
     */
    public function getFormattedDebugMessages(string $delimiter="\n", string $wrap='%s'): string
    {
        if (strpos($wrap, '%s') === false) {
            throw new cInvalidArgumentException('Missing type specifier %s in parameter wrap!');
        }
        $messages = implode($delimiter, $this->_debugMessages);
        return sprintf($wrap, $messages);
    }


    /**
     * Adds passed message to debug list, if debugging is enabled
     *
     * @param   string  $msg
     */
    protected function _debug(string $msg)
    {
        if ($this->_enableDebug) {
            $this->_debugMessages[] = $msg;
        }
    }


    /**
     * Returns directory iterator depending on $recursive parameter value
     *
     * @param   SplFileInfo  $fileInfo
     * @param   bool         $recursive
     * @return  RecursiveIteratorIterator|DirectoryIterator
     */
    protected function _getDirIterator(SplFileInfo $fileInfo, bool $recursive)
    {
        if ($recursive === true) {
            return new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($fileInfo->getRealPath()),
                RecursiveIteratorIterator::SELF_FIRST
            );
        } else {
            return new DirectoryIterator($fileInfo->getRealPath());
        }
    }


    /**
     * Checks if file is to process
     *
     * @param   SplFileInfo  $file
     * @return  bool
     */
    protected function _isFileToProcess(SplFileInfo $file): bool
    {
        if ($this->_isDirToExclude($file)) {
            $this->_debug('_isFileToProcess: Dir to exclude ' . $file->getPathname() . ' (realpath: ' . $file->getRealPath() . ')');
            return false;
        }
        if ($this->_isFileToExclude($file)) {
            $this->_debug('_isFileToProcess: File to exclude ' . $file->getPathname() . ' (realpath: ' . $file->getRealPath() . ')');
            return false;
        }
        if ($this->_isFileToParse($file)) {
            $this->_debug('_isFileToProcess: File to parse ' . $file->getPathname() . ' (realpath: ' . $file->getRealPath() . ')');
            return true;
        }
        return false;
    }


    /**
     * Checks if directory is to exclude
     *
     * @param   SplFileInfo  $file
     * @return  bool
     */
    protected function _isDirToExclude(SplFileInfo $file): bool
    {
        $path = strtolower($this->_normalizePathSeparator($file->getRealPath()));

        foreach ($this->_excludeDirs as $item) {
            if (strpos($path, $item) !== false) {
                return true;
            }
        }
        return false;
    }


    /**
     * Checks if file is to exclude
     *
     * @param   SplFileInfo  $file
     * @return  bool
     */
    protected function _isFileToExclude(SplFileInfo $file): bool
    {
        $path = strtolower($this->_normalizePathSeparator($file->getRealPath()));

        foreach ($this->_excludeFiles as $item) {
            if (strlen($item) > 2 && substr($item, 0, 2) == '/^') {
                if (preg_match($item, $path)) {
                    return true;
                }
            } else if (strpos($path, $item) !== false) {
                return true;
            }
        }
        return false;
    }


    /**
     * Checks if file is to parse (if file extension matches)
     *
     * @param   SplFileInfo  $file
     * @return  bool
     */
    protected function _isFileToParse(SplFileInfo $file): bool
    {
        $path = strtolower($this->_normalizePathSeparator($file->getRealPath()));

        foreach ($this->_extensionsToParse as $item) {
            if (substr($path, -strlen($item)) == $item) {
                return true;
            }
        }
        return false;
    }


    /**
     * Replaces windows style directory separator (backslash against slash)
     *
     * @param   string  $path
     * @return  string
     */
    protected function _normalizePathSeparator(string $path): string
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }
        return $path;
    }

}
