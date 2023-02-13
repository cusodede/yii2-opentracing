<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

/**
 * Class DataFormattersFactory
 *
 * @package app\components\opentracing
 */
interface DataFormattersFactory
{
	/**
	 * @return RequestDataFormatter
	 */
	public function getRequestDataFormatter(): RequestDataFormatter;

	/**
	 * @return ResponseDataFormatter
	 */
	public function getResponseDataFormatter(): ResponseDataFormatter;

	/**
	 * @return ExceptionDataFormatter
	 */
	public function getExceptionDataFormatter(): ExceptionDataFormatter;
}
