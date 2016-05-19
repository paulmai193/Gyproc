<?php
class VersionInfo {
	private $source;
	private $filter;
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
	public function getFilter() {
		return $this->filter;
	}
	public function jsonSerialize() {
		return array (
				'source' => $this->source,
				'filter' => $this->filter
		);
	}
}
