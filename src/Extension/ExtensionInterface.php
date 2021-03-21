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

namespace Berlioz\HtmlSelector\Extension;

use Berlioz\HtmlSelector\PseudoClass\PseudoClassInterface;

/**
 * Interface ExtensionInterface.
 */
interface ExtensionInterface
{
    /**
     * Get pseudo classes.
     *
     * @return PseudoClassInterface[]
     */
    public function getPseudoClasses(): array;
}