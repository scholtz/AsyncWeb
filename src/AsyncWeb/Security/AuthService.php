<?php
namespace AsyncWeb\Security;
interface AuthService {
    public function SERVICE_ID();
    public function SERVICE_Group();
    public function check(Array $data = array());
    public function loginForm();
}
