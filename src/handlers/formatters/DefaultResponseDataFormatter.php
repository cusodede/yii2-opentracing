<?php
declare(strict_types = 1);

namespace cusodede\opentracing\handlers\formatters;

use Yii;
use yii\console\Response as ConsoleResponse;
use yii\httpclient\Response as ClientResponse;
use yii\web\Response;

/**
 * Class DefaultResponseDataFormatter
 *
 * @package app\components\opentracing
 */
class DefaultResponseDataFormatter implements ResponseDataFormatterInterface
{
	/**
	 * response's max body size (in bytes)
	 */
	public const MAX_BODY_SIZE = 8192;

	/**
	 * {@inheritDoc}
	 */
	public function format(mixed $response): array
	{
		$array = [];

		if ($response instanceof ConsoleResponse) {
			$array = ['rsp.exitStatus' => $response->exitStatus];
			//nothing more to log here yet
		} elseif ($response instanceof Response || $response instanceof ClientResponse) {
			$array = [
				'rsp.http_code' => $response->statusCode,
				'rsp.body' => Response::FORMAT_HTML === $response->format ? null : mb_strcut((string)$response->content, 0, self::MAX_BODY_SIZE),
				'rsp.size' => mb_strlen($response->content ?? '', '8bit'),
				'rsp.headers' => $this->filterHeaders($response->getHeaders()->toArray())
			];

			if (Yii::$app->has('user')) {
				$array['user_id'] = Yii::$app->user->id;
			}
		}

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
