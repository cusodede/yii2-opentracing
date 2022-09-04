<?php
declare(strict_types = 1);

namespace cusodede\opentracing;

use OpenTracing\SpanContext;
use OpenTracing\StartSpanOptions;
use OpenTracing\Tracer;
use OpenTracing\UnsupportedFormatException;
use OpenTracing\InvalidReferenceArgumentException;
use yii\helpers\ArrayHelper;
use const OpenTracing\Formats\HTTP_HEADERS;

/**
 * Class OTTracer
 */
class OTTracer implements Tracer {
	public const UNKNOWN_PARENT_ID = '0000000000000000';

	/**
	 * @var string
	 */
	public string $traceParentHeaderName = 'traceparent';

	/**
	 * @var OTSpan[]
	 */
	private array $_spans = [];
	/**
	 * @var array|callable[]
	 */
	private array $_injectors;
	/**
	 * @var array|callable[]
	 */
	private array $_extractors;
	/**
	 * @var OTScopeManager
	 */
	private OTScopeManager $_scopeManager;

	/**
	 * @param array $injectors
	 * @param array $extractors
	 */
	public function __construct(array $injectors = [], array $extractors = []) {
		if ([] === $injectors) {
			$this->_injectors = [
				HTTP_HEADERS => function(OTSpanContext $context, &$carrier) {
					$carrier[$this->traceParentHeaderName] = "00-{$context->getTraceId()}-{$context->getSpanId()}-00";
				}
			];
		}

		if ([] === $extractors) {
			$this->_extractors = [
				HTTP_HEADERS => function($carrier) {
					if (null !== $traceParentInfo = ArrayHelper::getValue(array_change_key_case($carrier), strtolower($this->traceParentHeaderName))) {//RFC2616 headers are case-insensitive
						$traceParts = explode('-', $traceParentInfo);
						if (isset($traceParts[1], $traceParts[2])) {
							return new OTSpanContext($traceParts[1], $traceParts[2]);
						}
					}

					return null;
				}
			];
		}

		$this->_scopeManager = new OTScopeManager();
	}

	/**
	 * {@inheritdoc}
	 * @noinspection ParameterDefaultValueIsNotNullInspection according ot the parent interface.
	 */
	public function startActiveSpan(string $operationName, $options = []):OTScope {
		if (!($options instanceof StartSpanOptions)) {
			$options = StartSpanOptions::create($options);
		}

		if (null !== $activeSpan = $this->getActiveSpan()) {
			$options = $options->withParent($activeSpan);
		}

		$span = $this->startSpan($operationName, $options);

		return $this->_scopeManager->activate($span, $options->shouldFinishSpanOnClose());
	}

	/**
	 * {@inheritdoc}
	 * @noinspection ParameterDefaultValueIsNotNullInspection according ot the parent interface.
	 */
	public function startSpan(string $operationName, $options = []):OTSpan {
		if (!($options instanceof StartSpanOptions)) {
			$options = StartSpanOptions::create($options);
		}

		if (empty($options->getReferences())) {
			$spanContext = OTSpanContext::createRoot();
		} else {
			$referenceContext = $options->getReferences()[0]->getSpanContext();
			if (!$referenceContext instanceof OTSpanContext) {
				throw InvalidReferenceArgumentException::forInvalidContext($referenceContext);
			}

			$spanContext = OTSpanContext::createChildOf($referenceContext);
		}

		$span = new OTSpan($operationName, $spanContext);

		foreach ($options->getTags() as $key => $value) {
			$span->setTag($key, $value);
		}

		$this->_spans[] = $span;

		return $span;
	}

	/**
	 * {@inheritdoc}
	 */
	public function inject(OTSpanContext|SpanContext $spanContext, string $format, &$carrier):void {
		if (!array_key_exists($format, $this->_injectors)) {
			throw UnsupportedFormatException::forFormat($format);
		}

		$this->_injectors[$format]($spanContext, $carrier);
	}

	/**
	 * {@inheritdoc}
	 */
	public function extract(string $format, $carrier):?OTSpanContext {
		if (!array_key_exists($format, $this->_extractors)) {
			throw UnsupportedFormatException::forFormat($format);
		}

		return $this->_extractors[$format]($carrier);
	}

	/**
	 * {@inheritdoc}
	 */
	public function flush():void {
		$this->_spans = [];
	}

	/**
	 * @return OTSpan[]
	 */
	public function getSpans():array {
		return $this->_spans;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getScopeManager():OTScopeManager {
		return $this->_scopeManager;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getActiveSpan():?OTSpan {
		return $this->_scopeManager->getActive()?->getSpan();
	}
}