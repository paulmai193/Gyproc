<?php
class UserInfo {
	private $iduser;
	private $email;
	private $phone;
	private $name;
	private $gender;
	private $role;
	private $id_device;
	public function __construct() {
	}
	public static function initialize($__email, $__phone, $__name, $__gender, $__role) {
		$instance = new self ();

		$instance->email = $__email;
		$instance->phone = $__phone;
		$instance->name = $__name;
		$instance->gender = $__gender;
		$instance->role = $__role;

		return $instance;
	}
	public static function fromArray($__array) {
		$instance = new self ();

		foreach ( $__array as $key => $value ) {
			$instance->{$key} = $value;
		}

		return $instance;
	}
	public function getIduser() {
		return $this->iduser;
	}
	public function getEmail() {
		return $this->email;
	}
	public function getPhone() {
		return $this->phone;
	}
	public function getName() {
		return $this->name;
	}
	public function getGender() {
		return $this->gender;
	}
	public function getRole() {
		return $this->role;
	}
	public function getiddevice() {
		return $this->id_device;
	}
	public function jsonSerialize() {
		return array (
				'iduser' => $this->iduser,
				'name' => $this->name,
				'email' => $this->email,
				'phone' => $this->phone,
				'gender' => $this->gender,
				'role' => $this->role,
				'id_device' => $this->id_device
		);
	}
}
?>
