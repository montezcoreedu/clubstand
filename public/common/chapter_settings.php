<?php
    $chapter_query = "SELECT * FROM chapter_settings";
    $chapter_result = $conn->query($chapter_query);

    $chapter = [];

    if ($chapter_result->num_rows > 0) {
        while ($row = $chapter_result->fetch_assoc()) {
            $chapter[$row['SettingKey']] = $row['SettingValue'];
        }

        $AdvisorEmail = isset($chapter['AdvisorEmail']) ? $chapter['AdvisorEmail'] : '';
        $ChapterName = isset($chapter['ChapterName']) ? $chapter['ChapterName'] : '';
        $MaxDemerits = isset($chapter['MaxDemerits']) ? $chapter['MaxDemerits'] : '';
        $MaxExcuseRequests = isset($chapter['MaxExcuseRequests']) ? $chapter['MaxExcuseRequests'] : '';
        $MaxGradeLevel = isset($chapter['MaxGradeLevel']) ? $chapter['MaxGradeLevel'] : 6;
        $MaxProbationDays = isset($chapter['MaxProbationDays']) ? $chapter['MaxProbationDays'] : 60;
        $MaxServiceHours = isset($chapter['MaxServiceHours']) ? $chapter['MaxServiceHours'] : '';
        $MaxUnexcusedAbsence = isset($chapter['MaxUnexcusedAbsence']) ? $chapter['MaxUnexcusedAbsence'] : '';
        $MaxUnexcusedTardy = isset($chapter['MaxUnexcusedTardy']) ? $chapter['MaxUnexcusedTardy'] : '';
        $MinGradeLevel = isset($chapter['MinGradeLevel']) ? $chapter['MinGradeLevel'] : 12;
        $OfficerEmail = isset($chapter['OfficerEmail']) ? $chapter['OfficerEmail'] : '';
        $Schools = isset($chapter['Schools']) ? $chapter['Schools'] : '';
        $Website = isset($chapter['Website']) ? $chapter['Website'] : '';
    }
    