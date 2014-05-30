Feature: JTracker.Admin-Menu Feature
  In order to use the application
  As an admin
  I need to login and see a different top menu

  Scenario: Dummy login as Admin
    When I dummy-login as "admin"
    Then I should see "Hello Mr. Dummy \"admin\""

  Scenario: Check the top menu "Configuration"
    When I dummy-login as "admin"
    And I follow "Configuration"
    Then I should see "Edit Configuration"
