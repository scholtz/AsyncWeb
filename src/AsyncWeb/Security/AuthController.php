<?php

namespace AsyncWeb\Security;

interface AuthController{
	public function SERVICE_ID();
	public function check();
	public function form();
//	public function beforeLogout(); // if this function returns true, it means that controller has processed the logout sequence, and basic logout scheme should not continue
}
