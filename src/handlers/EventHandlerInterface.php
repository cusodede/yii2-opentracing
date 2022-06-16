<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers;

use cusodede\opentracing\OpenTracingComponent;

/**
 * Interface EventHandlerInterface
 */
interface EventHandlerInterface {

	/**
	 * @param OpenTracingComponent $tracingComponent
	 * @return void
	 */
	public function attach(OpenTracingComponent $tracingComponent):void;
}