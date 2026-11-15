<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Tracker\Tests\App\Tracker\Helper;

use App\Tracker\Helper\DescriptionSectionsHelper;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

/**
 * Tests for replacing sections of a pull request description.
 *
 * @since  1.0
 */
class DescriptionSectionsHelperTest extends TestCase
{
    /**
     * A description following the joomla-cms pull request template.
     */
    private const TEMPLATE = <<<'MD'
Pull Request for Issue #123.

### Summary of Changes

Fixes the thing.

### Testing Instructions

see issue

### Actual result BEFORE applying this Pull Request

broken

### Expected result AFTER applying this Pull Request

works

### Link to documentations
Please select:
- [x] No documentation changes for guide.joomla.org needed

MD;

    /**
     * A proposal as the review tool writes it.
     */
    private const PROPOSAL = <<<'MD'
### Testing Instructions

1. Open Articles.
2. Save an article.

### Actual result BEFORE applying this Pull Request

An error is shown.

### Expected result AFTER applying this Pull Request

The article is saved.
MD;

    #[TestDox('Each section of the proposal replaces the section with the same heading')]
    public function testReplacesTemplateSections(): void
    {
        $expected = <<<'MD'
Pull Request for Issue #123.

### Summary of Changes

Fixes the thing.

### Testing Instructions

1. Open Articles.
2. Save an article.

### Actual result BEFORE applying this Pull Request

An error is shown.

### Expected result AFTER applying this Pull Request

The article is saved.

### Link to documentations
Please select:
- [x] No documentation changes for guide.joomla.org needed

MD;

        $this->assertSame(self::lf($expected), DescriptionSectionsHelper::replaceSections(self::lf(self::TEMPLATE), self::lf(self::PROPOSAL)));
    }

    #[TestDox('CRLF line endings of the description are kept')]
    public function testKeepsCrlfLineEndings(): void
    {
        $description = str_replace("\n", "\r\n", self::lf(self::TEMPLATE));

        $result = DescriptionSectionsHelper::replaceSections($description, self::lf(self::PROPOSAL));

        $this->assertStringContainsString("### Testing Instructions\r\n\r\n1. Open Articles.\r\n2. Save an article.\r\n\r\n### Actual", $result);
        $this->assertStringNotContainsString("see issue", $result);
        $this->assertSame(substr_count($result, "\n"), substr_count($result, "\r\n"));
    }

    #[TestDox('Headings are matched regardless of case, level and markup, and the description keeps its own heading')]
    public function testMatchesHeadingsLoosely(): void
    {
        $description = "## **Testing instructions:**\n\nold steps\n\n## Other\n\ntext\n";

        $result = DescriptionSectionsHelper::replaceSections($description, "### Testing Instructions\n\nnew steps");

        $this->assertSame("## **Testing instructions:**\n\nnew steps\n\n## Other\n\ntext\n", $result);
    }

    #[TestDox('A missing section is inserted before "Link to documentations"')]
    public function testInsertsMissingSectionBeforeDocumentationLinks(): void
    {
        $description = "### Summary of Changes\n\nFix.\n\n### Link to documentations\n- [x] none\n";

        $result = DescriptionSectionsHelper::replaceSections($description, "### Testing Instructions\n\n1. Step");

        $this->assertSame(
            "### Summary of Changes\n\nFix.\n\n### Testing Instructions\n\n1. Step\n\n### Link to documentations\n- [x] none\n",
            $result
        );
    }

    #[TestDox('A missing section is appended to a description without the template')]
    public function testAppendsMissingSection(): void
    {
        $result = DescriptionSectionsHelper::replaceSections("Fixes the bug.\n\n", "### Testing Instructions\n\n1. Step");

        $this->assertSame("Fixes the bug.\n\n### Testing Instructions\n\n1. Step", $result);
    }

    #[TestDox('An empty description gets the section')]
    public function testFillsEmptyDescription(): void
    {
        $this->assertSame("### Testing Instructions\n\n1. Step", DescriptionSectionsHelper::replaceSections('', "### Testing Instructions\n\n1. Step"));
    }

    #[TestDox('A proposal without headings becomes the testing instructions')]
    public function testProposalWithoutHeadings(): void
    {
        $result = DescriptionSectionsHelper::replaceSections(self::lf(self::TEMPLATE), "1. Open Articles.");

        $this->assertStringContainsString("### Testing Instructions\n\n1. Open Articles.\n\n### Actual result", $result);
        $this->assertStringContainsString("### Actual result BEFORE applying this Pull Request\n\nbroken\n", $result);
    }

    #[TestDox('Subheadings inside a section are replaced with it, and the last section keeps the trailing newline')]
    public function testReplacesSubheadingsAndLastSection(): void
    {
        $description = "### Summary\n\ntext\n\n### Testing Instructions\n\nold\n\n#### Setup\n\nold setup\n";

        $result = DescriptionSectionsHelper::replaceSections($description, "### Testing Instructions\n\nnew");

        $this->assertSame("### Summary\n\ntext\n\n### Testing Instructions\n\nnew\n", $result);
    }

    #[TestDox('Headings inside code blocks are ignored')]
    public function testIgnoresHeadingsInCodeBlocks(): void
    {
        $description = "### Testing Instructions\n\n```\n### not a heading\n```\n\n### Next\n\nkeep\n";
        $proposal    = "### Testing Instructions\n\n```bash\n# run this\n```";

        $result = DescriptionSectionsHelper::replaceSections($description, $proposal);

        $this->assertSame("### Testing Instructions\n\n```bash\n# run this\n```\n\n### Next\n\nkeep\n", $result);
    }

    #[TestDox('The content of a section is found by its heading')]
    public function testGetSection(): void
    {
        $this->assertSame('see issue', DescriptionSectionsHelper::getSection(str_replace("\n", "\r\n", self::lf(self::TEMPLATE)), '### testing instructions'));
        $this->assertNull(DescriptionSectionsHelper::getSection('No template', '### Testing Instructions'));
    }

    /**
     * Normalise the line endings of a heredoc, which depend on how git checked out this file.
     *
     * @param   string  $text  The text.
     *
     * @return  string
     */
    private static function lf(string $text): string
    {
        return str_replace("\r\n", "\n", $text);
    }
}
