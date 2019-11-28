<?php
/**
 * Part of the Joomla Framework Github Package
 *
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace JTracker\Github\Package;

use JTracker\Github\Package;

/**
 * GitHub API Issues class for the Joomla Framework.
 *
 * @documentation http://developer.github.com/v3/issues
 *
 * @since  1.0
 *
 * @property-read  \Joomla\Github\Issues\Assignees   $assignees   GitHub API object for assignees.
 * @property-read  \JTracker\Github\Issues\Comments  $comments    GitHub API object for comments.
 * @property-read  \JTracker\Github\Issues\Events    $events      GitHub API object for events.
 * @property-read  \Joomla\Github\Issues\Labels      $labels      GitHub API object for labels.
 * @property-read  \Joomla\Github\Issues\Milestones  $milestones  GitHub API object for milestones.
 */
class Issues extends Package
{
	/**
	 * Create an issue.
	 *
	 * @param   string    $user       The name of the owner of the GitHub repository.
	 * @param   string    $repo       The name of the GitHub repository.
	 * @param   string    $title      The title of the new issue.
	 * @param   string    $body       The body text for the new issue.
	 * @param   string    $assignee   The login for the GitHub user that this issue should be assigned to.
	 * @param   integer   $milestone  The milestone to associate this issue with.
	 * @param   string[]  $labels     The labels to associate with this issue.
	 * @param   string[]  $assignees  The logins for GitHub users to assign to this issue.
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \DomainException
	 */
	public function create($user, $repo, $title, $body = null, $assignee = null, $milestone = null, array $labels = array(), array $assignees = array())
	{
		// Build the request path.
		$path = '/repos/' . $user . '/' . $repo . '/issues';

		// Ensure that we have a non-associative array.
		if (!empty($labels))
		{
			$labels = array_values($labels);
		}

		// Build the request data.
		$data = json_encode(
			array(
				'title'     => $title,
				'assignee'  => $assignee,
				'milestone' => $milestone,
				'labels'    => $labels,
				'body'      => $body,
				'assignees' => $assignees,
			)
		);

		// Send the request.
		return $this->processResponse($this->client->post($this->fetchUrl($path), $data), 201);
	}

	/**
	 * Edit an issue.
	 *
	 * @param   string   $user       The name of the owner of the GitHub repository.
	 * @param   string   $repo       The name of the GitHub repository.
	 * @param   integer  $issueId    The issue number.
	 * @param   string   $state      The optional new state for the issue. [open, closed]
	 * @param   string   $title      The title of the new issue.
	 * @param   string   $body       The body text for the new issue.
	 * @param   string   $assignee   The login for the GitHub user that this issue should be assigned to.
	 * @param   integer  $milestone  The milestone to associate this issue with.
	 * @param   array    $labels     The labels to associate with this issue.
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \DomainException
	 */
	public function edit($user, $repo, $issueId, $state = null, $title = null, $body = null, $assignee = null, $milestone = null, array $labels = null)
	{
		// Build the request path.
		$path = '/repos/' . $user . '/' . $repo . '/issues/' . (int) $issueId;

		// Create the data object.
		$data = new \stdClass;

		// If a title is set add it to the data object.
		if (isset($title))
		{
			$data->title = $title;
		}

		// If a body is set add it to the data object.
		if (isset($body))
		{
			$data->body = $body;
		}

		// If a state is set add it to the data object.
		if (isset($state))
		{
			$data->state = $state;
		}

		// If an assignee is set add it to the data object.
		if (isset($assignee))
		{
			$data->assignee = $assignee;
		}

		// If a milestone is set add it to the data object.
		if (isset($milestone))
		{
			$data->milestone = $milestone;
		}

		// If labels are set add them to the data object.
		if (isset($labels))
		{
			// Ensure that we have a non-associative array.
			if (isset($labels))
			{
				$labels = array_values($labels);
			}

			$data->labels = $labels;
		}

		// Encode the request data.
		$data = json_encode($data);

		// Send the request.
		return $this->processResponse($this->client->patch($this->fetchUrl($path), $data));
	}

	/**
	 * Get a single issue.
	 *
	 * @param   string   $user     The name of the owner of the GitHub repository.
	 * @param   string   $repo     The name of the GitHub repository.
	 * @param   integer  $issueId  The issue number.
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \DomainException
	 */
	public function get($user, $repo, $issueId)
	{
		// Build the request path.
		$path = '/repos/' . $user . '/' . $repo . '/issues/' . (int) $issueId;

		// Send the request.
		return $this->processResponse($this->client->get($this->fetchUrl($path)));
	}

	/**
	 * List issues.
	 *
	 * @param   string              $filter     The filter type: assigned, created, mentioned, subscribed.
	 * @param   string              $state      The optional state to filter requests by. [open, closed]
	 * @param   string              $labels     The list of comma separated Label names. Example: bug,ui,@high.
	 * @param   string              $sort       The sort order: created, updated, comments, default: created.
	 * @param   string              $direction  The list direction: asc or desc, default: desc.
	 * @param   \DateTimeInterface  $since      Only issues updated at or after this time are returned.
	 * @param   integer             $page       The page number from which to get items.
	 * @param   integer             $limit      The number of items on a page.
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \DomainException
	 */
	public function getList($filter = null, $state = null, $labels = null, $sort = null,
		$direction = null, \DateTimeInterface $since = null, $page = 0, $limit = 0
	)
	{
		// Build the request path.
		$path = '/issues';

		$uri = $this->fetchUrl($path, $page, $limit);

		if ($filter)
		{
			$uri->setVar('filter', $filter);
		}

		if ($state)
		{
			$uri->setVar('state', $state);
		}

		if ($labels)
		{
			$uri->setVar('labels', $labels);
		}

		if ($sort)
		{
			$uri->setVar('sort', $sort);
		}

		if ($direction)
		{
			$uri->setVar('direction', $direction);
		}

		if ($since)
		{
			$uri->setVar('since', $since->format(\DateTime::ISO8601));
		}

		// Send the request.
		return $this->processResponse($this->client->get((string) $uri));
	}

	/**
	 * List issues for a repository.
	 *
	 * @param   string     $user       The name of the owner of the GitHub repository.
	 * @param   string     $repo       The name of the GitHub repository.
	 * @param   string     $milestone  The milestone number, 'none', or *.
	 * @param   string     $state      The optional state to filter requests by. [open, closed]
	 * @param   string     $assignee   The assignee name, 'none', or *.
	 * @param   string     $mentioned  The GitHub user name.
	 * @param   string     $labels     The list of comma separated Label names. Example: bug,ui,@high.
	 * @param   string     $sort       The sort order: created, updated, comments, default: created.
	 * @param   string     $direction  The list direction: asc or desc, default: desc.
	 * @param   \DateTime  $since      Only issues updated at or after this time are returned.
	 * @param   integer    $page       The page number from which to get items.
	 * @param   integer    $limit      The number of items on a page.
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \DomainException
	 */
	public function getListByRepository($user, $repo, $milestone = null, $state = null, $assignee = null, $mentioned = null, $labels = null,
		$sort = null, $direction = null, \DateTime $since = null, $page = 0, $limit = 0
	)
	{
		// Build the request path.
		$path = '/repos/' . $user . '/' . $repo . '/issues';

		$uri = $this->fetchUrl($path, $page, $limit);

		if ($milestone)
		{
			$uri->setVar('milestone', $milestone);
		}

		if ($state)
		{
			$uri->setVar('state', $state);
		}

		if ($assignee)
		{
			$uri->setVar('assignee', $assignee);
		}

		if ($mentioned)
		{
			$uri->setVar('mentioned', $mentioned);
		}

		if ($labels)
		{
			$uri->setVar('labels', $labels);
		}

		if ($sort)
		{
			$uri->setVar('sort', $sort);
		}

		if ($direction)
		{
			$uri->setVar('direction', $direction);
		}

		if ($since)
		{
			$uri->setVar('since', $since->format(\DateTime::RFC3339));
		}

		// Send the request.
		return $this->processResponse($this->client->get((string) $uri));
	}

	/**
	 * Lock an issue.
	 *
	 * @param   string   $user     The name of the owner of the GitHub repository.
	 * @param   string   $repo     The name of the GitHub repository.
	 * @param   integer  $issueId  The issue number.
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \DomainException
	 */
	public function lock($user, $repo, $issueId)
	{
		// Build the request path.
		$path = "/repos/$user/$repo/issues/" . (int) $issueId . '/lock';

		return $this->processResponse($this->client->put($this->fetchUrl($path), array()), 204);
	}

	/**
	 * Unlock an issue.
	 *
	 * @param   string   $user     The name of the owner of the GitHub repository.
	 * @param   string   $repo     The name of the GitHub repository.
	 * @param   integer  $issueId  The issue number.
	 *
	 * @return  object
	 *
	 * @since   1.0
	 * @throws  \DomainException
	 */
	public function unlock($user, $repo, $issueId)
	{
		// Build the request path.
		$path = "/repos/$user/$repo/issues/" . (int) $issueId . '/lock';

		return $this->processResponse($this->client->delete($this->fetchUrl($path)), 204);
	}
}
