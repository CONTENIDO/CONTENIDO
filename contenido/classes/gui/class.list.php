<?php

/**
 * This file contains the list GUI class.
 *
 * @package    Core
 * @subpackage GUI
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * List GUI class
 *
 * @package    Core
 * @subpackage GUI
 */
class cGuiList
{

    /**
     * @var array
     */
    protected $cells;

    /**
     * @var string
     * @since CONTENIDO 4.10.2
     */
    protected $class = '';

    /**
     * @var string[]
     * @since CONTENIDO 4.10.2
     */
    protected $columnClasses = [];

    /**
     * Constructor to create an instance of this class.
     *
     * @param string $class The CSS class name for the list to set.
     *      Since CONTENIDO 4.10.2
     */
    public function __construct(string $class = '')
    {
        $this->cells = [];
        $this->setClass($class);
    }

    /**
     * @param string $class The CSS class name for the list to set.
     * @since CONTENIDO 4.10.2
     */
    public function setClass(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * @param int $column The column position to set the CSS class for.
     * @param string $class The CSS class name for the column to set.
     * @since CONTENIDO 4.10.2
     */
    public function setColumnClass(int $column, string $class): self
    {
        $this->columnClasses[$column] = $class;

        return $this;
    }

    /**
     * @param string|int $item
     * @param string|int $cell
     * @param string $value
     */
    public function setCell($item, $cell, $value): self
    {
        $this->cells[$item][$cell] = $value;

        return $this;
    }

    /**
     * @return ?string Complete template string or null
     * @throws cInvalidArgumentException
     */
    public function render(bool $print = false)
    {
        $cfg = cRegistry::getConfig();
        $templatesPath = cRegistry::getBackendPath() . $cfg['path']['templates'];

        $tpl = new cTemplate();
        $tpl->set('s', 'CLASS', $this->class);

        $tpl2 = new cTemplate();

        $colcount = 0;

        if (is_array($this->cells)) {
            foreach ($this->cells as $row => $cells) {
                $colcount++;
                $content = '';
                foreach ($cells as $key => $value) {
                    $tpl2->reset();

                    $tpl2->set('s', 'CONTENT', $value);
                    $tpl2->set('s', 'CLASS', $this->columnClasses[$key] ?? '');
                    if ($colcount == 1) {
                        $content .= $tpl2->generate(
                            $templatesPath . $cfg['templates']['generic_list_head'],
                            true
                        );
                    } else {
                        $content .= $tpl2->generate(
                            $templatesPath . $cfg['templates']['generic_list_row'],
                            true
                        );
                    }
                }

                $tpl->set('d', 'ROWS', $content);
                $tpl->next();
            }
        }

        $rendered = $tpl->generate($templatesPath . $cfg['templates']['generic_list'], true);

        if ($print) {
            echo $rendered;
            return null;
        } else {
            return $rendered;
        }
    }

}
