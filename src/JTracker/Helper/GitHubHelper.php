<?php
/**
 * Part of the Joomla Tracker
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Helper;

use App\Projects\TrackerProject;
use App\Tracker\Model\ActivityModel;

use Joomla\Database\DatabaseDriver;
use Joomla\Date\Date;

use JTracker\Github\DataType\JTracker\Issues\Comment;
use JTracker\GitHub\Github;

/**
 * Helper class containing methods for working with GitHub.
 *
 * @since  1.0
 */
class GitHubHelper
{
	/**
	 * @var Github
	 */
	private $gitHub;

	/**
	 * Constructor.
	 *
	 * @param   Github  $gitHub  The GitHub object.
	 */
	public function __construct($gitHub)
	{
		$this->gitHub = $gitHub;
	}

	/**
	 * Add a comment on GitHub.
	 *
	 * @param   TrackerProject  $project      The project.
	 * @param   integer         $issueNumber  The issue number.
	 * @param   string          $comment      The comment to add.
	 * @param   string          $userName     The username.
	 * @param   DatabaseDriver  $database     The database driver object.
	 *
	 * @return  Comment  The GitHub comment object
	 *
	 * @throws \DomainException
	 *
	 * @since  1.0
	 */
	public function addComment(TrackerProject $project, $issueNumber, $comment, $userName, DatabaseDriver $database)
	{
		$data = new Comment;

		if ($project->gh_user && $project->gh_project)
		{
			$gitHubResponse = $this->gitHub->issues->comments->create(
				$project->gh_user, $project->gh_project, $issueNumber, $comment
			);

			if (!isset($gitHubResponse->id))
			{
				throw new \DomainException('Invalid response from GitHub');
			}

			$data->created_at = $gitHubResponse->created_at;
			$data->opened_by = $gitHubResponse->user->login;
			$data->comment_id = $gitHubResponse->id;
			$data->text_raw = $gitHubResponse->body;

			$data->text = $this->gitHub->markdown->render(
				$comment,
				'gfm',
				$project->gh_user . '/' . $project->gh_project
			);
		}
		else
		{
			$date = new Date;

			$data->created_at = $date->format($database->getDateFormat());
			$data->opened_by  = $userName;
			$data->comment_id = '???';
			$data->text_raw = $comment;
			$data->text = $this->gitHub->markdown->render($comment, 'markdown');
		}

		(new ActivityModel($database))
			->addActivityEvent(
				'comment', $data->created_at, $data->opened_by, $project->project_id,
				$issueNumber, $data->comment_id, $data->text, $data->text_raw
			);

		$data->activities_id = $database->insertid();

		$date = new Date($data->created_at);
		$data->created_at = $date->format('j M Y');

		return $data;
	}
}
