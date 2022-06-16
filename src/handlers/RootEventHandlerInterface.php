<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers;

use cusodede\opentracing\OpenTracingComponent;

/**
 * Interface RootEventHandlerInterface
 */
interface RootEventHandlerInterface {
	/**
	 * @param OpenTracingComponent $tracingComponent
	 * @return void
	 */
	public function attach(OpenTracingComponent $tracingComponent):void;
}