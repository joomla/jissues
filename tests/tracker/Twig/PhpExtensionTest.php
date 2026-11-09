<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\Twig;

use JTracker\Twig\PhpExtension;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Tests for the PhpExtension class.
 *
 * @since  1.0
 */
class PhpExtensionTest extends TestCase
{
    /**
     * Renders a template with the extension loaded.
     *
     * @param   string  $template  The template source
     * @param   array   $context   The template context
     *
     * @return  string
     */
    private function render(string $template, array $context = []): string
    {
        $twig = new Environment(new ArrayLoader(['test' => $template]));
        $twig->addExtension(new PhpExtension());

        return $twig->render('test', $context);
    }

    /**
     * @return  void
     */
    #[TestDox('The extension registers the PHP functions as filters')]
    public function testGetFilters(): void
    {
        $filters = [];

        foreach ((new PhpExtension())->getFilters() as $filter) {
            $filters[$filter->getName()] = $filter->getCallable();
        }

        $this->assertSame(
            [
                'basename'    => 'basename',
                'get_class'   => 'get_class',
                'json_decode' => 'json_decode',
            ],
            $filters
        );
    }

    /**
     * @return  void
     */
    #[TestDox('The extension registers sprintf as function')]
    public function testGetFunctions(): void
    {
        $functions = (new PhpExtension())->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('sprintf', $functions[0]->getName());
        $this->assertSame('sprintf', $functions[0]->getCallable());
    }

    /**
     * @return  void
     */
    #[TestDox('The basename filter returns the trailing name of a path')]
    public function testBasenameFilter(): void
    {
        $this->assertSame('Inline.php', $this->render('{{ path|basename }}', ['path' => '/src/JTracker/Inline.php']));
    }

    /**
     * @return  void
     */
    #[TestDox('The get_class filter returns the class name of an object')]
    public function testGetClassFilter(): void
    {
        $this->assertSame(
            'RuntimeException',
            $this->render('{{ exception|get_class }}', ['exception' => new \RuntimeException()])
        );
    }

    /**
     * @return  void
     */
    #[TestDox('The json_decode filter decodes JSON into objects')]
    public function testJsonDecodeFilter(): void
    {
        $this->assertSame(
            'def456/2',
            $this->render(
                '{% set commits = json|json_decode %}{{ (commits|last).sha }}/{{ commits|length }}',
                ['json' => '[{"sha":"abc123"},{"sha":"def456"}]']
            )
        );
    }

    /**
     * @return  void
     */
    #[TestDox('The sprintf function formats a string and its output is escaped')]
    public function testSprintfFunction(): void
    {
        $this->assertSame('Page 2 of 5', $this->render('{{ sprintf("Page %d of %d", 2, 5) }}'));
        $this->assertSame('&lt;b&gt;x&lt;/b&gt;', $this->render('{{ sprintf("<b>%s</b>", "x") }}'));
    }
}
