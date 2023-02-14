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
	 * @return RequestDataFormatterInterface
	 */
	public function getRequestDataFormatter(): RequestDataFormatterInterface;

	/**
	 * @return ResponseDataFormatter
	 */
	public function getResponseDataFormatter(): ResponseDataFormatter;

	/**
	 * @return ExceptionDataFormatterInterface
	 */
	public function getExceptionDataFormatter(): ExceptionDataFormatterInterface;
}
