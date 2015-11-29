Feature: query
  As a php script
  I need to be able to retrieve data from database with high level queries

  Scenario: Retrieve all records from queried records from database table
    Given I have table named "material"
    And I have table column named "name"
    And I have table column named "url"
    When I call "exec"
    Then I should get:
    """
    something
    """

  Scenario: Retrieve first record from queried records from database table
    Given I have table named "material"
    And I have table column named "name"
    And I have table column named "url"
    When I call "first"
    Then I should get:
    """
    something
    """

  Scenario: Retrieve collection of field values of queried records from database table
    Given I have table named "material"
    And I have table column named "name"
    And I have table column named "url"
    When I call "fields"
    Then I should get:
    """
    something
    """

  Scenario: Retrieve amount of records in queried records from database table
    Given I have table named "material"
    And I have table column named "name"
    And I have table column named "url"
    When I call "exec"
    Then I should get:
    """
    something
    """

  Scenario: Add condition to a query
    Given I have table named "material"
    And I have table column named "name"
    And I have table column named "url"
    When I call "condition" or "cond"
    Then I should get:
    """
    something
    """

  Scenario: Add grouping to a query
    Given I have table named "material"
    And I have table column named "name"
    And I have table column named "url"
    When I call "group" or "group_by"
    Then I should get:
    """
    something
    """

  Scenario: Add limits to a query
    Given I have table named "material"
    And I have table column named "name"
    And I have table column named "url"
    When I call "limit"
    Then I should get:
    """
    something
    """

  Scenario: Add sorting to a query
    Given I have table named "material"
    And I have table column named "name"
    And I have table column named "url"
    When I call "order" or "order_by"
    Then I should get:
    """
    something
    """

  Scenario: Join other tables in a query
    Given I have table named "material"
    And I have table column named "name"
    And I have table column named "url"
    When I call "join"
    Then I should get:
    """
    something
    """

