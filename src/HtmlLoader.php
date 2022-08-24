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

namespace Berlioz\HtmlSelector;

use Berlioz\HtmlSelector\Exception\LoaderException;
use DOMDocument;
use DOMException;
use SimpleXMLElement;

/**
 * Class HtmlLoader.
 */
class HtmlLoader
{
    private SimpleXMLElement $xml;

    /**
     * HtmlLoader constructor.
     *
     * @param SimpleXMLElement|string $contents
     * @param bool $contentsIsFile
     * @param string|null $encoding
     *
     * @throws LoaderException
     */
    public function __construct(
        SimpleXMLElement|string $contents,
        bool $contentsIsFile = false,
        ?string $encoding = null
    ) {
        if (is_string($contents)) {
            if (true === $contentsIsFile) {
                $contents = $this->loadFile($contents);
            }

            $contents = $this->loadSimpleXml($contents, $encoding);
        }

        $this->xml = $contents;
    }

    /**
     * Get XML.
     *
     * @return SimpleXMLElement
     */
    public function getXml(): SimpleXMLElement
    {
        return $this->xml;
    }

    /**
     * Load file.
     *
     * @param string $filename
     *
     * @return string
     * @throws LoaderException
     */
    private function loadFile(string $filename): string
    {
        if (false === ($content = @file_get_contents($filename))) {
            throw new LoaderException(sprintf('Unable to load file "%s"', $filename));
        }

        return $content;
    }

    /**
     * Load SimpleXML.
     *
     * @param string $contents
     * @param string|null $encoding
     *
     * @return SimpleXMLElement
     * @throws LoaderException
     */
    private function loadSimpleXml(string $contents, ?string $encoding = null): SimpleXMLElement
    {
        // Encoding
        $encoding = $encoding ?? (mb_detect_encoding($contents) ?: 'ASCII');

        // Empty string
        if (empty($contents)) {
            return new SimpleXMLElement('<html></html>');
        }

        // Prepare html
        $contents = str_replace(['&nbsp;', chr(13)], [' ', ''], $contents);
        $contents = $this->stripInvalidXml($contents);

        try {
            // Convert HTML string to \DOMDocument
            libxml_use_internal_errors(true);
            $domHtml = new DOMDocument('1.0', $encoding);
            if (!$domHtml->loadHTML(mb_convert_encoding($contents, 'HTML-ENTITIES', $encoding), LIBXML_COMPACT)) {
                throw new LoaderException('Unable to parse HTML data.');
            }

            // Add 'document' root node
            $nodeDocument = $domHtml->createElement('document');
            $nodeDocument->setAttribute('dir', 'ltr');
            while (isset($domHtml->childNodes[0])) {
                $nodeDocument->appendChild($domHtml->childNodes[0]);
            }
            $domHtml->appendChild($nodeDocument);

            // Convert \DOMDocument to \SimpleXMLElement object
            return simplexml_import_dom($domHtml);
        } catch (DOMException $exception) {
            throw new LoaderException(previous: $exception);
        }
    }

    /**
     * Strip invalid xml.
     *
     * @param string $xml
     *
     * @return string
     */
    private function stripInvalidXml(string $xml): string
    {
        if (empty($xml)) {
            return '';
        }

        $result = '';
        $length = strlen($xml);
        for ($i = 0; $i < $length; $i++) {
            $current = ord($xml[$i]);

            if ((0x9 == $current) ||
                (0xA == $current) ||
                (0xD == $current) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF))
            ) {
                $result .= chr($current);
            } else {
                $result .= " ";
            }
        }

        return $result;
    }
}