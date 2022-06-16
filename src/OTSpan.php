<?php
declare(strict_types = 1);

namespace cusodede\opentracing;

use DateTime;
use OpenTracing\Span;
use Yii;
use yii\log\Logger;

/**
 * Class OTSpan
 */
class OTSpan implements Span {
	/**
	 * @var string
	 */
	private string $_operationName;
	/**
	 * @var OTSpanContext
	 */
	private OTSpanContext $_context;
	/**
	 * @var array
	 */
	private array $_tags = [];
	/**
	 * @var float
	 */
	private float $_startTime;
	/**
	 * @var int|null
	 */
	private int|null $_duration = null;

	/**
	 * @param string $operationName
	 * @param OTSpanContext $context
	 */
	public function __construct(string $operationName, OTSpanContext $context) {
		$this->_operationName = $operationName;
		$this->_context = $context;
		$this->_startTime = microtime(true);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getOperationName():string {
		return $this->_operationName;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getContext():OTSpanContext {
		return $this->_context;
	}

	/**
	 * @return float
	 */
	public function getStartTime():float {
		return $this->_startTime;
	}

	/**
	 * @return bool
	 */
	public function getIsFinished():bool {
		return null !== $this->_duration;
	}

	/**
	 * @return int|null
	 */
	public function getDuration():?int {
		return $this->_duration;
	}

	/**
	 * {@inheritdoc}
	 */
	public function overwriteOperationName(string $newOperationName):void {
		$this->_operationName = $newOperationName;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setTag(string $key, $value):void {
		$this->_tags[$key] = $value;
	}

	/**
	 * @return array
	 */
	public function getTags():array {
		return $this->_tags;
	}

	/**
	 * {@inheritdoc}
	 */
	public function log(array $fields = [], $timestamp = null):void {
		Yii::getLogger()->log($this->createLogRecord($fields), Logger::LEVEL_INFO, OpenTracingComponent::CATEGORY);
	}

	/**
	 * {@inheritdoc}
	 */
	public function addBaggageItem(string $key, string $value):void {
		$this->_context = $this->_context->withBaggageItem($key, $value);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBaggageItem(string $key):?string {
		return $this->_context->getBaggageItem($key);
	}

	/**
	 * {@inheritdoc}
	 */
	public function finish($finishTime = null):void {
		$this->_duration = ($finishTime?:(int)(microtime(true) * 1000)) - (int)($this->_startTime * 1000);
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	private function createLogRecord(array $fields):array {
		$data = [
			'TStamp' => DateTime::createFromFormat('U.u', number_format($this->getStartTime(), 6, '.', ''))->format(DateTime::RFC3339_EXTENDED),
			'trace_id' => $this->getContext()->getTraceId(),
			'parent_id' => $this->getContext()->getParentSpanId(),
			'span_id' => $this->getContext()->getSpanId(),
			'duration' => $this->getDuration(),
			'operationName' => $this->getOperationName()
		];

		$logData = array_merge($data, $this->getTags(), $this->getContext()->getItems(), $fields);

		$logData['level'] = $logData['level']??'info';

		return $logData;
	}
}