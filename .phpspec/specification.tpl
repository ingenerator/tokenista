<?php
/**
 * Defines %name% - specifications for %subject%
 *
 * @author     Andrew Coulton <andrew@ingenerator.com>
 * @copyright  2014, inGenerator Ltd
 * @licence    proprietary
 */

namespace %namespace%;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 *
 * @see %subject%
 */
class %name% extends ObjectBehavior
{
    /**
     * Use $this->subject to get proper type hinting for the subject class
     * @var \%subject%
     */
	protected $subject;

	function it_is_initializable()
    {
		$this->subject->shouldHaveType('%subject%');
	}
}
