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
