Feature: JTracker.Guest-Menu Feature
  In order to use the application
  As a guest
  I shall see the standard top menu

  Scenario: Check the top menu "Projects"
    Given I am on "/"
    And I follow "Projects"
    Then I should see "Projects List"

  Scenario: Check the top menu "Users"
    Given I am on "/"
    And I follow "Users"
    Then I should see "Users List"

  Scenario: Check the top menu "About us"
    Given I am on "/"
    And I follow "/etc"
    And I follow "About Us"
    Then I should see "Some info about the project here... @todo add more"

  Scenario: Check the top menu "Markdown page Joomla! Logo"
    Given I am on "/"
    And I follow "/etc"
    And I follow "Markdown"
    And I follow "Images"
    Then I should see "http://www.joomla.org/images/joomla_logo_black.jpg"
