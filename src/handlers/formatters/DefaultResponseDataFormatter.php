<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

/**
 * Class DefaultResponseDataFormatter
 *
 * @package app\components\opentracing
 */
class DefaultResponseDataFormatter implements ResponseDataFormatterInterface {
	/**
	 * {@inheritDoc}
	 */
	public function format(mixed $response):array {
		return [];
	}
}
