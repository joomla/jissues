<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CliApp\Command\Test;

use App\Projects\TrackerProject;

use CliApp\Exception\AbortException;

use Joomla\Github\Github;
use Joomla\Filesystem\Folder;

/**
 * Class for testing web hooks.
 *
 * @since  1.0
 */
class Hook extends Test
{
	/**
	 * Hook controller
	 *
	 * @var    \App\Tracker\Controller\AbstractHookController
	 * @since  1.0
	 */
	protected $controller;

	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Tests web hooks';

	/**
	 * Joomla! Github object
	 *
	 * @var    Github
	 * @since  1.0
	 */
	protected $github;

	/**
	 * The project object.
	 *
	 * @var    TrackerProject
	 * @since  1.0
	 */
	protected $project;

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 * @throws  \UnexpectedValueException
	 */
	public function execute()
	{
		// Define JPATH_THEMES as it is used in the hooks
		define('JPATH_THEMES', JPATH_ROOT . '/www');

		$this->getApplication()->outputTitle('Test Hooks');

		$this->logOut('Start testing hook');

		$this->selectProject()->selectHook();

		$this->getApplication()->input->set('project', $this->project->project_id);

		$this->setupGitHub();

		$this->controller->execute();
	}

	/**
	 * Select the hook.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  AbortException
	 */
	protected function selectHook()
	{
		$files = Folder::files(JPATH_ROOT . '/src/App/Tracker/Controller/Hooks');
		$hooks = array();

		foreach ($files as $file)
		{
			$hooks[] = str_replace(array('Receive', 'Hook.php'), '', $file);
		}

		$this->out()
			->out('<b>Available hooks:</b>')
			->out();

		$cnt = 1;

		$checks = array();

		foreach ($hooks as $hook)
		{
			$this->out('  <b>' . $cnt . '</b> ' . $hook);
			$checks[$cnt] = $hook;
			$cnt++;
		}

		$this->out()
			->out('<question>Select a hook:</question> ', false);

		$resp = (int) trim($this->getApplication()->in());

		if (!$resp)
		{
			throw new AbortException('Aborted');
		}

		if (false == array_key_exists($resp, $checks))
		{
			throw new AbortException('Invalid hook');
		}

		$classname = '\\App\\Tracker\\Controller\\Hooks\\Receive' . $checks[$resp] . 'Hook';

		// Initialize the hook controller
		$this->controller = new $classname;
		$this->controller->setContainer($this->container);

		if ($this->project->project_id === '1' && $resp === 3)
		{
			// @codingStandardsIgnoreStart
			// @todo this might go in a separate file
			$this->getApplication()->input->post->set('payload', '{"action":"synchronize","number":2783,"pull_request":{"url":"https://api.github.com/repos/joomla/joomla-cms/pulls/2783","id":11433958,"html_url":"https://github.com/joomla/joomla-cms/pull/2783","diff_url":"https://github.com/joomla/joomla-cms/pull/2783.diff","patch_url":"https://github.com/joomla/joomla-cms/pull/2783.patch","issue_url":"https://github.com/joomla/joomla-cms/pull/2783","number":2783,"state":"open","title":"Coding-style fixes for plugins.","user":{"login":"chrisdavenport","id":305153,"avatar_url":"https://gravatar.com/avatar/80cfa1e2542242b9be57dc0def07d381?d=https%3A%2F%2Fidenticons.github.com%2F73008553ed8e7d519b07642e43ec9d42.png&r=x","gravatar_id":"80cfa1e2542242b9be57dc0def07d381","url":"https://api.github.com/users/chrisdavenport","html_url":"https://github.com/chrisdavenport","followers_url":"https://api.github.com/users/chrisdavenport/followers","following_url":"https://api.github.com/users/chrisdavenport/following{/other_user}","gists_url":"https://api.github.com/users/chrisdavenport/gists{/gist_id}","starred_url":"https://api.github.com/users/chrisdavenport/starred{/owner}{/repo}","subscriptions_url":"https://api.github.com/users/chrisdavenport/subscriptions","organizations_url":"https://api.github.com/users/chrisdavenport/orgs","repos_url":"https://api.github.com/users/chrisdavenport/repos","events_url":"https://api.github.com/users/chrisdavenport/events{/privacy}","received_events_url":"https://api.github.com/users/chrisdavenport/received_events","type":"User","site_admin":false},"body":"This fixes the vast majority of coding-style issues in the /plugins directory.  It\'s a bit of an experiment to assess how easy/difficult it would be to bring the entire CMS into compliance and how much time it would take to do it.","created_at":"2014-01-12T10:54:17Z","updated_at":"2014-01-18T15:29:16Z","closed_at":null,"merged_at":null,"merge_commit_sha":"a37e10e01cd2aa4e851111dd6a85c545e7ab1320","assignee":null,"milestone":null,"commits_url":"https://github.com/joomla/joomla-cms/pull/2783/commits","review_comments_url":"https://github.com/joomla/joomla-cms/pull/2783/comments","review_comment_url":"/repos/joomla/joomla-cms/pulls/comments/{number}","comments_url":"https://api.github.com/repos/joomla/joomla-cms/issues/2783/comments","statuses_url":"https://api.github.com/repos/joomla/joomla-cms/statuses/c8da6766b76b4285c9ea8f6458729dfc6ca92ef0","head":{"label":"chrisdavenport:coding-style-fixes","ref":"coding-style-fixes","sha":"c8da6766b76b4285c9ea8f6458729dfc6ca92ef0","user":{"login":"chrisdavenport","id":305153,"avatar_url":"https://gravatar.com/avatar/80cfa1e2542242b9be57dc0def07d381?d=https%3A%2F%2Fidenticons.github.com%2F73008553ed8e7d519b07642e43ec9d42.png&r=x","gravatar_id":"80cfa1e2542242b9be57dc0def07d381","url":"https://api.github.com/users/chrisdavenport","html_url":"https://github.com/chrisdavenport","followers_url":"https://api.github.com/users/chrisdavenport/followers","following_url":"https://api.github.com/users/chrisdavenport/following{/other_user}","gists_url":"https://api.github.com/users/chrisdavenport/gists{/gist_id}","starred_url":"https://api.github.com/users/chrisdavenport/starred{/owner}{/repo}","subscriptions_url":"https://api.github.com/users/chrisdavenport/subscriptions","organizations_url":"https://api.github.com/users/chrisdavenport/orgs","repos_url":"https://api.github.com/users/chrisdavenport/repos","events_url":"https://api.github.com/users/chrisdavenport/events{/privacy}","received_events_url":"https://api.github.com/users/chrisdavenport/received_events","type":"User","site_admin":false},"repo":{"id":15442073,"name":"joomla-cms","full_name":"chrisdavenport/joomla-cms","owner":{"login":"chrisdavenport","id":305153,"avatar_url":"https://gravatar.com/avatar/80cfa1e2542242b9be57dc0def07d381?d=https%3A%2F%2Fidenticons.github.com%2F73008553ed8e7d519b07642e43ec9d42.png&r=x","gravatar_id":"80cfa1e2542242b9be57dc0def07d381","url":"https://api.github.com/users/chrisdavenport","html_url":"https://github.com/chrisdavenport","followers_url":"https://api.github.com/users/chrisdavenport/followers","following_url":"https://api.github.com/users/chrisdavenport/following{/other_user}","gists_url":"https://api.github.com/users/chrisdavenport/gists{/gist_id}","starred_url":"https://api.github.com/users/chrisdavenport/starred{/owner}{/repo}","subscriptions_url":"https://api.github.com/users/chrisdavenport/subscriptions","organizations_url":"https://api.github.com/users/chrisdavenport/orgs","repos_url":"https://api.github.com/users/chrisdavenport/repos","events_url":"https://api.github.com/users/chrisdavenport/events{/privacy}","received_events_url":"https://api.github.com/users/chrisdavenport/received_events","type":"User","site_admin":false},"private":false,"html_url":"https://github.com/chrisdavenport/joomla-cms","description":"Home of the Joomla! Content Management System","fork":true,"url":"https://api.github.com/repos/chrisdavenport/joomla-cms","forks_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/forks","keys_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/keys{/key_id}","collaborators_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/collaborators{/collaborator}","teams_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/teams","hooks_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/hooks","issue_events_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/issues/events{/number}","events_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/events","assignees_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/assignees{/user}","branches_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/branches{/branch}","tags_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/tags","blobs_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/git/blobs{/sha}","git_tags_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/git/tags{/sha}","git_refs_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/git/refs{/sha}","trees_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/git/trees{/sha}","statuses_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/statuses/{sha}","languages_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/languages","stargazers_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/stargazers","contributors_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/contributors","subscribers_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/subscribers","subscription_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/subscription","commits_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/commits{/sha}","git_commits_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/git/commits{/sha}","comments_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/comments{/number}","issue_comment_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/issues/comments/{number}","contents_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/contents/{+path}","compare_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/compare/{base}...{head}","merges_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/merges","archive_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/{archive_format}{/ref}","downloads_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/downloads","issues_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/issues{/number}","pulls_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/pulls{/number}","milestones_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/milestones{/number}","notifications_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/notifications{?since,all,participating}","labels_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/labels{/name}","releases_url":"https://api.github.com/repos/chrisdavenport/joomla-cms/releases{/id}","created_at":"2013-12-25T23:51:33Z","updated_at":"2014-01-18T15:29:14Z","pushed_at":"2014-01-18T15:29:14Z","git_url":"git://github.com/chrisdavenport/joomla-cms.git","ssh_url":"git@github.com:chrisdavenport/joomla-cms.git","clone_url":"https://github.com/chrisdavenport/joomla-cms.git","svn_url":"https://github.com/chrisdavenport/joomla-cms","homepage":"http://joomla.org","size":105528,"stargazers_count":0,"watchers_count":0,"language":"PHP","has_issues":false,"has_downloads":true,"has_wiki":true,"forks_count":0,"mirror_url":null,"open_issues_count":0,"forks":0,"open_issues":0,"watchers":0,"default_branch":"master","master_branch":"master"}},"base":{"label":"joomla:staging","ref":"staging","sha":"ff532960257e4a35c7218d2c779fe27da2741378","user":{"login":"joomla","id":751633,"avatar_url":"https://gravatar.com/avatar/ebafeb89560f7a68353d0ccc03fe79c7?d=https%3A%2F%2Fidenticons.github.com%2Fcb38053c967606d69bd6faf4544c1799.png&r=x","gravatar_id":"ebafeb89560f7a68353d0ccc03fe79c7","url":"https://api.github.com/users/joomla","html_url":"https://github.com/joomla","followers_url":"https://api.github.com/users/joomla/followers","following_url":"https://api.github.com/users/joomla/following{/other_user}","gists_url":"https://api.github.com/users/joomla/gists{/gist_id}","starred_url":"https://api.github.com/users/joomla/starred{/owner}{/repo}","subscriptions_url":"https://api.github.com/users/joomla/subscriptions","organizations_url":"https://api.github.com/users/joomla/orgs","repos_url":"https://api.github.com/users/joomla/repos","events_url":"https://api.github.com/users/joomla/events{/privacy}","received_events_url":"https://api.github.com/users/joomla/received_events","type":"Organization","site_admin":false},"repo":{"id":2464908,"name":"joomla-cms","full_name":"joomla/joomla-cms","owner":{"login":"joomla","id":751633,"avatar_url":"https://gravatar.com/avatar/ebafeb89560f7a68353d0ccc03fe79c7?d=https%3A%2F%2Fidenticons.github.com%2Fcb38053c967606d69bd6faf4544c1799.png&r=x","gravatar_id":"ebafeb89560f7a68353d0ccc03fe79c7","url":"https://api.github.com/users/joomla","html_url":"https://github.com/joomla","followers_url":"https://api.github.com/users/joomla/followers","following_url":"https://api.github.com/users/joomla/following{/other_user}","gists_url":"https://api.github.com/users/joomla/gists{/gist_id}","starred_url":"https://api.github.com/users/joomla/starred{/owner}{/repo}","subscriptions_url":"https://api.github.com/users/joomla/subscriptions","organizations_url":"https://api.github.com/users/joomla/orgs","repos_url":"https://api.github.com/users/joomla/repos","events_url":"https://api.github.com/users/joomla/events{/privacy}","received_events_url":"https://api.github.com/users/joomla/received_events","type":"Organization","site_admin":false},"private":false,"html_url":"https://github.com/joomla/joomla-cms","description":"Home of the Joomla! Content Management System","fork":false,"url":"https://api.github.com/repos/joomla/joomla-cms","forks_url":"https://api.github.com/repos/joomla/joomla-cms/forks","keys_url":"https://api.github.com/repos/joomla/joomla-cms/keys{/key_id}","collaborators_url":"https://api.github.com/repos/joomla/joomla-cms/collaborators{/collaborator}","teams_url":"https://api.github.com/repos/joomla/joomla-cms/teams","hooks_url":"https://api.github.com/repos/joomla/joomla-cms/hooks","issue_events_url":"https://api.github.com/repos/joomla/joomla-cms/issues/events{/number}","events_url":"https://api.github.com/repos/joomla/joomla-cms/events","assignees_url":"https://api.github.com/repos/joomla/joomla-cms/assignees{/user}","branches_url":"https://api.github.com/repos/joomla/joomla-cms/branches{/branch}","tags_url":"https://api.github.com/repos/joomla/joomla-cms/tags","blobs_url":"https://api.github.com/repos/joomla/joomla-cms/git/blobs{/sha}","git_tags_url":"https://api.github.com/repos/joomla/joomla-cms/git/tags{/sha}","git_refs_url":"https://api.github.com/repos/joomla/joomla-cms/git/refs{/sha}","trees_url":"https://api.github.com/repos/joomla/joomla-cms/git/trees{/sha}","statuses_url":"https://api.github.com/repos/joomla/joomla-cms/statuses/{sha}","languages_url":"https://api.github.com/repos/joomla/joomla-cms/languages","stargazers_url":"https://api.github.com/repos/joomla/joomla-cms/stargazers","contributors_url":"https://api.github.com/repos/joomla/joomla-cms/contributors","subscribers_url":"https://api.github.com/repos/joomla/joomla-cms/subscribers","subscription_url":"https://api.github.com/repos/joomla/joomla-cms/subscription","commits_url":"https://api.github.com/repos/joomla/joomla-cms/commits{/sha}","git_commits_url":"https://api.github.com/repos/joomla/joomla-cms/git/commits{/sha}","comments_url":"https://api.github.com/repos/joomla/joomla-cms/comments{/number}","issue_comment_url":"https://api.github.com/repos/joomla/joomla-cms/issues/comments/{number}","contents_url":"https://api.github.com/repos/joomla/joomla-cms/contents/{+path}","compare_url":"https://api.github.com/repos/joomla/joomla-cms/compare/{base}...{head}","merges_url":"https://api.github.com/repos/joomla/joomla-cms/merges","archive_url":"https://api.github.com/repos/joomla/joomla-cms/{archive_format}{/ref}","downloads_url":"https://api.github.com/repos/joomla/joomla-cms/downloads","issues_url":"https://api.github.com/repos/joomla/joomla-cms/issues{/number}","pulls_url":"https://api.github.com/repos/joomla/joomla-cms/pulls{/number}","milestones_url":"https://api.github.com/repos/joomla/joomla-cms/milestones{/number}","notifications_url":"https://api.github.com/repos/joomla/joomla-cms/notifications{?since,all,participating}","labels_url":"https://api.github.com/repos/joomla/joomla-cms/labels{/name}","releases_url":"https://api.github.com/repos/joomla/joomla-cms/releases{/id}","created_at":"2011-09-27T02:07:38Z","updated_at":"2014-01-18T15:27:07Z","pushed_at":"2014-01-18T15:27:07Z","git_url":"git://github.com/joomla/joomla-cms.git","ssh_url":"git@github.com:joomla/joomla-cms.git","clone_url":"https://github.com/joomla/joomla-cms.git","svn_url":"https://github.com/joomla/joomla-cms","homepage":"http://joomla.org","size":197173,"stargazers_count":1089,"watchers_count":1089,"language":"PHP","has_issues":true,"has_downloads":true,"has_wiki":true,"forks_count":1068,"mirror_url":null,"open_issues_count":535,"forks":1068,"open_issues":535,"watchers":1089,"default_branch":"staging","master_branch":"staging"}},"_links":{"self":{"href":"https://api.github.com/repos/joomla/joomla-cms/pulls/2783"},"html":{"href":"https://github.com/joomla/joomla-cms/pull/2783"},"issue":{"href":"https://api.github.com/repos/joomla/joomla-cms/issues/2783"},"comments":{"href":"https://api.github.com/repos/joomla/joomla-cms/issues/2783/comments"},"review_comments":{"href":"https://api.github.com/repos/joomla/joomla-cms/pulls/2783/comments"},"statuses":{"href":"https://api.github.com/repos/joomla/joomla-cms/statuses/c8da6766b76b4285c9ea8f6458729dfc6ca92ef0"}},"merged":false,"mergeable":null,"mergeable_state":"unknown","merged_by":null,"comments":1,"review_comments":2,"commits":2,"additions":577,"deletions":252,"changed_files":41},"repository":{"id":2464908,"name":"joomla-cms","full_name":"joomla/joomla-cms","owner":{"login":"joomla","id":751633,"avatar_url":"https://gravatar.com/avatar/ebafeb89560f7a68353d0ccc03fe79c7?d=https%3A%2F%2Fidenticons.github.com%2Fcb38053c967606d69bd6faf4544c1799.png&r=x","gravatar_id":"ebafeb89560f7a68353d0ccc03fe79c7","url":"https://api.github.com/users/joomla","html_url":"https://github.com/joomla","followers_url":"https://api.github.com/users/joomla/followers","following_url":"https://api.github.com/users/joomla/following{/other_user}","gists_url":"https://api.github.com/users/joomla/gists{/gist_id}","starred_url":"https://api.github.com/users/joomla/starred{/owner}{/repo}","subscriptions_url":"https://api.github.com/users/joomla/subscriptions","organizations_url":"https://api.github.com/users/joomla/orgs","repos_url":"https://api.github.com/users/joomla/repos","events_url":"https://api.github.com/users/joomla/events{/privacy}","received_events_url":"https://api.github.com/users/joomla/received_events","type":"Organization","site_admin":false},"private":false,"html_url":"https://github.com/joomla/joomla-cms","description":"Home of the Joomla! Content Management System","fork":false,"url":"https://api.github.com/repos/joomla/joomla-cms","forks_url":"https://api.github.com/repos/joomla/joomla-cms/forks","keys_url":"https://api.github.com/repos/joomla/joomla-cms/keys{/key_id}","collaborators_url":"https://api.github.com/repos/joomla/joomla-cms/collaborators{/collaborator}","teams_url":"https://api.github.com/repos/joomla/joomla-cms/teams","hooks_url":"https://api.github.com/repos/joomla/joomla-cms/hooks","issue_events_url":"https://api.github.com/repos/joomla/joomla-cms/issues/events{/number}","events_url":"https://api.github.com/repos/joomla/joomla-cms/events","assignees_url":"https://api.github.com/repos/joomla/joomla-cms/assignees{/user}","branches_url":"https://api.github.com/repos/joomla/joomla-cms/branches{/branch}","tags_url":"https://api.github.com/repos/joomla/joomla-cms/tags","blobs_url":"https://api.github.com/repos/joomla/joomla-cms/git/blobs{/sha}","git_tags_url":"https://api.github.com/repos/joomla/joomla-cms/git/tags{/sha}","git_refs_url":"https://api.github.com/repos/joomla/joomla-cms/git/refs{/sha}","trees_url":"https://api.github.com/repos/joomla/joomla-cms/git/trees{/sha}","statuses_url":"https://api.github.com/repos/joomla/joomla-cms/statuses/{sha}","languages_url":"https://api.github.com/repos/joomla/joomla-cms/languages","stargazers_url":"https://api.github.com/repos/joomla/joomla-cms/stargazers","contributors_url":"https://api.github.com/repos/joomla/joomla-cms/contributors","subscribers_url":"https://api.github.com/repos/joomla/joomla-cms/subscribers","subscription_url":"https://api.github.com/repos/joomla/joomla-cms/subscription","commits_url":"https://api.github.com/repos/joomla/joomla-cms/commits{/sha}","git_commits_url":"https://api.github.com/repos/joomla/joomla-cms/git/commits{/sha}","comments_url":"https://api.github.com/repos/joomla/joomla-cms/comments{/number}","issue_comment_url":"https://api.github.com/repos/joomla/joomla-cms/issues/comments/{number}","contents_url":"https://api.github.com/repos/joomla/joomla-cms/contents/{+path}","compare_url":"https://api.github.com/repos/joomla/joomla-cms/compare/{base}...{head}","merges_url":"https://api.github.com/repos/joomla/joomla-cms/merges","archive_url":"https://api.github.com/repos/joomla/joomla-cms/{archive_format}{/ref}","downloads_url":"https://api.github.com/repos/joomla/joomla-cms/downloads","issues_url":"https://api.github.com/repos/joomla/joomla-cms/issues{/number}","pulls_url":"https://api.github.com/repos/joomla/joomla-cms/pulls{/number}","milestones_url":"https://api.github.com/repos/joomla/joomla-cms/milestones{/number}","notifications_url":"https://api.github.com/repos/joomla/joomla-cms/notifications{?since,all,participating}","labels_url":"https://api.github.com/repos/joomla/joomla-cms/labels{/name}","releases_url":"https://api.github.com/repos/joomla/joomla-cms/releases{/id}","created_at":"2011-09-27T02:07:38Z","updated_at":"2014-01-18T15:27:07Z","pushed_at":"2014-01-18T15:27:07Z","git_url":"git://github.com/joomla/joomla-cms.git","ssh_url":"git@github.com:joomla/joomla-cms.git","clone_url":"https://github.com/joomla/joomla-cms.git","svn_url":"https://github.com/joomla/joomla-cms","homepage":"http://joomla.org","size":197173,"stargazers_count":1089,"watchers_count":1089,"language":"PHP","has_issues":true,"has_downloads":true,"has_wiki":true,"forks_count":1068,"mirror_url":null,"open_issues_count":535,"forks":1068,"open_issues":535,"watchers":1089,"default_branch":"staging","master_branch":"staging"},"sender":{"login":"chrisdavenport","id":305153,"avatar_url":"https://gravatar.com/avatar/80cfa1e2542242b9be57dc0def07d381?d=https%3A%2F%2Fidenticons.github.com%2F73008553ed8e7d519b07642e43ec9d42.png&r=x","gravatar_id":"80cfa1e2542242b9be57dc0def07d381","url":"https://api.github.com/users/chrisdavenport","html_url":"https://github.com/chrisdavenport","followers_url":"https://api.github.com/users/chrisdavenport/followers","following_url":"https://api.github.com/users/chrisdavenport/following{/other_user}","gists_url":"https://api.github.com/users/chrisdavenport/gists{/gist_id}","starred_url":"https://api.github.com/users/chrisdavenport/starred{/owner}{/repo}","subscriptions_url":"https://api.github.com/users/chrisdavenport/subscriptions","organizations_url":"https://api.github.com/users/chrisdavenport/orgs","repos_url":"https://api.github.com/users/chrisdavenport/repos","events_url":"https://api.github.com/users/chrisdavenport/events{/privacy}","received_events_url":"https://api.github.com/users/chrisdavenport/received_events","type":"User","site_admin":false}}');
			// @codingStandardsIgnoreEnd
		}

		$this->controller->initialize();

		return $this;
	}

	/**
	 * Select the project.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 * @throws  AbortException
	 */
	protected function selectProject()
	{
		/* @type \Joomla\Database\DatabaseDriver $db */
		$db = $this->getContainer()->get('db');

		$projects = $db->setQuery(
			$db->getQuery(true)
				->from($db->quoteName('#__tracker_projects'))
				->select(array('project_id', 'title', 'gh_user', 'gh_project'))

		)->loadObjectList();
/*
		$projectsModel = new ProjectsModel($this->getContainer()->get('db'), $this->getApplication()->input);
		$user = new GitHubUser($this->getApplication()->getp);
		$projects = with()->getItems();
*/
		$id = $this->getApplication()->input->getInt('project', $this->getApplication()->input->getInt('p'));

		if (!$id)
		{
			$this->out()
				->out('<b>Available projects:</b>')
				->out();

			$cnt = 1;

			$checks = array();

			foreach ($projects as $project)
			{
				if ($project->gh_user && $project->gh_project)
				{
					$this->out('  <b>' . $cnt . '</b> (id: ' . $project->project_id . ') ' . $project->title);
					$checks[$cnt] = $project;
					$cnt++;
				}
			}

			$this->out()
				->out('<question>Select a project:</question> ', false);

			$resp = (int) trim($this->getApplication()->in());

			if (!$resp)
			{
				throw new AbortException('Aborted');
			}

			if (false == array_key_exists($resp, $checks))
			{
				throw new AbortException('Invalid project');
			}

			$this->project = $checks[$resp];
		}
		else
		{
			foreach ($projects as $project)
			{
				if ($project->project_id == $id)
				{
					$this->project = $project;

					break;
				}
			}

			if (is_null($this->project))
			{
				throw new AbortException('Invalid project');
			}
		}

		$this->logOut('Processing project: <info>' . $this->project->title . '</info>');

		return $this;
	}

	/**
	 * Setup the Github object.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 * @throws  \RuntimeException
	 */
	protected function setupGitHub()
	{
		$this->github = $this->container->get('gitHub');

		return $this;
	}
}
