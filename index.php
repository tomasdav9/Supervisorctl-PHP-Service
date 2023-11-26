<?php
// Load composer
require __DIR__ . '/vendor/autoload.php';
// Load supervisor service
include './src/SupervisorService.php';

// Create supervisor service and get processes
$supervisor = new SupervisorService('localhost', '9001', 'user', '123');
print_r($supervisor->getProcesses());