<?php
declare(strict_types = 1);

namespace cusodede\opentracing;

use DateTime;
use OpenTracing\GlobalTracer;
use Yii;
use yii\base\Application as BaseApplication;
use yii\base\Component;
use yii\base\Event;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use yii\httpclient\Client;
use yii\httpclient\RequestEvent;
use yii\log\Logger;
use yii\web\Application as WebApplication;
use yii\web\Response;
use Throwable;
use const OpenTracing\Formats\HTTP_HEADERS;

/**
 * Class OpenTracingComponent
 */
class OpenTracingComponent extends Component {
	/**
	 * @var string
	 */
	public string $traceParentHeaderName = 'traceparent';
	/**
	 * @var array
	 */
	public array $excludedRequestsPaths = [];
	/**
	 * @var OTTracer|null обработчик, отвечающий за управление span'ми.
	 */
	private ?OTTracer $_tracer = null;
	/**
	 * @var OTScope|null базовый (верхний) trace span.
	 */
	private ?OTScope $_rootScope = null;

	/**
	 * {@inheritdoc}
	 */
	public function init():void {
		parent::init();
		if (null !== $requestPath = Yii::$app?->request?->pathInfo) {//Не будем даже инициализировать компонент, если url запроса исключается из логирования.
			foreach ($this->excludedRequestsPaths as $excludedPath) {
				if (StringHelper::matchWildcard($excludedPath, $requestPath)) {
					return;
				}
			}
		}

		register_shutdown_function(function() {
			$this->finish();
		});

		$this->_tracer = new OTTracer();
		$this->_tracer->traceParentHeaderName = $this->traceParentHeaderName;

		GlobalTracer::set($this->_tracer);

		$this->attachEvents();
	}

	/**
	 * Навешиваем обработчики на события уровня приложения.
	 */
	private function attachEvents():void {
		Yii::$app->on(
			BaseApplication::EVENT_BEFORE_REQUEST,
			function() {
				try {
					$spanContext = GlobalTracer::get()->extract(HTTP_HEADERS, getallheaders());
				} catch (Throwable) {
					$spanContext = null;
				}

				$span = $this->_tracer->startSpan('application.request', null !== $spanContext?['child_of' => $spanContext]:[]);
				$span->log(
					OpenTracingLogDataHandler::transformRequestParams(Yii::$app->request)
				);

				$this->_rootScope = $this->_tracer->getScopeManager()->activate($span);
			}
		);

		$responseLogCallback = function() {
			$this->_rootScope->getSpan()->log(
				OpenTracingLogDataHandler::transformResponseParams(Yii::$app->response)
			);

			$exception = ArrayHelper::getValue(Yii::$app->controller->module, 'errorHandler.exception');
			if (null !== $exception) {
				$this->_rootScope->getSpan()->log(
					OpenTracingLogDataHandler::transformException($exception)
				);
			}
		};

		if (Yii::$app instanceof WebApplication) {
			Yii::$app->response->on(Response::EVENT_AFTER_SEND, $responseLogCallback);
		} else {
			//Не уверен до конца, пригодно ли для консольного приложения, но пусть будет.
			Yii::$app->on(BaseApplication::EVENT_AFTER_REQUEST, $responseLogCallback);
		}

		$this->attachHttpClientEvents();
	}

	/**
	 * Навешиваем обработчики на события yii\httpclient\Client - используется как прослойка для подключения к внешним АПИ.
	 */
	private function attachHttpClientEvents():void {
		Event::on(
			Client::class,
			Client::EVENT_BEFORE_SEND,
			function(RequestEvent $e) {
				$activeScope = $this->_tracer->startActiveSpan('client.request');
				$activeScope->getSpan()->log(
					OpenTracingLogDataHandler::transformRequestParams($e->request)
				);

				$this->_tracer->inject($activeScope->getSpan()->getContext(), HTTP_HEADERS, $e->request->headers);
			}
		);

		Event::on(
			Client::class,
			Client::EVENT_AFTER_SEND,
			function(RequestEvent $e) {
				if (null !== $activeScope = $this->_tracer->getScopeManager()->getActive()) {
					$activeScope->getSpan()->log(
						OpenTracingLogDataHandler::transformResponseParams($e->response)
					);
					$activeScope->close();
				}
			}
		);
	}

	/**
	 * Подготавливаем накопившиеся логи и отправляем в логгер.
	 * @param bool $forceFlush `true` - если надо принудительно записать логи в таргеты.
	 * @return void
	 */
	public function finish(bool $forceFlush = false):void {
		$this->_rootScope?->close();

		$logsData = array_map([$this, 'extractSpanData'], $this->_tracer->getSpans());

		$this->_tracer->flush();

		foreach ($logsData as $spanLogData) {
			Yii::getLogger()->log($spanLogData, Logger::LEVEL_INFO, 'opentracing');
		}

		if (true === $forceFlush) {
			Yii::getLogger()->flush();
		}
	}

	/**
	 * @param OTSpan $span
	 * @return array
	 */
	private function extractSpanData(OTSpan $span):array {
		$data = [
			'TStamp' => DateTime::createFromFormat('U.u', number_format($span->getStartTime(), 6, '.', ''))->format(DateTime::RFC3339_EXTENDED),
			'trace_id' => $span->getContext()->getTraceId(),
			'parent_id' => $span->getContext()->getParentSpanId(),
			'span_id' => $span->getContext()->getSpanId(),
			'duration' => $span->getDuration(),
			'operationName' => $span->getOperationName()
		];

		$logData = array_merge($data, $span->getTags(), $span->getContext()->getItems(), ...array_column($span->getLogs(), 'fields'));

		$logData['level'] = $logData['level']??'info';

		return $logData;
	}
}