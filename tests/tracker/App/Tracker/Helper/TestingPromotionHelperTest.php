<?php

/**
 * Part of the Joomla! Tracker Package.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Tracker\Tests\App\Tracker\Helper;

use App\Projects\TrackerProject;
use App\Tracker\Helper\TestingPromotionHelper;
use App\Tracker\Model\PromotionModel;
use Joomla\Database\DatabaseDriver;
use JTracker\Mattermost\MattermostNotifier;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests for posting pull requests to the testing channel.
 *
 * @since  1.0
 */
class TestingPromotionHelperTest extends TestCase
{
    #[TestDox('The message links the pull request and shows tests, setup, promoter and instructions')]
    public function testBuildMessage(): void
    {
        $message = TestingPromotionHelper::buildMessage(
            $this->project(),
            $this->item("### Summary\n\nFix.\n\n### Testing Instructions\n\n1. Open Articles.\n2. Save.\n\n### Actual result\n\nbroken"),
            'maintainer',
            'https://issues.joomla.org/',
            1,
            'yes'
        );

        $expected = <<<'MD'
#### Testers wanted: [#48496 Fix \[a11y\] label](https://issues.joomla.org/tracker/joomla-cms/48496)
[Pull request on GitHub](https://github.com/joomla/joomla-cms/pull/48496) · Successful tests: 1 of 2 · Test setup: testable in the browser on a fresh install · Promoted by @maintainer

**Testing instructions**
> 1. Open Articles.
> 2. Save.

Please report your test result in the tracker: [https://issues.joomla.org/tracker/joomla-cms/48496](https://issues.joomla.org/tracker/joomla-cms/48496)
MD;

        // The line endings of the heredoc depend on how git checked out this file
        $this->assertSame(str_replace("\r\n", "\n", $expected), $message);
    }

    #[TestDox('Long instructions are shortened with a link, and missing instructions are mentioned')]
    public function testBuildMessageShortensAndHandlesMissingInstructions(): void
    {
        $steps   = implode("\n", array_map(static fn ($n) => $n . '. Step', range(1, 20)));
        $message = TestingPromotionHelper::buildMessage($this->project(), $this->item("### Testing Instructions\n\n" . $steps), 'm', 'https://x/', 0, null);

        $this->assertStringContainsString("> 15. Step\n> …\n\n[Read the full instructions](https://x/tracker/joomla-cms/48496)", $message);
        $this->assertStringNotContainsString('16. Step', $message);
        $this->assertStringNotContainsString('Test setup', $message);

        $message = TestingPromotionHelper::buildMessage($this->project(), $this->item('No template.'), 'm', 'https://x/', 0, null);

        $this->assertStringContainsString('_The pull request has no testing instructions yet._', $message);
    }

    #[TestDox('A pull request is posted once per label')]
    public function testPromotePostsOnce(): void
    {
        $model = $this->createMock(PromotionModel::class);
        $model->method('claim')->willReturnOnConsecutiveCalls(true, false);
        $model->method('countSuccessfulTests')->willReturn(0);
        $model->method('getTestSuitability')->willReturn(null);
        $model->expects($this->never())->method('release');

        $notifier = $this->createMock(MattermostNotifier::class);
        $notifier->expects($this->once())->method('post');

        $helper = new TestingPromotionHelper($notifier, $model, new NullLogger());

        $this->assertTrue($helper->promote($this->project(), $this->item(''), 'Testers wanted', 'm', 'https://x/'));
        $this->assertFalse($helper->promote($this->project(), $this->item(''), 'Testers wanted', 'm', 'https://x/'));
    }

    #[TestDox('If posting fails, the claim is released so the next label change tries again, and nothing is thrown')]
    public function testPromoteReleasesClaimOnFailure(): void
    {
        $model = $this->createMock(PromotionModel::class);
        $model->method('claim')->willReturn(true);
        $model->expects($this->once())->method('release')->with(77, 'Testers wanted');

        $notifier = $this->createStub(MattermostNotifier::class);
        $notifier->method('post')->willThrowException(new \RuntimeException('Mattermost answered with 500'));

        $helper = new TestingPromotionHelper($notifier, $model, new NullLogger());

        $this->assertFalse($helper->promote($this->project(), $this->item(''), 'Testers wanted', 'm', 'https://x/'));
    }

    /**
     * The joomla-cms project.
     *
     * @return  TrackerProject
     */
    private function project(): TrackerProject
    {
        return new TrackerProject(
            $this->createStub(DatabaseDriver::class),
            ['project_id' => 1, 'alias' => 'joomla-cms', 'gh_user' => 'joomla', 'gh_project' => 'joomla-cms']
        );
    }

    /**
     * Pull request #48496 with the given description.
     *
     * @param   string  $description  The raw description.
     *
     * @return  object
     */
    private function item(string $description): object
    {
        return (object) [
            'id'              => 77,
            'issue_number'    => 48496,
            'title'           => 'Fix [a11y] label',
            'description_raw' => $description,
            'pr_head_sha'     => str_repeat('a', 40),
        ];
    }
}
