<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

interface RequestDataFormatter
{
	/**
	 * @param mixed $request
	 * @return array
	 */
	public function format(mixed $request): array;
}
