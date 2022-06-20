<?php
declare(strict_types = 1);

namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I
use Codeception\Module;
use Exception;

/**
 * Class Functional
 */
class Functional extends Module {

	/**
	 * @return string
	 * @throws Exception
	 */
	public static function gen_trace_header():string {
		return sprintf("00-%016x-%08x-00", random_int(0, 0xfffffffffffffff), random_int(0, 0xffffffff));
	}

}
