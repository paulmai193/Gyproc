<?php
class VersionInfo {
	private $source;
	function __construct() {
	}
	public static function fromArray($__array) {
		$instance = new self ();

		foreach ( $__array as $key => $value ) {
			$instance->{$key} = $value;
		}

		return $instance;
	}
	public function getSource() {
		return $this->source;
	}
	public function jsonSerialize() {
		return array (
				'source' => $this->source
		);
	}
}
