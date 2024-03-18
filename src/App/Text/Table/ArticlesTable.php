<?php

/**
 * Part of the Joomla Tracker's Text Application
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace App\Text\Table;

use Joomla\Database\DatabaseDriver;
use Joomla\Filter\OutputFilter;
use JTracker\Database\AbstractDatabaseTable;

/**
 * Table interface class for the #__articles table
 *
 * @property   integer  $article_id    PK
 * @property   string   $path          The article path.
 * @property   string   $title         The article title.
 * @property   string   $alias         The article alias.
 * @property   string   $text          The article text.
 * @property   string   $text_md       The raw article text.
 * @property   string   $created_date  The created date.
 * @property   integer  $is_file       If the text is present as a file (for different handling)
 *
 * @since  1.0
 */
class ArticlesTable extends AbstractDatabaseTable
{
    /**
     * Constructor
     *
     * @param   DatabaseDriver  $db  A database connector object
     *
     * @since   1.0
     */
    public function __construct(DatabaseDriver $db)
    {
        parent::__construct('#__articles', 'article_id', $db);
    }

    /**
     * Method to perform sanity checks on the AbstractDatabaseTable instance properties to ensure
     * they are safe to store in the database.
     *
     * @return  $this  Method allows chaining
     *
     * @since   1.0
     * @throws  \InvalidArgumentException
     */
    public function check()
    {
        $errors = [];

        if (trim($this->alias) === '') {
            if (trim($this->title)) {
                $this->alias = trim($this->title);
            } else {
                $errors[] = 'An alias or a title is required.';
            }
        }

        if (trim($this->text_md) === '') {
            $errors[] = 'Some text is required.';
        }

        $this->alias = OutputFilter::stringUrlUnicodeSlug($this->alias);

        if ($errors) {
            throw new \InvalidArgumentException(implode("\n", $errors));
        }

        return $this;
    }

    /**
     * Method to store a row in the database from the AbstractDatabaseTable instance properties.
     * If a primary key value is set the row with that primary key value will be
     * updated with the instance property values.  If no primary key value is set
     * a new row will be inserted into the database with the properties from the
     * AbstractDatabaseTable instance.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  $this  Method allows chaining
     *
     * @since   1.0
     */
    public function store($updateNulls = false)
    {
        if (!$this->created_date || $this->created_date === $this->db->getNullDate()) {
            // New item
            $this->created_date = (new \DateTime('now', new \DateTimeZone('UTC')))->format($this->db->getDateFormat());
        }

        return parent::store($updateNulls);
    }
}
