#!/usr/bin/env fish

# Part of the Joomla! Tracker application.
# @copyright  Copyright (C) 2016 Open Source Matters, Inc. All rights reserved.
# @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later

function __fish_jtracker_needs_command
  set cmd (commandline -opc)
  if [ (count $cmd) -eq 1 -a $cmd[1] = 'jtracker' ]
    return 0
  end
  return 1
end

function __fish_jtracker_using_command
  set cmd (commandline -opc)
  if [ (count $cmd) -gt 1 ]
    if [ $argv[1] = $cmd[2] ]
      return 0
    end
  end
  return 1
end

function __fish_jtracker_using_action
  set cmd (commandline -opc)
  if [ (count $cmd) -gt 2 ]
    if [ $argv[1] = $cmd[2] -a $argv[2] = $cmd[3] ]
      	return 0
    end
  end
  return 1
end

# jtracker help
complete -f -c jtracker -n '__fish_jtracker_using_command' -a help -d "Show the help for a command"
# jtracker install
complete -f -c jtracker -n '__fish_jtracker_using_command' -a install -d "Install the application."
complete -f -c jtracker -n '__fish_jtracker_using_action install' -l reinstall -d "Reinstall the application (without confirmation)."
# jtracker list
complete -f -c jtracker -n '__fish_jtracker_using_command' -a list -d "List the application's available commands"

# jtracker clear
complete -f -c jtracker -n '__fish_jtracker_using_command list' -a clear
complete -f -c jtracker -n '__fish_jtracker_using_command' -a clear:allcache -d "Clear all cache stores."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a clear:cache -d "Clear the application cache."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a clear:twig -d "Clear the Twig cache."

# jtracker database
complete -f -c jtracker -n '__fish_jtracker_using_command list' -a database
complete -f -c jtracker -n '__fish_jtracker_using_command' -a database:migrate -d "Migrate the database schema to a newer version."
complete -f -c jtracker -n '__fish_jtracker_using_action database:migrate' -l db_version -d "Apply a specific database version."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a database:status -d "Check the database migration status."

# jtracker get
complete -f -c jtracker -n '__fish_jtracker_using_command list' -a get
complete -f -c jtracker -n '__fish_jtracker_using_command' -a get:avatars -d "Retrieve avatar images from GitHub."
complete -f -c jtracker -n '__fish_jtracker_using_action get:avatars' -s p -l project -d "Process the project with the given ID."
complete -f -c jtracker -n '__fish_jtracker_using_action get:avatars' -l noprogress -d "Don't use a progress bar."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a get:composertags -d "Retrieve a list of project tags from GitHub and show their installed versions."
complete -f -c jtracker -n '__fish_jtracker_using_action get:composertags' -s p -l project -d "Process the project with the given ID."
complete -f -c jtracker -n '__fish_jtracker_using_action get:composertags' -l all -d "Show all tags or only the most recent."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a get:project -d "Get the whole project info from GitHub, including issues and issue comments."
complete -f -c jtracker -n '__fish_jtracker_using_action get:project' -l all -d "Process all issues."
complete -f -c jtracker -n '__fish_jtracker_using_action get:project' -l issue -d "Process only a single issue."
complete -f -c jtracker -n '__fish_jtracker_using_action get:project' -l range_from -d "First issue to process."
complete -f -c jtracker -n '__fish_jtracker_using_action get:project' -l range_to -d "Last issue to process."
complete -f -c jtracker -n '__fish_jtracker_using_action get:project' -s f -l force -d "Force an update even if the issue has not changed."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a get:users -d "Retrieve user info from GitHub."
complete -f -c jtracker -n '__fish_jtracker_using_action get:users' -s p -l project -d "Process the project with the given ID."
complete -f -c jtracker -n '__fish_jtracker_using_action get:users' -l noprogress -d "Don't use a progress bar."

# jtracker make
complete -f -c jtracker -n '__fish_jtracker_using_command list' -a make
complete -f -c jtracker -n '__fish_jtracker_using_command' -a make:autocomplete -d "Generate autocomplete files."
complete -f -c jtracker -n '__fish_jtracker_using_action make:autocomplete' -s t -l type -d "The type of auto complete file (currently supported: 'phpstorm' 'fish')."
complete -f -c jtracker -n '__fish_jtracker_using_action make:autocomplete' -s e -l echo -d "Echo the output instead of writing it to a file."
complete -f -c jtracker -n '__fish_jtracker_using_action make:autocomplete' -s h -l help -d "Display the help information"
complete -f -c jtracker -n '__fish_jtracker_using_action make:autocomplete' -s q -l quiet -d "Flag indicating that all output should be silenced"
complete -f -c jtracker -n '__fish_jtracker_using_action make:autocomplete' -s v|vv|vvv -l verbose -d "Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug"
complete -f -c jtracker -n '__fish_jtracker_using_action make:autocomplete' -s V -l version -d "Displays the application version"
complete -f -c jtracker -n '__fish_jtracker_using_action make:autocomplete' -l ansi -d "Force ANSI output"
complete -f -c jtracker -n '__fish_jtracker_using_action make:autocomplete' -l no-ansi -d "Disable ANSI output"
complete -f -c jtracker -n '__fish_jtracker_using_action make:autocomplete' -s n -l no-interaction -d "Flag to disable interacting with the user"
complete -f -c jtracker -n '__fish_jtracker_using_command' -a make:composergraph -d "Graph visualisation for your project's composer.json and its dependencies."
complete -f -c jtracker -n '__fish_jtracker_using_action make:composergraph' -s f -l file -d "Write output to a file."
complete -f -c jtracker -n '__fish_jtracker_using_action make:composergraph' -l format -d "The image type."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a make:dbcomments -d "Generate class doc blocks for Table classes."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a make:depfile -d "Create and update a dependency file."
complete -f -c jtracker -n '__fish_jtracker_using_action make:depfile' -s f -l file -d "Write output to a file."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a make:docu -d "Compile documentation using GitHub Flavored Markdown."
complete -f -c jtracker -n '__fish_jtracker_using_action make:docu' -l noprogress -d "Don't use a progress bar."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a make:repoinfo -d "Generate repository information."

# jtracker test
complete -f -c jtracker -n '__fish_jtracker_using_command list' -a test
complete -f -c jtracker -n '__fish_jtracker_using_command' -a test:checkstyle -d "Run PHP CodeSniffer tests."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a test:hook -d "Tests web hooks."
complete -f -c jtracker -n '__fish_jtracker_using_action test:hook' -s p -l project -d "Process the project with the given ID."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a test:phpunit -d "Run PHPUnit tests."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a test:run -d "Run all tests."

# jtracker update
complete -f -c jtracker -n '__fish_jtracker_using_command list' -a update
complete -f -c jtracker -n '__fish_jtracker_using_command' -a update:pulls -d "Updates selected information for pull requests on GitHub for a specified project."
complete -f -c jtracker -n '__fish_jtracker_using_action update:pulls' -s p -l project -d "Process the project with the given ID."
complete -f -c jtracker -n '__fish_jtracker_using_command' -a update:server -d "Updates the local installation to either a specified version or latest git HEAD for the active branch."
complete -f -c jtracker -n '__fish_jtracker_using_action update:server' -l app_version -d "An optional version number to update to."
