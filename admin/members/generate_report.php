<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/chapter_settings.php");
    require_once('../../TCPDF/tcpdf.php');
    include("../common/permissions.php");

    if (!in_array("Reports", $userPermissions)) {
        header("Location: ../home/index.php");
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $MemberId = $_POST['MemberId'];
        $reportType = $_POST['report'];
        $savePdf = $_POST['save_pdf'];
        $watermark = $_POST['watermark'];

        $max_absences = (!empty($chapter['MaxUnexcusedAbsence'])) ? (int)$chapter['MaxUnexcusedAbsence'] : 0;
        $max_tardies = (!empty($chapter['MaxUnexcusedTardy'])) ? (int)$chapter['MaxUnexcusedTardy'] : 0;
        $max_demerits = (!empty($chapter['MaxDemerits'])) ? (int)$chapter['MaxDemerits'] : 0;
        $required_services = (!empty($chapter['MaxServiceHours'])) ? (int)$chapter['MaxServiceHours'] : 0;

        $member_query = "SELECT * FROM members WHERE MemberId = $MemberId";
        $member_result = mysqli_query($conn, $member_query);
        $member = mysqli_fetch_assoc($member_result);

        if (!$member) {
            header("HTTP/1.0 404 Not Found");
            exit();
        }

        if ($reportType == 'membershipletter') {
            $ReportName = 'At Risk Membership Letter';
        } elseif ($reportType == 'attendance') {
            $ReportName = 'Attendance Report';
        } elseif ($reportType == 'communityservices') {
            $ReportName = 'Community Services Report';
        } elseif ($reportType == 'demerits') {
            $ReportName = 'Demerits Report';
        } elseif ($reportType == 'memberprofile') {
            $ReportName = 'Member Profile';
        } elseif ($reportType == 'portalletter') {
            $ReportName = 'Membership Portal Letter';
        } elseif ($reportType == 'probationletter') {
            $ReportName = 'Probation Letter';
        } elseif ($reportType == 'terminationletter') {
            $ReportName = 'Termination Letter';
        }

        $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
        $pdf->SetMargins(20, 25, 20);
        $pdf->SetAutoPageBreak(true, 20);

        $pdf->setHeaderData('', 0, '', '', array(255, 255, 255), array(255, 255, 255));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->SetFont('avenir', '', 12);

        $pdf->SetCreator('TCPDF');
        $pdf->SetAuthor('' . $chapter['ChapterName'] . '');
        $pdf->SetSubject('Member Report');
        $pdf->SetTitle(''.$member['FirstName'].' '.$member['LastName'].' - '.$ReportName.'');

        $pdf->AddPage();

        $currentFont = $pdf->getFontFamily();
        $currentFontSize = $pdf->getFontSizePt();

        if ($watermark) {
            $pdf->SetAlpha(0.1);
        
            $pdf->SetFont('avenirb', '', 80);
            
            $watermarkWidth = $pdf->GetStringWidth(strtoupper($watermark));
            $watermarkHeight = 80;
        
            $xPosition = (230 - $watermarkWidth) / 2;
            $yPosition = (250 - $watermarkHeight) / 2;
        
            $pdf->StartTransform();
            $pdf->Rotate(30, $xPosition + ($watermarkWidth / 2), $yPosition + ($watermarkHeight / 2));
            $pdf->Text($xPosition, $yPosition, strtoupper($watermark));
            $pdf->StopTransform();

            $pdf->SetAlpha(1);
        }

        $pdf->SetXY(20, 25);

        $pdf->SetFont($currentFont, '', $currentFontSize);

        if ($reportType == 'membershipletter') {
            // Current Status
            $internal_progress_sql = "SELECT SUM(ServiceHours) AS ServiceHours FROM memberservicehours WHERE MemberId = $MemberId AND Archived = 0";
            $internal_progress_query = $conn->query($internal_progress_sql);
            $internal_progress = mysqli_fetch_assoc($internal_progress_query);
            $internal_hours = $internal_progress['ServiceHours'] ?? 0;

            $transfer_progress_sql = "SELECT SUM(ServiceHours) AS ServiceHours FROM membertransferhours WHERE MemberId = $MemberId AND Archived = 0";
            $transfer_progress_query = $conn->query($transfer_progress_sql);
            $transfer_progress = mysqli_fetch_assoc($transfer_progress_query);
            $transfer_hours = $transfer_progress['ServiceHours'] ?? 0;

            $service_hours = $internal_hours + $transfer_hours;

            $attendance_query = "SELECT 
                                    SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS total_absences,
                                    SUM(CASE WHEN status = 'Tardy' THEN 1 ELSE 0 END) AS total_tardies
                                FROM attendance
                                WHERE MemberId = $MemberId
                                AND Archived = 0";
            $attendance_result = $conn->query($attendance_query);
            $attendance = ($attendance_result && $row = $attendance_result->fetch_assoc()) ? $row : ['total_absences' => 0, 'total_tardies' => 0];

            $currentStatusNote = [];

            if ($service_hours <= $required_services) {
                $currentStatusNote[] = "Low service hours (≤ $required_services)";
            }

            if ($attendance['total_absences'] > $max_absences || $attendance['total_tardies'] > $max_tardies) {
                $currentStatusNote[] = "Too many absences or tardies";
            }

            $currentStatusString = !empty($currentStatusNote) ? implode("<br>", $currentStatusNote) : "In good standing";

            // Image
            $imagePath = realpath(__DIR__ . '/../images/bhsfblalogo-pdf.PNG');
            if (file_exists($imagePath)) {
                $pdf->Image($imagePath, 10, 5, 45);
                $pdf->Ln(5);
            }

            // Header
            $html = '
                <h4 style="font-size: 14px; line-height: 10px; text-align: center;">' . $chapter['ChapterName'] . '</h4>
            ';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(15);

            if ($member['SecondaryContactName']) {
                $SecondaryContactExists = ', '.htmlspecialchars($member['SecondaryContactName']);
            } else {
                $SecondaryContactExists = '';
            }

            // Letter Data
            $html = '
                <p style="font-size: 11px;">Dear '.htmlspecialchars($member['FirstName']).' '.htmlspecialchars($member['LastName']).',</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(5);

            $html = '
                <p style="font-size: 11px; line-height: 25px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;We would like to inform you that your current membership may not be renewed for the upcoming school year due to a lower number of service hours or attendance percentage. As we approach the end of this academic year, we encourage you to consider increasing your participation if you are interested in maintaining your membership for the upcoming year. Active engagement is also essential if you aspire to take on a leadership role. Below, you will find a detailed report outlining the areas where improvement is needed to enhance your membership experience.</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(8);

            $html = '
                <p style="font-size: 11px; line-height: 20px;"><b>Current status:<br>' . $currentStatusString . '</b></p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(8);

            // Thanks Message
            $html = '
                <p style="font-size: 11px; line-height: 20px;">If you have any questions before or during this process, please reach out to your coordinating directory.</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(5);

            $html = '
                <p style="font-size: 11px; line-height: 20px;">Thanks,
                    <br>' . $chapter['ChapterName'] . '</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
        } if ($reportType == 'attendance') {
            // Attendance DB
            $attendance_query = "SELECT MeetingDate, Status FROM attendance WHERE MemberId = $MemberId AND Archived = 0 ORDER BY MeetingDate desc";
            $attendance_result = mysqli_query($conn, $attendance_query);

            // Attendance Percentage
            $attpercentage_query = "SELECT ROUND((SELECT COUNT(*) FROM attendance WHERE (Status = 'Present' OR Status = 'Excused') AND MemberId = $MemberId) * 100 / COUNT(*)) AS AttPercentage FROM attendance WHERE MemberId = $MemberId AND Archived = 0";
            $attpercentage_result = $conn->query($attpercentage_query);
            $att_percent = mysqli_fetch_assoc($attpercentage_result);

            // Image
            $imagePath = realpath(__DIR__ . '/../images/bhsfblalogo-pdf.PNG');
            if (file_exists($imagePath)) {
                $pdf->Image($imagePath, 10, 5, 45);
                $pdf->Ln(5);
            }

            // Header
            $html = '
                <h4 style="font-size: 14px; line-height: 10px; text-align: center;">' . $chapter['ChapterName'] . '</h4>
            ';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(15);

            if ($member['SecondaryContactName']) {
                $SecondaryContactExists = ', '.htmlspecialchars($member['SecondaryContactName']);
            } else {
                $SecondaryContactExists = '';
            }

            // Member Data
            $html = '
                <p style="font-weight: bold; font-size: 11px; line-height: 4px;">To the Member & Parent/Guardian:</p>
                <p style="font-size: 11px; line-height: 4px;">'.htmlspecialchars($member['FirstName']).' '.htmlspecialchars($member['LastName']).', '.htmlspecialchars($member['PrimaryContactName']).''.htmlspecialchars($SecondaryContactExists).'</p>
                <p style="font-size: 11px; line-height: 4px;">Grade '.htmlspecialchars($member['GradeLevel']).'</p>
                <p style="font-size: 11px; line-height: 4px;">Date Reported: ' . date("m/d/Y") . '</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(12);

            // Additional Text
            $html = '
                <p style="font-size: 11px; line-height: 18px;">Please find attached a comprehensive report detailing meeting attendance. Members are required to achieve an attendance percentage of at least 70% to maintain eligibility for membership in the upcoming year.</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(5);

            $html = '
                <p style="font-size: 11px; line-height: 18px;"><b>Daily Attendance Average: ' .$att_percent['AttPercentage']. '%</b></p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(5);

            // Attendance Table
            $htmlTable = '
                <table style="font-size: 11px; width: 100%; padding: 4px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 4px; border-bottom: 0.8px solid #b9b9b9;"><b>Meeting Date</b></th>
                            <th style="text-align: left; padding: 4px; border-bottom: 0.8px solid #b9b9b9;"><b>Attendance</b></th>
                        </tr>
                    </thead>
                    <tbody>';
                    while ($attendance = mysqli_fetch_assoc($attendance_result)) {
                        $htmlTable .= '
                            <tr>
                                <td style="padding: 4px; border-bottom: 1px solid #c8c8c8;">' . date("F j, Y", strtotime($attendance['MeetingDate'])) . '</td>
                                <td style="padding: 4px; border-bottom: 1px solid #c8c8c8;">' . $attendance['Status'] . '</td>
                            </tr>';
                    }
                $htmlTable .= '
                    </tbody>
                </table>';
            $pdf->writeHTML($htmlTable, true, false, false, false, '');
        } elseif ($reportType == 'communityservices') {
            // Community Services DB
            $services_query = "SELECT c.ServiceName, c.ServiceDate, c.ServiceType, ms.EntryId, ms.ServiceHours FROM communityservices c JOIN memberservicehours ms ON c.ServiceId = ms.ServiceId WHERE ms.MemberId = $MemberId AND ms.Archived = 0 ORDER BY c.ServiceDate desc";
            $services_result = $conn->query($services_query);

            // Service Hours
            $servicehours_sql = "SELECT SUM(ServiceHours) AS TotalServiceHours FROM memberservicehours WHERE MemberId = $MemberId AND Archived = 0";
            $servicehours_query = $conn->query($servicehours_sql);
            $hours_count = mysqli_fetch_assoc($servicehours_query);

            // Image
            $imagePath = realpath(__DIR__ . '/../images/bhsfblalogo-pdf.PNG');
            if (file_exists($imagePath)) {
                $pdf->Image($imagePath, 10, 5, 45);
                $pdf->Ln(5);
            }

            // Header
            $html = '
                <h4 style="font-size: 14px; line-height: 10px; text-align: center;">' . $chapter['ChapterName'] . '</h4>
            ';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(15);

            if ($member['SecondaryContactName']) {
                $SecondaryContactExists = ', '.htmlspecialchars($member['SecondaryContactName']);
            } else {
                $SecondaryContactExists = '';
            }

            // Member Data
            $html = '
                <p style="font-weight: bold; font-size: 11px; line-height: 4px;">To the Member & Parent/Guardian:</p>
                <p style="font-size: 11px; line-height: 4px;">'.htmlspecialchars($member['FirstName']).' '.htmlspecialchars($member['LastName']).', '.htmlspecialchars($member['PrimaryContactName']).''.htmlspecialchars($SecondaryContactExists).'</p>
                <p style="font-size: 11px; line-height: 4px;">Grade '.htmlspecialchars($member['GradeLevel']).'</p>
                <p style="font-size: 11px; line-height: 4px;">Date Reported: ' . date("m/d/Y") . '</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(12);

            // Additional Text
            $html = '
                <p style="font-size: 11px; line-height: 18px;">Attached is a comprehensive report detailing the community service activities conducted within the organization. Members are required to complete a total of ' . $required_services . ' community service hours by the end of the membership year in order to be eligible for membership in the upcoming school year. Please be aware that transferred community service hours are not included in this report.</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(8);

            // Community Services Table
            $htmlTable = '
                <table style="font-size: 11px; width: 100%; padding: 4px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 4px; border-bottom: 0.8px solid #b9b9b9;"><b>Date</b></th>
                            <th style="text-align: left; padding: 4px; border-bottom: 0.8px solid #b9b9b9;"><b>Service Name</b></th>
                            <th style="text-align: left; padding: 4px; border-bottom: 0.8px solid #b9b9b9;"><b>Type</b></th>
                            <th style="text-align: center; padding: 4px; border-bottom: 0.8px solid #b9b9b9;"><b>Credit Hours</b></th>
                        </tr>
                    </thead>
                    <tbody>';
                    while ($service = mysqli_fetch_assoc($services_result)) {
                        $htmlTable .= '
                            <tr>
                                <td style="padding: 4px; border-bottom: 1px solid #c8c8c8;">' . date("m/d/Y", strtotime($service['ServiceDate'])) . '</td>
                                <td style="padding: 4px; border-bottom: 1px solid #c8c8c8;">' . $service['ServiceName'] . '</td>
                                <td style="padding: 4px; border-bottom: 1px solid #c8c8c8;">' . $service['ServiceType'] . '</td>
                                <td style="text-align: center; padding: 4px; border-bottom: 1px solid #c8c8c8;">' . $service['ServiceHours'] . '</td>
                            </tr>';
                    }
                $htmlTable .= '
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; padding: 4px; border-top: 1px solid #c8c8c8;"><b>Total Service Hours:</b></td>
                            <td style="text-align: center; padding: 4px; border-top: 1px solid #c8c8c8;">' . $hours_count['TotalServiceHours'] . '</td>
                        </tr>
                    </tfoot>
                </table>';
            $pdf->writeHTML($htmlTable, true, false, false, false, '');
        } elseif ($reportType == 'demerits') {
            // Demerits DB
            $demerits_query = "SELECT DemeritDate, Demerit, DemeritDescription, DemeritPoints FROM demerits WHERE MemberId = $MemberId AND Archived = 0 ORDER BY DemeritDate desc";
            $demerits_result = mysqli_query($conn, $demerits_query);

            // Cumulative Points
            $pointsdemerits_sql = "SELECT SUM(DemeritPoints) AS CumulativePoints FROM demerits WHERE MemberId = $MemberId AND Archived = 0";
            $pointsdemerits_query = $conn->query($pointsdemerits_sql);
            $demerit_count = mysqli_fetch_assoc($pointsdemerits_query);

            // Image
            $imagePath = realpath(__DIR__ . '/../images/bhsfblalogo-pdf.PNG');
            if (file_exists($imagePath)) {
                $pdf->Image($imagePath, 10, 5, 45);
                $pdf->Ln(5);
            }

            // Header
            $html = '
                <h4 style="font-size: 14px; line-height: 10px; text-align: center;">' . $chapter['ChapterName'] . '</h4>
            ';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(15);

            if ($member['SecondaryContactName']) {
                $SecondaryContactExists = ', '.htmlspecialchars($member['SecondaryContactName']);
            } else {
                $SecondaryContactExists = '';
            }

            // Member Data
            $html = '
                <p style="font-weight: bold; font-size: 11px; line-height: 4px;">To the Member & Parent/Guardian:</p>
                <p style="font-size: 11px; line-height: 4px;">'.htmlspecialchars($member['FirstName']).' '.htmlspecialchars($member['LastName']).', '.htmlspecialchars($member['PrimaryContactName']).''.htmlspecialchars($SecondaryContactExists).'</p>
                <p style="font-size: 11px; line-height: 4px;">Grade '.htmlspecialchars($member['GradeLevel']).'</p>
                <p style="font-size: 11px; line-height: 4px;">Date Reported: ' . date("m/d/Y") . '</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(12);

            // Additional Text
            $html = '
                <p style="font-size: 11px; line-height: 18px;">Attached is a comprehensive report outlining the demerits issued. Please note that if a member accumulates a total of ' . $max_demerits . '  demerit points, they will enter a 60-day probation period.</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(8);

            // Demerits Table
            $htmlTable = '
                <table style="font-size: 11px; width: 100%; padding: 4px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 4px; border-bottom: 0.8px solid #b9b9b9;"><b>Issued Date</b></th>
                            <th style="text-align: left; padding: 4px; border-bottom: 0.8px solid #b9b9b9;"><b>Demerit</b></th>
                            <th style="text-align: left; padding: 4px; border-bottom: 0.8px solid #b9b9b9;"><b>Description</b></th>
                            <th style="text-align: center; padding: 4px; border-bottom: 0.8px solid #b9b9b9;"><b>Points</b></th>
                        </tr>
                    </thead>
                    <tbody>';
                    while ($demerit = mysqli_fetch_assoc($demerits_result)) {
                        $htmlTable .= '
                            <tr>
                                <td style="padding: 4px; border-bottom: 1px solid #c8c8c8;">' . date("m/d/Y", strtotime($demerit['DemeritDate'])) . '</td>
                                <td style="padding: 4px; border-bottom: 1px solid #c8c8c8;">' . $demerit['Demerit'] . '</td>
                                <td style="padding: 4px; border-bottom: 1px solid #c8c8c8;">' . $demerit['DemeritDescription'] . '</td>
                                <td style="text-align: center; padding: 4px; border-bottom: 1px solid #c8c8c8;">' . $demerit['DemeritPoints'] . '</td>
                            </tr>';
                    }
                $htmlTable .= '
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right; padding: 4px; border-top: 1px solid #c8c8c8;"><b>Total Points:</b></td>
                            <td style="text-align: center; padding: 4px; border-top: 1px solid #c8c8c8;">' . $demerit_count['CumulativePoints'] . '</td>
                        </tr>
                    </tfoot>
                </table>';
            $pdf->writeHTML($htmlTable, true, false, false, false, '');
        } elseif ($reportType == 'memberprofile') {
            // Image
            $imagePath = realpath(__DIR__ . '/../images/bhsfblalogo-pdf.PNG');
            if (file_exists($imagePath)) {
                $pdf->Image($imagePath, 10, 5, 45);
                $pdf->Ln(5);
            }

            // Header
            $html = '
                <h4 style="font-size: 14px; line-height: 10px; text-align: center;">' . $chapter['ChapterName'] . '</h4>
            ';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(5);

            // Member Image
            $memberImage = realpath(__DIR__ . '/../../MemberPhotos/' . $member['MemberPhoto']);
            if (file_exists($memberImage)) {
                $imgWidth = 20;
                $imgX = 20;
                $imgY = $pdf->GetY();

                list($origWidth, $origHeight) = getimagesize($memberImage);
                $imgHeight = ($imgWidth / $origWidth) * $origHeight;
                $pdf->Image($memberImage, $imgX, $imgY, $imgWidth, $imgHeight);
                $pdf->Ln($imgHeight + 2);
            }

            // Member Data
            $htmlTable = '
                <table style="font-size: 9px; width: 100%; padding: 6px;">
                    <tbody>';
                    $htmlTable .= '
                        <tr>
                            <td colspan="2" style="padding: 6px; border-bottom: 0.8px solid #b9b9b9;"><b>Member Demographics</b></td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Member Name (Last, First Suffix):</b></td>
                            <td style="padding: 6px;">'. $member['LastName'] . ', '. $member['FirstName'] . ' '. $member['Suffix'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Email Address:</b></td>
                            <td style="padding: 6px;">'. $member['EmailAddress'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Cell Phone #:</b></td>
                            <td style="padding: 6px;">'. $member['CellPhone'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Grade Level:</b></td>
                            <td style="padding: 6px;">'. $member['GradeLevel'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Birthdate:</b></td>
                            <td style="padding: 6px;">'. date("m/d/Y", strtotime($member['Birthdate'])) . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Gender:</b></td>
                            <td style="padding: 6px;">'. $member['Gender'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Ethnicity:</b></td>
                            <td style="padding: 6px;">'. $member['Ethnicity'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>T-shirt Size:</b></td>
                            <td style="padding: 6px;">'. $member['ShirtSize'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Address:</b></td>
                            <td style="padding: 6px;">'. $member['Street'] . '<br>'. $member['City'] . ', '. $member['State'] . ' '. $member['Zip'] . '</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding: 6px; border-bottom: 0.8px solid #b9b9b9;"><b>Contacts</b></td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Primary Contact Name:</b></td>
                            <td style="padding: 6px;">'. $member['PrimaryContactName'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Phone:</b></td>
                            <td style="padding: 6px;">'. $member['PrimaryContactPhone'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Email:</b></td>
                            <td style="padding: 6px;">'. $member['PrimaryContactEmail'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Secondary Contact Name:</b></td>
                            <td style="padding: 6px;">'. $member['SecondaryContactName'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Phone:</b></td>
                            <td style="padding: 6px;">'. $member['SecondaryContactPhone'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Email:</b></td>
                            <td style="padding: 6px;">'. $member['SecondaryContactEmail'] . '</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding: 6px; border-bottom: 0.8px solid #b9b9b9;"><b>Membership</b></td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>School:</b></td>
                            <td style="padding: 6px;">'. $member['School'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Membership Year:</b></td>
                            <td style="padding: 6px;">'. $member['MembershipYear'] . '</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px;"><b>Membership Tier:</b></td>
                            <td style="padding: 6px;">'. $member['MembershipTier'] . '</td>
                        </tr>';
                $htmlTable .= '
                    </tbody>
                </table>';
                $pdf->writeHTML($htmlTable, true, false, false, false,'');
        } elseif ($reportType == 'portalletter') {
            // Image
            $imagePath = realpath(__DIR__ . '/../images/bhsfblalogo-pdf.PNG');
            if (file_exists($imagePath)) {
                $pdf->Image($imagePath, 10, 5, 45);
                $pdf->Ln(5);
            }

            // Header
            $html = '
                <h4 style="font-size: 14px; line-height: 10px; text-align: center;">' . $chapter['ChapterName'] . '</h4>
            ';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(15);

            // Letter Data
            $html = '
                <p style="font-size: 11px;">Dear '.htmlspecialchars($member['FirstName']).' '.htmlspecialchars($member['LastName']).',</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(5);

            $html = '
                <p style="font-size: 11px; line-height: 25px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $chapter['ChapterName'] . ' chapter is excited to give you access to your membership account online. You will be able to check your attendance, demerits, and community service records. To get this information, just follow these steps:</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(8);

            // Steps
            $html = '
                <ol>
                    <li style="font-size: 11px; line-height: 20px;">Open an internet browser on any web accessible device.</li>
                    <li style="font-size: 11px; line-height: 20px">Type https://berkeleyfblamembership.com/public in the address bar of your browser. Choose "Create Account" to start the process.</li>
                    <li style="font-size: 11px; line-height: 20px">To create your account, enter the registration key found below and choose a username and password that you want to use.</li>
                    <li style="font-size: 11px; line-height: 20px">When you\'re done, type in the username and password you used on the "Create Account" page.</li>
                </ol>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(10);

            // Registration Key
            $html = '
                <p style="font-size: 11px;">Registration Key: <b>'.$member['RegistrationKey'].'<b></p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(h: 8);

            // Thanks Message
            $html = '
                <p style="font-size: 11px; line-height: 20px;">If you have any questions before or during this process, please reach out to your membership coordinator at montezbhsfbla@gmail.com for help.</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(5);

            $html = '
                <p style="font-size: 11px; line-height: 20px;">Thanks,
                    <br>' . $chapter['ChapterName'] . '</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
        } elseif ($reportType == 'probationletter') {
            // Current Probation DB
            $current_probation_query = "SELECT * FROM probation WHERE MemberId = $MemberId AND EndDate >= CURDATE()";
            $current_probation_result = $conn->query($current_probation_query);
            $probation = mysqli_fetch_assoc($current_probation_result);

            $conditionsList = "";
            $conditionsForRemoval = $probation['ConditionsForRemoval'] ?? '[]'; // Just in case it's null
            
            $conditionsArray = json_decode($conditionsForRemoval, true);
            if (is_array($conditionsArray) && count(array_filter($conditionsArray))) {
                $conditionsList = "<ul>";
                foreach ($conditionsArray as $condition) {
                    $conditionsList .= "<li>" . htmlspecialchars($condition) . "</li>";
                }
                $conditionsList .= "</ul>";
            } else {
                $conditionsList = "<p>No conditions set for removal.</p>";
            }

            // Image
            $imagePath = realpath(__DIR__ . '/../images/bhsfblalogo-pdf.PNG');
            if (file_exists($imagePath)) {
                $pdf->Image($imagePath, 10, 5, 45);
                $pdf->Ln(5);
            }

            // Header
            $html = '
                <h4 style="font-size: 14px; line-height: 10px; text-align: center;">' . $chapter['ChapterName'] . '</h4>
            ';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(15);

            if ($member['SecondaryContactName']) {
                $SecondaryContactExists = ', '.htmlspecialchars($member['SecondaryContactName']);
            } else {
                $SecondaryContactExists = '';
            }

            // Member Data
            $html = '
                <p style="font-weight: bold; font-size: 11px; line-height: 4px;">To the Member & Parent/Guardian:</p>
                <p style="font-size: 11px; line-height: 4px;">'.htmlspecialchars($member['FirstName']).' '.htmlspecialchars($member['LastName']).', '.htmlspecialchars($member['PrimaryContactName']).''.htmlspecialchars($SecondaryContactExists).'</p>
                <p style="font-size: 11px; line-height: 4px;">Grade '.htmlspecialchars($member['GradeLevel']).'</p>
                <p style="font-size: 11px; line-height: 4px;">Date Reported: ' . date("m/d/Y") . '</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(12);

            $probationStatus = $current_probation_result->num_rows > 0;
            if ($probationStatus) {
                // Letter Data
                $html = '
                    <p style="font-size: 11px;">Dear '.htmlspecialchars($member['FirstName']).' '.htmlspecialchars($member['LastName']).',</p>';
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Ln(5);

                $html = '
                    <p style="font-size: 11px; line-height: 25px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;You have been placed on a 60-day probation period from <b>'.date("n/j/Y", strtotime($probation['StartDate'])).' to '.date("n/j/Y", strtotime($probation['EndDate'])).' due to the accumulation of six or more demerits</b>. As a member, you are still expected to attend chapter meetings and participate in community service and fundraising events; however, you are not permitted to attend celebrations or activities during this time. The following expectations that must be met for your probation are below:</p>';
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Ln(8);

                $html = '
                    <p style="font-size: 11px; line-height: 12px;"><b>Probation reason:</b> '.$probation['ProbationReason'].'</p>
                    <div style="font-size: 11px; line-height: 18px;">'.$conditionsList.'</div>';
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Ln(0);

                $html = '
                    <hr><div style="font-size: 11px; line-height: 25px;">Failure to adhere to these conditions may result in an extension of your probation period. For additional inquiries, you may contact SmalleyS@bcsdschools.net. Additionally, you can monitor your membership portal for updates as they arise.</div>';
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Ln(0);
            } else {
                $html = '
                    <p style="font-size: 11px; line-height: 20px;">No letter to be generated</p>';
                $pdf->writeHTML($html, true, false, false, false, '');
            }
        } elseif ($reportType == 'terminationletter') {
            // Termination DB
            $termination_query = "SELECT * FROM termination WHERE MemberId = $MemberId ORDER BY TerminationDate desc LIMIT 1";
            $termination_result = $conn->query($termination_query);
            $termination = mysqli_fetch_assoc($termination_result);

            // Image
            $imagePath = realpath(__DIR__ . '/../images/bhsfblalogo-pdf.PNG');
            if (file_exists($imagePath)) {
                $pdf->Image($imagePath, 10, 5, 45);
                $pdf->Ln(5);
            }

            // Header
            $html = '
                <h4 style="font-size: 14px; line-height: 10px; text-align: center;">' . $chapter['ChapterName'] . '</h4>
            ';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(15);

            if ($member['SecondaryContactName']) {
                $SecondaryContactExists = ', '.htmlspecialchars($member['SecondaryContactName']);
            } else {
                $SecondaryContactExists = '';
            }

            // Member Data
            $html = '
                <p style="font-weight: bold; font-size: 11px; line-height: 4px;">To the Member & Parent/Guardian:</p>
                <p style="font-size: 11px; line-height: 4px;">'.htmlspecialchars($member['FirstName']).' '.htmlspecialchars($member['LastName']).', '.htmlspecialchars($member['PrimaryContactName']).''.htmlspecialchars($SecondaryContactExists).'</p>
                <p style="font-size: 11px; line-height: 4px;">Grade '.htmlspecialchars($member['GradeLevel']).'</p>
                <p style="font-size: 11px; line-height: 4px;">Date Reported: ' . date("m/d/Y") . '</p>';
            $pdf->writeHTML($html, true, false, false, false, '');
            $pdf->Ln(12);

            $terminationStatus = $termination_result->num_rows > 0;
            if ($terminationStatus) {
                // Letter Data
                $html = '
                    <p style="font-size: 11px;">Dear '.htmlspecialchars($member['FirstName']).' '.htmlspecialchars($member['LastName']).',</p>';
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Ln(5);

                $html = '
                    <p style="font-size: 11px; line-height: 25px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Your membership in the ' . $chapter['ChapterName'] . ' chapter has been officially terminated as of '.date("n/j/Y", strtotime($termination['TerminationDate'])).'. As a result, you are no longer a member of our chapter. Below, you will find the reasons for your termination. Should you have any further questions or concerns, please do not hesitate to contact <a href="mailto:SmalleyS@bcsdschools.net">SmalleyS@bcsdschools.net</a>.</p>';
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Ln(8);

                $html = '
                    <p style="font-size: 11px; line-height: 12px;"><b>Termination reason:</b> '.$termination['TerminationReason'].'</p>';
                $pdf->writeHTML($html, true, false, false, false, '');
                $pdf->Ln(10);

                $html = '
                    <p style="font-size: 11px; line-height: 20px;">Thanks,
                        <br>' . $chapter['ChapterName'] . '</p>';
                $pdf->writeHTML($html, true, false, false, false, '');
            } else {
                $html = '
                    <p style="font-size: 11px; line-height: 20px;">No letter to be generated</p>';
                $pdf->writeHTML($html, true, false, false, false, '');
            }
        }

        // Save PDF if requested
        if ($savePdf == 'yes') {
            $pdf->Output(''.$member['FirstName'].' '.$member['LastName'].' - '.$ReportName.'.pdf', 'D');
        } else {
            $pdf->Output();
        }
    } else {
        header("HTTP/1.0 404 Not Found");
        exit();
    }