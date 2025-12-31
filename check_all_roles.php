<?php
require_once('../../../wp-load.php');

global $wp_roles;
$all_roles = $wp_roles->get_names();

$counts = [];
foreach ($all_roles as $role_key => $role_name) {
    $users = get_users(['role' => $role_key, 'fields' => 'ID']);
    if (count($users) > 0) {
        $counts[$role_key] = [
            'name' => $role_name,
            'count' => count($users)
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($counts, JSON_PRETTY_PRINT);

