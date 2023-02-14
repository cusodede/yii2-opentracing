<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

use Throwable;

/**
 * интерфейс форматтеров логирования данных для исключений
 */
interface ExceptionDataFormatterInterface
{
	/**
	 * @param Throwable $e
	 * @param int|null $exceptionId
	 * @return array
	 */
	public function format(Throwable $e, ?int $exceptionId = null): array;
}
