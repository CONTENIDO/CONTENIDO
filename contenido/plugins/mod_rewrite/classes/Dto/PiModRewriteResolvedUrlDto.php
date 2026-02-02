<?php

declare(strict_types=1);

/**
 * AMR resolved URL DTO class.
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
 * Resolved URL DTO.
 * Contains data about resolved details like client id, language id, article id, category id, etc.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 */
class PiModRewriteResolvedUrlDto
{
    /**
     * @var int|null
     */
    private $error;

    /**
     * @var int|null
     */
    private $clientId;

    /**
     * @var int|null
     */
    private $changeClientId;

    /**
     * @var int|null
     */
    private $languageId;

    /**
     * @var int|null
     */
    private $changeLanguageId;

    /**
     * @var int|null
     */
    private $articleId;

    /**
     * @var int|null
     */
    private $categoryId;

    /**
     * @var string|null
     */
    private $path;

    public function __construct(
        ?int $error = null,
        ?int $clientId = null,
        ?int $changeClientId = null,
        ?int $languageId = null,
        ?int $changeLanguageId = null,
        ?int $articleId = null,
        ?int $categoryId = null,
        ?string $path = null
    )
    {
        $this->error = $error;
        $this->clientId = $clientId;
        $this->changeClientId = $changeClientId;
        $this->languageId = $languageId;
        $this->changeLanguageId = $changeLanguageId;
        $this->articleId = $articleId;
        $this->categoryId = $categoryId;
        $this->path = $path;
    }

    public function getError(): ?int
    {
        return $this->error;
    }

    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    public function getChangeClientId(): ?int
    {
        return $this->changeClientId;
    }

    public function getLanguageId(): ?int
    {
        return $this->languageId;
    }

    public function getChangeLanguageId(): ?int
    {
        return $this->changeLanguageId;
    }

    public function getArticleId(): ?int
    {
        return $this->articleId;
    }

    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function toArray(bool $filterUnset = true): array
    {
        $array = [
            'error' => $this->error,
            'clientId' => $this->clientId,
            'changeClientId' => $this->changeClientId,
            'languageId' => $this->languageId,
            'changeLanguageId' => $this->changeLanguageId,
            'articleId' => $this->articleId,
            'categoryId' => $this->categoryId,
            'path' => $this->path,
        ];

        return $filterUnset ? array_filter($array) : $array;
    }
}
