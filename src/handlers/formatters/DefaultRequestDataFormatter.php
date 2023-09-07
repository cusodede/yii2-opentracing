<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

use yii\console\Request as ConsoleRequest;
use yii\httpclient\Request as ClientRequest;
use yii\web\Request;

/**
 * Class DefaultRequestDataFormatter
 *
 * @package app\components\opentracing
 */
class DefaultRequestDataFormatter implements RequestDataFormatterInterface {
	/**
	 * {@inheritDoc}
	 */
	public function format(mixed $request):array {
		$array = [];

		if ($request instanceof ConsoleRequest) {
			$array = [
				'url' => implode(' ', array_merge((array)$request->scriptFile, $request->params)),
				'direction' => 'script',
			];
		} elseif ($request instanceof Request) {
			$array = [
				'url' => $request->absoluteUrl,
				'direction' => 'in',
			];
		} elseif ($request instanceof ClientRequest) {
			$array = [
				'url' => $request->fullUrl,
				'direction' => 'out',
			];
		}

		return $array;
	}
}
