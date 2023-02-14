<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

use Exception;
use yii\console\Request as ConsoleRequest;
use yii\httpclient\Request as ClientRequest;
use yii\web\Request;

/**
 * Class DefaultRequestDataFormatter
 *
 * @package app\components\opentracing
 */
class DefaultRequestDataFormatter implements RequestDataFormatterInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function format(mixed $request): array
	{
		$array = [];

		if ($request instanceof ConsoleRequest) {
			/** @noinspection NestedAssignmentsUsageInspection никакого криминала здесь быть не может. */
			$route = $params = $scriptFile = null;

			try {
				[$route, $params] = $request->resolve();

				$scriptFile = $request->getScriptFile();
			} catch (Exception) {
			}

			$array = ['script.file' => $scriptFile, 'script.route' => $route, 'script.params' => $params, 'direction' => 'script'];
			//nothing more to log here yet
		} elseif ($request instanceof Request) {
			$array = [
				'req.method' => $request->method,
				'req.remote_ip' => $request->remoteIP,
				'req.host' => $request->hostInfo,
				'req.remote_host' => $request->remoteHost,
				'req.remote_port' => $_SERVER['REMOTE_PORT'] ?? null,
				'req.url' => $request->absoluteUrl,
				'req.path' => $request->pathInfo,
				'req.referer' => $request->referrer,
				'req.body' => $request->rawBody,
				'req.size' => mb_strlen($request->rawBody ?? '', '8bit'),
				'req.headers' => $this->filterHeaders($request->getHeaders()->toArray()),
				'direction' => 'in'
			];
		} elseif ($request instanceof ClientRequest) {
			//Необходимо подготовить запрос, чтобы собрать content в соответствии с заданным форматом.
			$request->prepare();

			$array = [
				'req.method' => $request->method,
				'req.host' => parse_url($request->fullUrl, PHP_URL_HOST),
				'req.url' => $request->fullUrl,
				'req.path' => $request->url,
				'req.body' => $request->content,
				'req.size' => mb_strlen($request->content ?: '', '8bit'),
				'req.headers' => $this->filterHeaders($request->getHeaders()->toArray()),
				'req.options' => $request->options,
				'direction' => 'out'//Запрос исходящий, т.к. yii\httpclient\Request используется для взаимодействия с внешними системами.
			];
		}

		$array['env'] = YII_ENV;

		return $array;
	}

	/**
	 * @param array $headers
	 * @return array
	 */
	private function filterHeaders(array $headers): array
	{
		unset($headers['authorization']);

		return array_map(static fn($item) => (is_array($item) && 1 === count($item)) ? $item[0] : $item, $headers);
	}
}
