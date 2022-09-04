<?php
declare(strict_types = 1);

namespace cusodede\opentracing;

use Yii;
use yii\web\HttpException;
use yii\web\Request;
use yii\web\Response;
use yii\httpclient\Request as ClientRequest;
use yii\httpclient\Response as ClientResponse;
use yii\console\Request as ConsoleRequest;
use yii\console\Response as ConsoleResponse;
use yii\log\Logger;
use Exception;
use Throwable;

/**
 * Class OpenTracingLogDataHandler
 */
class OpenTracingLogDataHandler {
	/**
	 * @param mixed $request
	 * @return array
	 */
	public static function transformRequestParams(mixed $request):array {
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
				'req.host' => $request->hostInfo,
				'req.method' => $request->method,
				'req.remote_ip' => $request->remoteIP,
				'req.remote_host' => $request->remoteHost,
				'req.remote_port' => $_SERVER['REMOTE_PORT']??null,
				'req.url' => $request->absoluteUrl,
				'req.path' => $request->pathInfo,
				'req.referer' => $request->referrer,
				'req.size' => mb_strlen($request->rawBody??'', '8bit'),
				'req.headers' => self::filterHeaders($request->getHeaders()->toArray()),
				'req.body' => $request->post(),
				'direction' => 'in'
			];
		} elseif ($request instanceof ClientRequest) {
			//Необходимо подготовить запрос, чтобы собрать content в соответствии с заданным форматом.
			$request->prepare();

			$array = [
				'req.host' => parse_url($request->fullUrl, PHP_URL_HOST),
				'req.method' => $request->method,
				'req.url' => $request->fullUrl,
				'req.path' => $request->url,
				'req.size' => mb_strlen($request->content?:'', '8bit'),
				'req.headers' => self::filterHeaders($request->getHeaders()->toArray()),
				'req.body' => $request->content,
				'req.options' => $request->options,
				'direction' => 'out'//Запрос исходящий, т.к. yii\httpclient\Request используется для взаимодействия с внешними системами.
			];
		}

		$array['env'] = YII_ENV;

		return $array;
	}

	/**
	 * @param mixed $response
	 * @return array
	 */
	public static function transformResponseParams(mixed $response):array {
		$array = [];

		if ($response instanceof ConsoleResponse) {
			$array = ['rsp.exitStatus' => $response->exitStatus];
			//nothing more to log here yet
		} elseif ($response instanceof Response || $response instanceof ClientResponse) {
			$array = [
				'rsp.http_code' => $response->statusCode,
				'rsp.size' => mb_strlen($response->content??'', '8bit'),
				'rsp.headers' => self::filterHeaders($response->getHeaders()->toArray()),
				'rsp.body' => Response::FORMAT_HTML === $response->format?null:$response->content
			];
			/**
			 * It's necessary to check if session hasn't finished at this moment, because Yii tries to get user id from session at first, and it'll fail
			 * if user isn't logged.
			 */
			if (Yii::$app->has('user') && Yii::$app->session->isActive) {
				$array['user_id'] = Yii::$app->user->id;
			}
		}

		return $array;
	}

	/**
	 * @param Throwable $e
	 * @return array
	 */
	public static function transformException(Throwable $e):array {
		if ($e instanceof HttpException && $e->statusCode >= 500) {
			$level = Logger::LEVEL_ERROR;
		} else {
			$level = Logger::LEVEL_WARNING;
		}

		return ['level' => Logger::getLevelName($level), 'exception' => $e->__toString()];
	}

	/**
	 * @param array $headers
	 * @return array
	 */
	private static function filterHeaders(array $headers):array {
		unset($headers['authorization']);

		return array_map(static fn($item) => (is_array($item) && 1 === count($item))?$item[0]:$item, $headers);
	}
}