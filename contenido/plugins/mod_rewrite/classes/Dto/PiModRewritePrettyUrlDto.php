<?php

declare(strict_types=1);

/**
 * AMR pretty URL DTO class.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 * @since      Advanced Mod Rewrite 2.1.0
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Pretty URL DTO.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 */
class PiModRewritePrettyUrlDto
{
    /**
     * @var string The category path in the pretty URL.
     */
    private $urlPath;

    /**
     * @var string The article name in the pretty URL.
     */
    private $urlName;

    /**
     * @param string $urlPath The category path in the pretty URL.
     * @param string $urlName The article name in the pretty URL.
     */
    public function __construct(string $urlPath, string $urlName)
    {
        $this->urlPath = $urlPath;
        $this->urlName = $urlName;
    }

    public function getUrlPath(): string
    {
        return $this->urlPath;
    }

    public function getUrlName(): string
    {
        return $this->urlName;
    }

    /**
     * @return array{urlPath: string, urlName: string}
     */
    public function toArray(): array
    {
        return [
            'urlPath' => $this->urlPath,
            'urlName' => $this->urlName,
        ];
    }
}
