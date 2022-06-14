<?php
declare(strict_types = 1);

namespace cusodede\opentracing\targets;

use yii\log\FileTarget;

/**
 * Class OpenTracingFileTarget
 */
class OpenTracingFileTarget extends FileTarget {

	/**
	 * {@inheritdoc}
	 */
	public function init():void {
		if (is_callable($this->logFile)) {
			$this->logFile = call_user_func($this->logFile);
		}

		parent::init();
	}

	/**
	 * @param array $message
	 * @return string
	 */
	public function formatMessage($message):string {
		return is_string($message[0])?$message[0]:json_encode($message[0], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
}