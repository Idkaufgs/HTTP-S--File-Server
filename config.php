<?php
// Backend API URL
define('FASTAPI_URL', 'http://127.0.0.1:8000');

//Session Config
ini_set('session.gc_maxlifetime', 3600);       
ini_set('session.cookie_lifetime', 3600);      
ini_set('session.cookie_httponly', 1);         
ini_set('session.cookie_samesite', 'Strict');  