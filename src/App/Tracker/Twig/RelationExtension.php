<?php

/**
 * Part of the Joomla Tracker's Tracker Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Tracker\Twig;

use Joomla\Database\DatabaseDriver;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension integrating relation support
 *
 * @since  1.0
 */
class RelationExtension extends AbstractExtension
{
    /**
     * A list of the tracker's issue relations
     *
     * @var    array
     * @since  1.0
     */
    private const ISSUE_RELATIONS = [
        'duplicate_of' => 'Duplicate of',
        'related_to'   => 'Related to',
        'not_before'   => 'Not before',
        'pr_for'       => 'Pull Request for',
    ];

    /**
     * Database driver
     *
     * @var    DatabaseDriver
     * @since  1.0
     */
    private $db;

    /**
     * Cached relation data, lazy loaded when needed
     *
     * @var    array|null
     * @since  1.0
     */
    private $relationTypes;

    /**
     * Constructor.
     *
     * @param   DatabaseDriver  $db  Database driver.
     *
     * @since   1.0
     */
    public function __construct(DatabaseDriver $db)
    {
        $this->db = $db;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return  TwigFunction[]  An array of functions.
     *
     * @since   1.0
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('issue_relation', [$this, 'getIssueRelation']),
            new TwigFunction('issue_relation_type', [$this, 'getIssueRelationType']),
            new TwigFunction('issue_relation_types', [$this, 'getIssueRelationTypes']),
        ];
    }

    /**
     * Retrieves a human friendly relationship for a given type
     *
     * @param   string  $relation  Relation type
     *
     * @return  string
     *
     * @since   1.0
     */
    public function getIssueRelation($relation): string
    {
        return self::ISSUE_RELATIONS[$relation] ?? '';
    }

    /**
     * Get the issue relation type text.
     *
     * @param   integer  $id  The relation id.
     *
     * @return  string
     *
     * @since   1.0
     */
    public function getIssueRelationType($id): string
    {
        foreach ($this->getIssueRelationTypes() as $relType) {
            if ($relType->value == $id) {
                return $this->getIssueRelation($relType->text);
            }
        }

        return '';
    }

    /**
     * Get the list of issue relation types.
     *
     * @return  array
     *
     * @since   1.0
     */
    public function getIssueRelationTypes(): array
    {
        if ($this->relationTypes === null) {
            $this->relationTypes = $this->db->setQuery(
                $this->db->getQuery(true)
                    ->from($this->db->quoteName('#__issues_relations_types'))
                    ->select($this->db->quoteName('id', 'value'))
                    ->select($this->db->quoteName('name', 'text'))
            )->loadObjectList();
        }

        return $this->relationTypes;
    }
}
