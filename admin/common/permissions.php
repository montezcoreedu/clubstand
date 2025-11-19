<?php
    $accountId = $_SESSION['account_id'];
    
    function getUserPermissions($accountId, $conn) {
        $stmt = $conn->prepare("SELECT AccountGroup FROM accounts WHERE AccountId = ?");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (is_null($account['AccountGroup'])) {
            $stmt = $conn->query("SELECT PermissionName FROM permissions");
            $perms = [];
            while ($row = $stmt->fetch_assoc()) {
                $perms[] = $row['PermissionName'];
            }
            return $perms;
        }

        $stmt = $conn->prepare("SELECT p.PermissionName 
            FROM group_permissions gp
            JOIN permissions p ON gp.PermissionId = p.PermissionId
            WHERE gp.GroupId = ?");
        $stmt->bind_param("i", $account['AccountGroup']);
        $stmt->execute();
        $result = $stmt->get_result();

        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row['PermissionName'];
        }

        return $permissions;
    }

    $userPermissions = getUserPermissions($accountId, $conn);
