<?php
declare(strict_types = 1);

namespace cusodede\opentracing;

use OpenTracing\Scope;

/**
 * Class OTScope
 */
class OTScope implements Scope {
	/**
	 * @var OTSpan
	 */
	private OTSpan $_span;
	/**
	 * @var OTScopeManager
	 */
	private OTScopeManager $_scopeManager;
	/**
	 * @var bool
	 */
	private bool $_finishSpanOnClose;

	/**
	 * @param OTScopeManager $scopeManager
	 * @param OTSpan $span
	 * @param bool $finishSpanOnClose
	 */
	public function __construct(OTScopeManager $scopeManager, OTSpan $span, bool $finishSpanOnClose) {
		$this->_scopeManager = $scopeManager;
		$this->_span = $span;
		$this->_finishSpanOnClose = $finishSpanOnClose;
	}

	public function close():void {
		if ($this->_finishSpanOnClose) {
			$this->_span->finish();
		}

		$this->_scopeManager->deactivate($this);
	}

	/**
	 * @return OTSpan
	 */
	public function getSpan():OTSpan {
		return $this->_span;
	}
}