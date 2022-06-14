<?php
declare(strict_types = 1);

namespace cusodede\opentracing\log_targets;

use yii\log\FileTarget;

/**
 * Class OpenTracingFileTarget
 */
class OpenTracingFileTarget extends FileTarget {
	public string $dir = '@runtime/logs';

	/**
	 * {@inheritdoc}
	 */
	public function init():void {
		$this->enableRotation = false;//DevOps will handle file rotation.

		$this->maxFileSize = 10240 * 4;

		$this->logVars = [];
		if (null === $this->logFile) {
			$this->logFile = $this->dir.DIRECTORY_SEPARATOR.'ot-'.date('YmdH').'.log';
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