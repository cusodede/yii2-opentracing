<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

interface ResponseDataFormatter
{
	/**
	 * @param mixed $response
	 * @return array
	 */
	public function format(mixed $response): array;
}
