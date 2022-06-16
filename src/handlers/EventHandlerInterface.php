<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers;

use cusodede\opentracing\OTTracer;

/**
 * Interface EventHandlerInterface
 */
interface EventHandlerInterface {

	/**
	 * @param OTTracer $tracer
	 * @return void
	 */
	public function attach(OTTracer $tracer):void;
}