<?php

/**
 * Part of the Joomla Tracker Authentication Package
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace JTracker\Authentication;

use App\Projects\ProjectAwareTrait;
use App\Projects\TrackerProject;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use JTracker\Authentication\Database\TableUsers;
use JTracker\Authentication\Exception\AuthenticationException;

/**
 * Abstract class containing the application user object
 *
 * @since  1.0
 */
abstract class User implements \Serializable
{
    use ProjectAwareTrait;

    /**
     * Id.
     *
     * @var    integer
     * @since  1.0
     */
    public $id = 0;

    /**
     * User name.
     *
     * @var    string
     * @since  1.0
     */
    public $username = '';

    /**
     * Name.
     *
     * @var    string
     * @since  1.0
     */
    public $name = '';

    /**
     * E-mail.
     *
     * @var    string
     * @since  1.0
     */
    public $email = '';

    /**
     * Register date.
     *
     * @var    string
     * @since  1.0
     */
    public $registerDate = '';

    /**
     * If a user has special "admin" rights.
     *
     * @var    boolean
     * @since  1.0
     */
    public $isAdmin = false;

    /**
     * User parameters.
     *
     * @var    Registry
     * @since  1.0
     */
    public $params;

    /**
     * A list of groups a user has access to.
     *
     * @var    array
     * @since  1.0
     */
    protected $accessGroups = [];

    /**
     * A list of cleared access rights.
     *
     * @var    array
     * @since  1.0
     */
    private $cleared = [];

    /**
     * Database object
     *
     * @var    DatabaseDriver
     * @since  1.0
     */
    protected $database;

    /**
     * Constructor.
     *
     * @param   TrackerProject  $project     The tracker project.
     * @param   DatabaseDriver  $database    The database connector.
     * @param   integer         $identifier  The primary key of the user to load..
     *
     * @since   1.0
     */
    public function __construct(TrackerProject $project, DatabaseDriver $database, $identifier = 0)
    {
        $this->setDatabase($database);
        $this->setProject($project);

        // Create the user parameters object.
        $this->params = new Registry();

        // Load the user if it exists
        if ($identifier) {
            $this->load($identifier);
        }
    }

    /**
     * Load a user with the given keys
     *
     * @param   array  $lookup  Keys to lookup user by
     *
     * @return  $this
     *
     * @since   1.0
     */
    public function loadBy(array $lookup)
    {
        return $this->load($lookup);
    }

    /**
     * Load data by a given user name.
     *
     * @param   string  $userName  The user name
     *
     * @return  $this
     *
     * @since   1.0
     */
    public function loadByUserName($userName)
    {
        $db = $this->database;

        $table = (new TableUsers($db))
            ->loadByUserName($userName);

        if (!$table->id) {
            // Register a new user
            $this->registerDate = (new \DateTime())->format($db->getDateFormat());

            $table->save($this);
        }

        $this->id = $table->id;
        $this->params->loadString($table->params);

        $this->loadAccessGroups();

        return $this;
    }

    /**
     * Get available access groups.
     *
     * @return  array
     *
     * @since   1.0
     */
    public function getAccessGroups()
    {
        return $this->accessGroups;
    }

    /**
     * Method to load a User object by user id number.
     *
     * @param   mixed  $identifier  The user id of the user to load.
     *
     * @return  $this  Method allows chaining
     *
     * @since   1.0
     * @throws  \RuntimeException
     */
    protected function load($identifier)
    {
        // Load the User object based on the user id or throw a warning.
        try {
            $table = (new TableUsers($this->database))
                ->load($identifier);
        } catch (\Exception $e) {
            throw new \RuntimeException('Unable to load the user with id: ' . $identifier, $e->getCode(), $e);
        }

        // Assuming all is well at this point let's bind the data
        foreach ($table->getFields() as $key => $value) {
            if (isset($this->$key) && $key != 'params') {
                $this->$key = $table->$key;
            }
        }

        $this->params->loadString($table->params);

        $this->loadAccessGroups();

        return $this;
    }

    /**
     * Load the access groups.
     *
     * @return  $this  Method allows chaining
     *
     * @since   1.0
     */
    protected function loadAccessGroups()
    {
        $db = $this->database;

        $this->accessGroups = $db->setQuery(
            $db->getQuery(true)
                ->from($db->quoteName('#__user_accessgroup_map'))
                ->select($db->quoteName('group_id'))
                ->where($db->quoteName('user_id') . '=' . (int) $this->id)
        )->loadColumn();

        return $this;
    }

    /**
     * Check if a user can edit her own item.
     *
     * @param   string  $username  The user name of the "owner" of the item to edit.
     *
     * @return  boolean
     *
     * @since   1.0
     */
    public function canEditOwn($username)
    {
        return $this->check('editown') && $this->username == $username;
    }

    /**
     * Check if a user is authorized to perform a given action.
     *
     * @param   string  $action  The action to check.
     *
     * @return  boolean
     *
     * @since   1.0
     */
    public function check($action)
    {
        if (\array_key_exists($action, $this->cleared)) {
            return $this->cleared[$action] ? true : false;
        }

        try {
            $this->authorize($action);

            return true;
        } catch (AuthenticationException $e) {
            return false;
        }
    }

    /**
     * Check if the user is authorized to perform a given action.
     *
     * @param   string  $action  The action.
     *
     * @return  $this  Method allows chaining
     *
     * @since   1.0
     * @throws  \InvalidArgumentException
     * @throws  AuthenticationException
     */
    public function authorize($action)
    {
        if (\array_key_exists($action, $this->cleared)) {
            if ($this->cleared[$action] == 0) {
                throw new AuthenticationException($this, $action);
            }

            return $this;
        }

        if ($this->isAdmin) {
            // "Admin users" are granted all permissions - globally.
            return $this;
        }

        if ($action == 'admin') {
            // "Admin action" requested for non "Admin user".
            $this->cleared[$action] = 0;

            throw new AuthenticationException($this, $action);
        }

        if (\in_array($action, $this->getProject()->getDefaultActions()) === false) {
            throw new \InvalidArgumentException('Undefined action: ' . $action);
        }

        $project = $this->getProject();

        if ($project->getAccessGroups($action, 'Public')) {
            // Project has public access for the action.
            $this->cleared[$action] = 1;

            return $this;
        }

        if ($this->id) {
            if ($project->getAccessGroups($action, 'User')) {
                // Project has User access for the action.
                $this->cleared[$action] = 1;

                return $this;
            }

            // Check if a User has access to a custom group
            $groups = $project->getAccessGroups($action);

            foreach ($groups as $group) {
                if (\in_array($group, $this->accessGroups)) {
                    // The User is member of the group.
                    $this->cleared[$action] = 1;

                    return $this;
                }
            }
        }

        $this->cleared[$action] = 0;

        throw new AuthenticationException($this, $action);
    }

    /**
     * Serialize the object
     *
     * @return  string  The string representation of the object or null
     *
     * @since   1.0
     */
    public function serialize(): ?string
    {
        return serialize(
            [
                $this->id,
                $this->username,
                $this->project,
            ]
        );
    }

    /**
     * Unserialize the object
     *
     * @param   string  $data  The serialized string
     *
     * @return  void
     *
     * @since   1.0
     */
    public function unserialize($data): void
    {
        list($this->id, $this->username, $this->project) = unserialize($data);

        // Initialize the user parameters object.
        $this->params = new Registry();
    }

    /**
     * Serialize the object
     *
     * @return  array
     *
     * @since   2.0
     */
    public function __serialize(): array
    {
        return [
            'id'       => $this->id,
            'username' => $this->username,
            'project'  => $this->project,
        ];
    }

    /**
     * Unserialize the object
     *
     * @param   array  $data  The serialized data
     *
     * @return  void
     *
     * @since   2.0
     */
    public function __unserialize(array $data): void
    {
        $this->id       = $data['id'];
        $this->username = $data['username'];
        $this->project  = $data['project'];

        $this->params = new Registry();
    }

    /**
     * Method to set the database connector.
     *
     * @param   DatabaseDriver  $database  The Database connector.
     *
     * @return  $this
     *
     * @since   1.0
     */
    public function setDatabase(DatabaseDriver $database)
    {
        $this->database = $database;

        return $this;
    }
}
