Feature: JTracker.Guest-Multilang Feature
  In order to use the application
  As a guest
  I shall see the standard top menu

  Scenario: Check the top menu "Users" in "multiple languages"
    Given I am on "/"
    And I follow "Users"
    Then I should see "Users List"
    When I follow "de-DE"
    Then I should see "Benutzerliste"
    When I follow "ru-RU"
    Then I should see "Список пользователей"
