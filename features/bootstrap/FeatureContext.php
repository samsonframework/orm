<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @Given I have table named :arg1
     */
    public function iHaveTableNamed($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Given I have table column named :arg1
     */
    public function iHaveTableColumnNamed($arg1)
    {
        throw new PendingException();
    }

    /**
     * @When I call :arg1
     */
    public function iCall($arg1)
    {
        throw new PendingException();
    }

    /**
     * @Then I should get:
     */
    public function iShouldGet(PyStringNode $string)
    {
        throw new PendingException();
    }

    /**
     * @When I call :arg1 or :arg2
     */
    public function iCallOr($arg1, $arg2)
    {
        throw new PendingException();
    }

}
