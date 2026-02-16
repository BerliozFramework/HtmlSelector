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

use Berlioz\HtmlSelector\CssSelector\CssSelectorParser;
use Berlioz\HtmlSelector\Extension\CssExtension;
use Berlioz\HtmlSelector\Extension\ExtensionInterface;
use Berlioz\HtmlSelector\Extension\QueryExtension;
use Berlioz\HtmlSelector\PseudoClass\PseudoClassSet;
use Berlioz\HtmlSelector\Query\Query;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;

/**
 * Class HtmlSelector.
 */
class HtmlSelector
{
    protected PseudoClassSet $pseudoClasses;
    protected XpathSolver $xpathSolver;
    protected CssSelectorParser $selectorParser;

    public function __construct()
    {
        $this->pseudoClasses = new PseudoClassSet();
        $this->xpathSolver = new XpathSolver($this->pseudoClasses);
        $this->selectorParser = new CssSelectorParser();

        $this->addExtension(
            new CssExtension($this),
            new QueryExtension($this)
        );
    }

    /**
     * Add extension.
     *
     * @param ExtensionInterface ...$extension
     */
    public function addExtension(ExtensionInterface ...$extension): void
    {
        array_walk($extension, fn($extension) => $this->pseudoClasses->add(...$extension->getPseudoClasses()));
    }

    /**
     * Get pseudo classes.
     *
     * @return PseudoClassSet
     */
    public function getPseudoClasses(): PseudoClassSet
    {
        return $this->pseudoClasses;
    }

    /**
     * Solve xpath.
     *
     * @param string $selector
     * @param string|null $context
     *
     * @return string
     * @throws Exception\SelectorException
     */
    public function solveXpath(string $selector, ?string $context = XpathSolver::CONTEXT_ALL): string
    {
        return $this->xpathSolver->solve($selector, $context);
    }

    /**
     * Get selector parser.
     *
     * @return CssSelectorParser
     */
    public function getSelectorParser(): CssSelectorParser
    {
        return $this->selectorParser;
    }

    /**
     * Query from response.
     *
     * @param ResponseInterface $response
     * @param string|null $encoding
     *
     * @return Query
     * @throws Exception\LoaderException
     */
    public function queryFromResponse(ResponseInterface $response, ?string $encoding = null): Query
    {
        if (null === $encoding) {
            if ($contentType = $response->getHeader('Content-Type')) {
                $contentType = implode(' ; ', $contentType);
                $matches = [];

                if (1 === preg_match('/charset\s*=\s*(?<charset>[\w-]+)/i', $contentType, $matches)) {
                    $encoding = $matches['charset'];
                }
            }
        }

        $contents = $response->getBody()->getContents();

        return $this->query($contents, encoding: $encoding);
    }

    /**
     * Create query.
     *
     * @param SimpleXMLElement|string $contents
     * @param bool $contentsIsFile
     * @param string|null $encoding
     *
     * @return Query
     * @throws Exception\LoaderException
     */
    public function query(
        SimpleXMLElement|string $contents,
        bool $contentsIsFile = false,
        ?string $encoding = null
    ): Query {
        if (is_string($contents)) {
            $contents = new HtmlLoader($contents, $contentsIsFile, $encoding);
        }

        return new Query([$contents->getXml()], null, $this);
    }
}