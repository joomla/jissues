Feature: JTracker.Project-List-Menu Feature
  In order to use the application
  As a user
  I need to login and see a different top menu

  Scenario: Change a project - check short title usage
    Given I am on "/"
    And I follow "Projects"
    Then I should see "Joomla! CMS"
    And I should see "J!Tracker"
    When I follow "J!Tracker"
    Then I should see "Joomla! Issue Tracker - J!Tracker"
    When I follow "Projects"
    And I follow "Joomla! CMS"
    Then I should see "Joomla! Issue Tracker - CMS"
