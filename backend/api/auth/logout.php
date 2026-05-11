<?php
require_once __DIR__ . '/../../config/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();

json_response(['ok' => true]);
