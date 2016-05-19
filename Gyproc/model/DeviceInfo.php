<?php
class DeviceInfo {
	private $id_device;
	private $uuid;
	private $os;
	private $push_token;
	public function __construct() {
	}
	public static function initialize($__uuid, $__os, $__push_token) {
		$instance = new self ();

		$instance->uuid = $__uuid;
		$instance->os = $__os;
		$instance->push_token = $__push_token;

		return $instance;
	}
	public static function fromArray($__array) {
		$instance = new self ();

		foreach ( $__array as $key => $value ) {
			$instance->{$key} = $value;
		}

		return $instance;
	}
	public function getIdDevice() {
		return $this->id_device;
	}
	public function getUuid() {
		return $this->uuid;
	}
	public function getOs() {
		return $this->os;
	}
	public function getDeviceToken() {
		return $this->device_token;
	}
	public function jsonSerialize() {
		return array (
				'id_device' => $this->id_device,
				'uuid' => $this->uuid,
				'os' => $this->os,
				'push_token' => $this->push_token
		);
	}
}
