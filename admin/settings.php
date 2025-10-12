<?php
include('../../dbconnection.php');

$query = "SELECT * FROM settings";
$result = $conn->query($query);

$settings = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $settings[$row['SettingKey']] = $row['SettingValue'];
    }

    $auto_logout_minutes = isset($settings['AutoLogoutMinutes']) ? (int)$settings['AutoLogoutMinutes'] : 15;
    $enable_admin_login = isset($settings['EnableAdminLogin']) ? (int)$settings['EnableAdminLogin'] : 1;
    $enable_member_login = isset($settings['EnableMemberLogin']) ? (int)$settings['EnableMemberLogin'] : 1;
    $lockout_duration = isset($settings['LockoutDuration']) ? (int)$settings['LockoutDuration'] : 15;
    $max_login_attempts = isset($settings['MaxLoginAttempts']) ? (int)$settings['MaxLoginAttempts'] : 5;
    $registration_open = isset($settings['RegistrationForm']) ? (int)$settings['RegistrationForm'] : 0;
}
