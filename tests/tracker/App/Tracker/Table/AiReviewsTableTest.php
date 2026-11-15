<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Tracker\Tests\App\Tracker\Table;

use App\Tracker\Table\AiReviewsTable;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the AI reviews table.
 *
 * @since  1.0
 */
class AiReviewsTableTest extends TestCase
{
    /**
     * The description hash ignores the line ending style.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testHashDescriptionNormalisesLineEndings(): void
    {
        $hash = sha1("### Summary\n\nText\n");

        $this->assertSame($hash, AiReviewsTable::hashDescription("### Summary\n\nText\n"));
        $this->assertSame($hash, AiReviewsTable::hashDescription("### Summary\r\n\r\nText\r\n"));
        $this->assertNotSame($hash, AiReviewsTable::hashDescription("### Summary\n\nOther text\n"));
    }

    /**
     * An empty or missing description has a stable hash.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testHashDescriptionOfEmptyDescription(): void
    {
        $this->assertSame(sha1(''), AiReviewsTable::hashDescription(null));
        $this->assertSame(sha1(''), AiReviewsTable::hashDescription(''));
    }

    /**
     * The SQL expression applies the same normalisation to the given column.
     *
     * @return  void
     *
     * @since   1.0
     */
    public function testHashDescriptionSql(): void
    {
        $this->assertSame(
            "SHA1(REPLACE(COALESCE(`i`.`description_raw`, ''), CHAR(13, 10), CHAR(10)))",
            AiReviewsTable::hashDescriptionSql('`i`.`description_raw`')
        );
    }
}
