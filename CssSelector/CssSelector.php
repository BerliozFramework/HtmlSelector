<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2021 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\HtmlSelector\CssSelector;

/**
 * Class CssSelector.
 */
class CssSelector
{
    protected ?NextCssSelector $next = null;

    public function __construct(
        protected string $selector,
        protected ?string $type = null,
        protected ?string $id = null,
        protected array $classes = [],
        protected array $attributes = [],
        protected array $pseudoClasses = [],
    ) {
    }

    /**
     * __toString() PHP magic method.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->selector;
    }

    /**
     * Get type.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Get id.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Get classes.
     *
     * @return array
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * Get attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get pseudo classes.
     *
     * @return array
     */
    public function getPseudoClasses(): array
    {
        return $this->pseudoClasses;
    }

    /**
     * Get next.
     *
     * @return NextCssSelector|null
     */
    public function getNext(): ?NextCssSelector
    {
        return $this->next;
    }

    /**
     * Set next.
     *
     * @param NextCssSelector|null $next
     */
    public function setNext(?NextCssSelector $next): void
    {
        $this->next = $next;
    }
}