<?php
declare(strict_types = 1);

namespace cusodede\opentracing;

use OpenTracing\ScopeManager;
use OpenTracing\Span;

/**
 * Class OTScopeManager
 */
class OTScopeManager implements ScopeManager {
	/**
	 * @var OTScope[]
	 */
	private array $_scopes = [];

	/**
	 * {@inheritdoc}
	 */
	public function activate(OTSpan|Span $span, bool $finishSpanOnClose = self::DEFAULT_FINISH_SPAN_ON_CLOSE):OTScope {
		$scope = new OTScope($this, $span, $finishSpanOnClose);
		$this->_scopes[] = $scope;

		return $scope;
	}

	/**
	 * @param OTScope $scope
	 */
	public function deactivate(OTScope $scope):void {
		foreach ($this->_scopes as $scopeIndex => $scopeItem) {
			if ($scope === $scopeItem) {
				unset($this->_scopes[$scopeIndex]);

				//Refresh array keys.
				$this->_scopes = array_values($this->_scopes);
				return;
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getActive():?OTScope {
		return [] === $this->_scopes?null:$this->_scopes[count($this->_scopes) - 1];
	}
}