<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

use Throwable;

interface ExceptionDataFormatter
{
	/**
	 * @param Throwable $e
	 * @param int|null $exceptionId
	 * @return array
	 */
	public function format(Throwable $e, ?int $exceptionId = null): array;
}
