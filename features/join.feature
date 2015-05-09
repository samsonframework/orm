Feature: Join tables in database request
  As a database request
  I need to be able to join any amount of tables

  Scenario: Join
    Given I have database "driver" "name" "user" "password" "host" "port"
    When I call "connect"
    Then Database resource should be created