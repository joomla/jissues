<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Tests\App\Tracker\Twig;

use App\Tracker\Twig\RelationExtension;
use JTracker\Tests\Mocks\DatabaseMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

/**
 * Tests for the RelationExtension class.
 *
 * @since  1.0
 */
class RelationExtensionTest extends TestCase
{
    use DatabaseMockTrait;

    /**
     * Relation type rows as returned by the database
     *
     * @var  array
     */
    private const RELATION_TYPES = [
        ['value' => 1, 'text' => 'duplicate_of'],
        ['value' => 2, 'text' => 'related_to'],
        ['value' => 3, 'text' => 'not_before'],
        ['value' => 4, 'text' => 'pr_for'],
        ['value' => 5, 'text' => 'unknown_relation'],
    ];

    /**
     * Creates the extension with a mocked database.
     *
     * @return  RelationExtension
     */
    private function createExtension(): RelationExtension
    {
        return new RelationExtension($this->createDatabaseMock(self::RELATION_TYPES));
    }

    /**
     * @return  void
     */
    #[TestDox('The extension registers the relation functions')]
    public function testGetFunctions(): void
    {
        $extension = $this->createExtension();
        $functions = [];

        foreach ($extension->getFunctions() as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);

            $functions[$function->getName()] = $function->getCallable();
        }

        $this->assertSame(
            [
                'issue_relation'       => [$extension, 'getIssueRelation'],
                'issue_relation_type'  => [$extension, 'getIssueRelationType'],
                'issue_relation_types' => [$extension, 'getIssueRelationTypes'],
            ],
            $functions
        );
    }

    /**
     * Data provider for testGetIssueRelation
     *
     * @return  \Generator
     */
    public static function relationProvider(): \Generator
    {
        yield 'duplicate' => ['duplicate_of', 'Duplicate of'];
        yield 'related' => ['related_to', 'Related to'];
        yield 'not before' => ['not_before', 'Not before'];
        yield 'pull request' => ['pr_for', 'Pull Request for'];
        yield 'unknown' => ['something_else', ''];
    }

    /**
     * @param   string  $relation  The relation name
     * @param   string  $label     The expected label
     *
     * @return  void
     */
    #[TestDox('getIssueRelation() returns the label of a relation')]
    #[DataProvider('relationProvider')]
    public function testGetIssueRelation(string $relation, string $label): void
    {
        $this->assertSame($label, $this->createExtension()->getIssueRelation($relation));
    }

    /**
     * @return  void
     */
    #[TestDox('getIssueRelationTypes() loads the relation types from the database only once')]
    public function testGetIssueRelationTypes(): void
    {
        $extension = $this->createExtension();

        $types = $extension->getIssueRelationTypes();

        $this->assertEquals(
            array_map(static function (array $row) {
                return (object) $row;
            }, self::RELATION_TYPES),
            $types
        );
        $this->assertSame($types, $extension->getIssueRelationTypes());

        $this->assertCount(1, $this->executedQueries);
        $this->assertStringContainsString('`id` AS `value`', $this->executedQueries[0]);
        $this->assertStringContainsString('`name` AS `text`', $this->executedQueries[0]);
        $this->assertStringContainsString('FROM `#__issues_relations_types`', $this->executedQueries[0]);
    }

    /**
     * @return  void
     */
    #[TestDox('getIssueRelationType() returns the label of a relation type ID')]
    public function testGetIssueRelationType(): void
    {
        $extension = $this->createExtension();

        $this->assertSame('Duplicate of', $extension->getIssueRelationType(1));
        $this->assertSame('Pull Request for', $extension->getIssueRelationType('4'));
        $this->assertSame('', $extension->getIssueRelationType(5));
        $this->assertSame('', $extension->getIssueRelationType(99));

        $this->assertCount(1, $this->executedQueries);
    }

    /**
     * @return  void
     */
    #[TestDox('The functions can be used in templates')]
    public function testFunctionsInTemplate(): void
    {
        $twig = new Environment(new ArrayLoader([
            'test' => '{{ issue_relation("related_to") }}/{{ issue_relation_type(3) }}/{{ issue_relation_types()|length }}',
        ]));
        $twig->addExtension($this->createExtension());

        $this->assertSame('Related to/Not before/5', $twig->render('test'));
    }
}
