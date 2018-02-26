<?php

/**
 * Base spec for all application specs
 *
 * @author    Andrew Coulton <andrew@ingenerator.com>
 * @copyright 2014 inGenerator Ltd
 * @licence   BSD
 */

namespace spec;

use PhpSpec\Exception\Example\FailureException;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory;

abstract class ObjectBehavior extends \PhpSpec\ObjectBehavior
{

    /**
     * Use the subject property instead of $this for calls to the class under spec so that method and argument
     * completion works as expected and to allow use of refactoring tools, "Find Usages" etc.
     *
     * For example - the raw PHPSpec way is:
     *
     *   $this->method_on_subject()->shouldReturn(FALSE);
     *
     * And our way is:
     *   $this->subject->method_on_subject()->shouldReturn(FALSE);
     *
     * Each spec class should redeclare this field appropriately type-hinted for the subject class. Then only the
     * PHPSpec methods and matchers should be visible as undefined in the IDE - subject methods should always complete
     * correctly.
     *
     * @var object
     */
    protected $subject;

    /**
     * Create an instance and map $this to the subject field
     */
    public function __construct()
    {
        $this->subject = $this;
    }

    public function expectEquals($expect, $actual)
    {
        try {
            Factory::getInstance()
                ->getComparatorFor($expect, $actual)
                ->assertEquals($expect, $actual);
        } catch (ComparisonFailure $e) {
            throw new FailureException($e->getMessage()."\n".$e->getDiff());
        }
    }

}
