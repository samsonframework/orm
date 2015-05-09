Feature: connection
  In order to work with a database
  As PHP program
  I need to be able to connect to it

  Scenario: Connection to a database
    Given I have database "driver" "name" "user" "password" "host" "port"
    When I call "connect"
    Then Database resource should be created