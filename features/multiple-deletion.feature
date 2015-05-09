Feature: Delete large amount of records from database
  As a database request to group of entity records
  I need to be able to remove large amount of entities
  specifying them in a loop and only then deleting

  Scenario: Delete
    Given I have database "driver" "name" "user" "password" "host" "port"
    When I call "connect"
    Then Database resource should be created