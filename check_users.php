<?php
require_once('../../../wp-load.php');

$roles = ['asmaa_staff', 'asmaa_manager', 'asmaa_admin', 'customer', 'administrator'];
$counts = [];

foreach ($roles as $role) {
    $users = get_users(['role' => $role, 'fields' => 'ID']);
    $counts[$role] = count($users);
}

header('Content-Type: application/json');
echo json_encode($counts, JSON_PRETTY_PRINT);
