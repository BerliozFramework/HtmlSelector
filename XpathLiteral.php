<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2026 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\HtmlSelector;

/**
 * Class XpathLiteral.
 *
 * @internal
 */
final class XpathLiteral
{
    /**
     * Quote a string as an XPath 1.0 expression, which has no backslash escapes.
     */
    public static function quote(string $value): string
    {
        if (!str_contains($value, '"')) {
            return '"' . $value . '"';
        }

        if (!str_contains($value, "'")) {
            return "'" . $value . "'";
        }

        return 'concat("' . implode('", \'"\', "', explode('"', $value)) . '")';
    }
}
