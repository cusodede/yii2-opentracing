<?php
declare(strict_types = 1);

namespace cusodede\opentracing;

use cusodede\opentracing\handlers\EventHandlerInterface;
use cusodede\opentracing\handlers\HttpRequestHandler;
use cusodede\opentracing\handlers\RootEventHandlerInterface;
use DateTime;
use OpenTracing\GlobalTracer;
use Yii;
use yii\base\Application as BaseApplication;
use yii\base\Component;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use Throwable;
use yii\httpclient\Client;
use yii\httpclient\RequestEvent;
use yii\log\Logger;
use yii\web\Application as WebApplication;
use yii\web\Response;
use const OpenTracing\Formats\HTTP_HEADERS;

/**
 * Class OpenTracingComponent
 *
 * @property string $traceParentHeaderName
 * @property string[] $excludedRequestsPaths Исключаемые из логирования урлы
 * @property string $rootHandler Подключённый класс корневого обработчика
 * @property string[] $handlers Подключённые классы обработчиков
 *
 * @property-read ?OTTracer $tracer Обработчик, отвечающий за управление span'ми.
 * @property-read ?OTScope $rootScope Базовый (верхний) trace span. Сеттер может быть вызван только напрямую, чтобы не было соблазна.
 */
class OpenTracingComponent extends Component {

	public const CATEGORY = 'opentracing';
	/**
	 * @var string
	 */
	public string $traceParentHeaderName = 'traceparent';
	/**
	 * @var string[]
	 */
	public array $excludedRequestsPaths = [];

	/**
	 * @var string
	 */
	public string $rootHandler = HttpRequestHandler::class;
	/**
	 * @var string[]
	 */
	public array $handlers = [];
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
		if (null !== $requestPath = $this->getPathInfo()) {//Не будем даже инициализировать компонент, если url запроса исключается из логирования.
			foreach ($this->excludedRequestsPaths as $excludedPath) {
				if (StringHelper::matchWildcard($excludedPath, $requestPath)) return;
			}
		}

		register_shutdown_function(function() {
			$this->finish();
		});

		$this->_tracer = new OTTracer();
		$this->_tracer->traceParentHeaderName = $this->traceParentHeaderName;

		GlobalTracer::set($this->_tracer);

		$this->attachEvents();
		$this->attachHttpClientEvents();
	}

	/**
	 * Навешиваем обработчики на события уровня приложения.
	 */
	private function attachEvents():void {
		Yii::$app->on(BaseApplication::EVENT_BEFORE_REQUEST, function() {
			try {
				$spanContext = GlobalTracer::get()->extract(HTTP_HEADERS, getallheaders());
			} catch (Throwable) {
				$spanContext = null;
			}

			$span = $this->_tracer->startSpan('application.request', null !== $spanContext?['child_of' => $spanContext]:[]);
			$span->log(OpenTracingLogDataHandler::transformRequestParams(Yii::$app->request));

			$this->_rootScope = $this->_tracer->getScopeManager()->activate($span);
		}
		);

		$responseLogCallback = function() {
			$this->_rootScope->getSpan()->log(OpenTracingLogDataHandler::transformResponseParams(Yii::$app->response));

			if (null !== $exception = ArrayHelper::getValue(Yii::$app->controller->module, 'errorHandler.exception')) {
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

	}

	/**
	 * Навешиваем обработчики на события yii\httpclient\Client - используется как прослойка для подключения к внешним АПИ.
	 */
	private function attachHttpClientEvents():void {
		Event::on(Client::class, Client::EVENT_BEFORE_SEND, function(RequestEvent $e) {
			$activeScope = $this->_tracer->startActiveSpan('client.request');
			$activeScope->getSpan()->log(
				OpenTracingLogDataHandler::transformRequestParams($e->request)
			);

			$this->_tracer->inject($activeScope->getSpan()->getContext(), HTTP_HEADERS, $e->request->headers);
		});

		Event::on(Client::class, Client::EVENT_AFTER_SEND, function(RequestEvent $e) {
			if (null !== $activeScope = $this->_tracer->getScopeManager()->getActive()) {
				$activeScope->getSpan()->log(
					OpenTracingLogDataHandler::transformResponseParams($e->response)
				);
				$activeScope->close();
			}
		});
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

	/**
	 * @return RootEventHandlerInterface
	 * @throws InvalidConfigException
	 */
	private function getRootHandler():RootEventHandlerInterface {
		return Yii::createObject($this->rootHandler);
	}

	/**
	 * Загружает классы обработчиков событий из конфигов
	 * @return EventHandlerInterface[]
	 * @throws InvalidConfigException
	 */
	protected function getEventHandlers():array {
		$result = [];
		foreach ($this->handlers as $item) {
			$result[] = Yii::createObject($item);
		}
		return $result;
	}

	/**
	 * @return OTTracer|null
	 */
	public function getTracer():?OTTracer {
		return $this->_tracer;
	}

	/**
	 * @return OTScope|null
	 */
	public function getRootScope():?OTScope {
		return $this->_rootScope;
	}

	/**
	 * @param OTScope $rootScope
	 */
	public function setRootScope(OTScope $rootScope):void {
		$this->_rootScope = $rootScope;
	}

	/**
	 * @return string|null Part of the request URL that is after the entry script and before the question or null, if no URI is requested
	 */
	protected function getPathInfo():?string {
		try {
			return Yii::$app->request->pathInfo;
		} catch (Throwable) {
			return null;
		}
	}

}