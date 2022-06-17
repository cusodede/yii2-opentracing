<?php
declare(strict_types = 1);

namespace cusodede\opentracing;

use OpenTracing\Span;

/**
 * Class OTSpan
 * @package app\components\opentracing
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
	 * @var array
	 */
	private array $_logs = [];
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
		$this->_logs[] = ['timestamp' => $timestamp?:time(), 'fields' => $fields];
	}

	/**
	 * @return array
	 */
	public function getLogs():array {
		return $this->_logs;
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
}