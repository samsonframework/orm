Feature: query
  In order to retrieve data from a database
  As
  I need to be able to query it

  Scenario: Retrieve all records from database table by a query
    Given I have table named "material"
    And I have table column named "name"
    And I have table column named "url"
    When I call "exec"
    Then I should get:
    """
    something
    """