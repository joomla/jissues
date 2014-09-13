Feature: JTracker.User-Menu Feature
  In order to use the application
  As a user
  I need to login and see a different top menu

  Scenario: Dummy login User
    When I dummy-login as "user"
    Then I should see "Hello Mr. Dummy \"user\""

  Scenario: Check the top menu contains an entry for creating a new item
    When I dummy-login as "user"
    Then I should see "New Item"

