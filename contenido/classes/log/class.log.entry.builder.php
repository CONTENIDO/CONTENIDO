<?php
/**
 * This file contains the log entry builder class.
 *
 * @since      CONTENIDO 4.10.2
 * @package    Core
 * @author     Murat Purc <murat@purc.de>
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

class cLogEntryBuilder
{

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $trace;

    /**
     * @var int
     */
    private $startLevel;

    /**
     * @var bool
     */
    private $addSapiDetails;

    /**
     * @var bool
     */
    private $addStackTrace;

    /**
     * @param string $message The message to log
     * @param string $type Log type, e.g. 'Error', 'Warning', 'Deprecated', etc.
     */
    public function __construct(string $message, string $type = '')
    {
        $this->setMessage($message);
        $this->setType($type);
    }

    /**
     * Setter for the message to log.
     *
     * @param string $message The message to log
     * @return $this
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Setter for the log type.
     *
     * @param string $type Log type, e.g. 'Error', 'Warning', 'Deprecated', etc.
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Setter for trace.
     *
     * @param array $trace
     * @param int $startLevel
     * @return $this
     */
    public function setTrace(array $trace, int $startLevel): self
    {
        $this->trace = $trace;
        $this->startLevel = $startLevel;
        return $this;
    }

    private function getTrace(): array
    {
        if (!isset($trace)) {
            $this->setTrace((new Exception())->getTrace(), 1);
        }

        return $this->trace;
    }

    /**
     * Setter for whether add SAPI details to the log entry.
     *
     * @param bool $addSapiDetails
     * @return $this
     */
    public function setAddSapiDetails(bool $addSapiDetails): self
    {
        $this->addSapiDetails = $addSapiDetails;
        return $this;
    }

    /**
     * Setter for whether add trace details to the log entry.
     *
     * @param bool $addStackTrace
     * @return $this
     */
    public function setAddStackTrace(bool $addStackTrace): self
    {
        $this->addStackTrace = $addStackTrace;
        return $this;
    }

    /**
     * Build and return the log entry.
     *
     * @return string
     */
    public function build(): string
    {
        $trace = $this->getTrace();
        $data[] = "[" . date("Y-m-d H:i:s") . "] $this->type: \"$this->message\" at "
            .self::getCallerDetails($trace[$this->startLevel + 2] ?? []);

        if ($this->addSapiDetails) {
            $data = array_merge($data, self::buildSapiDetails());
        }

        if ($this->addStackTrace) {
            $data = array_merge($data, self::buildTraceDetails($trace, $this->startLevel + 2));
        }

        return implode("\n", $data) . "\n";
    }

    /**
     * Return the log entry.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->build();
    }

    /**
     * Returns caller details, e.g. `function()`, `class->function()` or `class::function()`.
     *
     * @param array $traceEntry The trace entry.
     * @return string
     */
    public static function getCallerDetails(array $traceEntry): string
    {
        $entry = $traceEntry['class'] ?? '';
        $entry .= $traceEntry['type'] ?? '';
        $entry .= isset($traceEntry['function']) ? $traceEntry['function'] . '()' : '';
        return $entry;
    }

    /**
     * Builds and returns SAPI details.
     *
     * @return string[]
     */
    public static function buildSapiDetails(): array
    {
        $data = ["\tSAPI: " . PHP_SAPI];
        if (isRunningFromWeb()) {
            $data[] = "\tURI: " . $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $data[] = "\tMethod: " . $_SERVER['REQUEST_METHOD'];
        } else {
            $_argv = $_SERVER['argv'];
            $data[] = "\tScript: " . array_shift($_argv);
            $data[] = "\tArguments: " . json_encode($_argv);
        }

        return $data;
    }

    /**
     * Builds and returns trace details.
     *
     * @param array $trace Trace array
     * @param int $startLevel
     * @return array
     */
    public static function buildTraceDetails(array $trace, int $startLevel = 2): array
    {
        $msg = [];

        $msg[] = "\tStack trace:";
        $pos = 0;
        for ($i = $startLevel; $i < count($trace); $i++) {
            $filename = basename($trace[$i]['file']);

            $caller = self::getCallerDetails($trace[$i]);
            $msg[] = "\t#" . $pos++ . ' ' . $caller . " called in file " . $filename . ":" . $trace[$i]['line'];
        }

        return $msg;
    }

}
