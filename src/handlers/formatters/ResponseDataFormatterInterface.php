<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

/**
 * интерфейс форматтеров данных для логирования данных ответа из сторонних систем
 */
interface ResponseDataFormatterInterface {
	/**
	 * @param mixed $response
	 * @return array
	 */
	public function format(mixed $response):array;
}
