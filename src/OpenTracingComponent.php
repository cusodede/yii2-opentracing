<?php
declare(strict_types = 1);

namespace cusodede\opentracing;

use cusodede\opentracing\handlers\EventHandlerInterface;
use cusodede\opentracing\handlers\HttpRequestHandler;
use cusodede\opentracing\handlers\RootEventHandlerInterface;
use DateTime;
use OpenTracing\GlobalTracer;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\StringHelper;
use Throwable;
use yii\log\Logger;

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

		$this->getRootHandler()->attach($this);
		foreach ($this->getEventHandlers() as $eventHandlers) {
			$eventHandlers->attach($this->_tracer);
		}
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