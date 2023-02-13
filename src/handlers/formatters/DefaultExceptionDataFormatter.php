<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

use Throwable;
use yii\log\Logger;
use yii\web\HttpException;

/**
 * Class DefaultExceptionDataFormatter
 *
 * @package app\components\opentracing
 */
class DefaultExceptionDataFormatter implements ExceptionDataFormatter
{
	/**
	 * {@inheritDoc}
	 */
	public function format(Throwable $e, ?int $exceptionId = null): array
	{
		if ($e instanceof HttpException && $e->statusCode >= 500) {
			$level = Logger::LEVEL_ERROR;
		} else {
			$level = Logger::LEVEL_WARNING;
		}

		return ['level' => Logger::getLevelName($level), 'exception' => $exceptionId ?? $e->__toString()];
	}
}
