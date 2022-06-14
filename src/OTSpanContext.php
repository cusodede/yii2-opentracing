<?php
declare(strict_types = 1);

namespace cusodede\opentracing;

use ArrayIterator;
use Exception;
use OpenTracing\SpanContext;
use yii\helpers\ArrayHelper;

/**
 * Class OTSpanContext
 */
class OTSpanContext implements SpanContext {
	/**
	 * @var string
	 */
	private string $_traceId;
	/**
	 * @var string
	 */
	private string $_parentSpanId;
	/**
	 * @var string
	 */
	private string $_spanId;
	/**
	 * @var bool
	 */
	private bool $_isSampled;
	/**
	 * @var array
	 */
	private array $_items;

	/**
	 * @param string $traceId
	 * @param string $spanId
	 * @param string $parentSpanId
	 * @param bool $isSampled
	 * @param array $items
	 */
	public function __construct(
		string $traceId,
		string $spanId,
		string $parentSpanId = OTTracer::UNKNOWN_PARENT_ID,
		bool $isSampled = true,
		array $items = []
	) {
		$this->_traceId = $traceId;
		$this->_spanId = $spanId;
		$this->_parentSpanId = $parentSpanId;
		$this->_isSampled = $isSampled;
		$this->_items = $items;
	}

	/**
	 * @param bool $sampled
	 * @param array $items
	 * @return OTSpanContext
	 * @throws Exception
	 */
	public static function createRoot(bool $sampled = true, array $items = []):OTSpanContext {
		return new self(
			self::generateTraceId(),
			self::generateSpanId(),
			OTTracer::UNKNOWN_PARENT_ID,
			$sampled,
			$items
		);
	}

	/**
	 * @param OTSpanContext $spanContext
	 * @return OTSpanContext
	 * @throws Exception
	 */
	public static function createChildOf(OTSpanContext $spanContext):OTSpanContext {
		return new self(
			$spanContext->getTraceId(),
			self::generateSpanId(),
			$spanContext->getSpanId(),
			$spanContext->getIsSampled(),
			$spanContext->getItems()
		);
	}

	/**
	 * @return string
	 */
	public function getTraceId():string {
		return $this->_traceId;
	}

	/**
	 * @return string
	 */
	public function getSpanId():string {
		return $this->_spanId;
	}

	/**
	 * @return string
	 */
	public function getParentSpanId():string {
		return $this->_parentSpanId;
	}

	/**
	 * @return bool
	 */
	public function getIsSampled():bool {
		return $this->_isSampled;
	}

	/**
	 * @return array
	 */
	public function getItems():array {
		return $this->_items;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getIterator():ArrayIterator {
		return new ArrayIterator($this->_items);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBaggageItem(string $key):?string {
		return ArrayHelper::getValue($this->_items, $key);
	}

	/**
	 * {@inheritdoc}
	 */
	public function withBaggageItem(string $key, string $value):OTSpanContext {
		return new self($this->_traceId, $this->_spanId, $this->_parentSpanId, $this->_isSampled, array_merge($this->_items, [$key => $value]));
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	private static function generateTraceId():string {
		return bin2hex(random_bytes(16));
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	private static function generateSpanId():string {
		return bin2hex(random_bytes(8));
	}
}