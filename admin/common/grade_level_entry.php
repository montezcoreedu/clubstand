<?php
    $minGradeQuery = $conn->query("SELECT SettingValue FROM chapter_settings WHERE SettingKey = 'MinGradeLevel'");
    $maxGradeQuery = $conn->query("SELECT SettingValue FROM chapter_settings WHERE SettingKey = 'MaxGradeLevel'");

    $minGrade = $minGradeQuery->fetch_assoc()['SettingValue'] ?? 0;
    $maxGrade = $maxGradeQuery->fetch_assoc()['SettingValue'] ?? 12;
    function getGradeLabel($grade) {
        switch ((int)$grade) {
            case -2: return "PK3";
            case -1: return "PK4";
            case 0: return "K";
            default: return $grade;
        }
    }
