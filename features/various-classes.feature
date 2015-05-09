Feature: Return array of different object classes as query result
  As a database request to group of entity record
  I need to be able to get array of object with different classes

  Scenario: Get different object collection
    Given I have database "driver" "name" "user" "password" "host" "port"
    When I call "connect"
    Then Database resource should be created