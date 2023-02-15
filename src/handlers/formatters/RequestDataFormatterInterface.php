<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

/**
 * интерфейс форматтеров данных для логирования данных запросов в сторонние системы
 */
interface RequestDataFormatterInterface {
	/**
	 * @param mixed $request
	 * @return array
	 */
	public function format(mixed $request):array;
}
