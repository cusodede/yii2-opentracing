<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

/**
 * Class DefaultDataFormattersFactory
 *
 * @package app\components\opentracing
 */
class DefaultDataFormattersFactory implements DataFormattersFactory
{
	/**
	 * {@inheritDoc}
	 */
	public function getRequestDataFormatter(): RequestDataFormatter
	{
		return new DefaultRequestDataFormatter();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getResponseDataFormatter(): ResponseDataFormatter
	{
		return new DefaultResponseDataFormatter();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExceptionDataFormatter(): ExceptionDataFormatter
	{
		return new DefaultExceptionDataFormatter();
	}
}
