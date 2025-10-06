<?php
    $adminId = $_SESSION['account_id'];
    $current_id = $_GET['id'] ?? null;

    // Quick Select & Toggle Between Members
    $qs_query = "SELECT m.MemberId, m.LastName, m.FirstName, m.Suffix, m.GradeLevel 
        FROM quick_select q 
        INNER JOIN members m ON q.MemberId = m.MemberId 
        WHERE q.AdminId = $adminId 
        AND q.AddedOn >= NOW() - INTERVAL 1 DAY 
        ORDER BY m.LastName asc, m.FirstName asc ";
    $qs_result = $conn->query($qs_query);

    if ($qs_result && $qs_result->num_rows > 0) {
        $toggle_members = $qs_result->fetch_all(MYSQLI_ASSOC);
    } else {
        $toggle_query = "
            SELECT MemberId, LastName, FirstName, Suffix, GradeLevel 
            FROM members 
            WHERE MemberStatus IN (1, 2) 
            ORDER BY LastName ASC, FirstName ASC
        ";
        $toggle_result = $conn->query($toggle_query);
        $toggle_members = $toggle_result->fetch_all(MYSQLI_ASSOC);
    }

    $index = array_search($current_id, array_column($toggle_members, 'MemberId'));
    $total_members = count($toggle_members);

    if ($total_members > 1) {
        $prev_id = ($index > 0) 
            ? $toggle_members[$index - 1]['MemberId'] 
            : $toggle_members[$total_members - 1]['MemberId'];

        $next_id = ($index < $total_members - 1) 
            ? $toggle_members[$index + 1]['MemberId'] 
            : $toggle_members[0]['MemberId'];

        $current_position = ($index !== false) ? $index + 1 : 1;
    }
    
    // Demerits DB
    $cumulative_demerits_query = "SELECT SUM(DemeritPoints) AS CumulativePoints FROM demerits WHERE MemberId = $getMemberId AND Archived = 0";
    $cumulative_demerits = $conn->query($cumulative_demerits_query);
    $TotalDemeritPoints = mysqli_fetch_assoc($cumulative_demerits);

    // Probation Alert DB
    $probation_alert_query = "SELECT * FROM probation WHERE MemberId = $getMemberId AND ProbationStatus = 1";
    $probation_alert_result = $conn->query($probation_alert_query);
    $probation_alert = mysqli_fetch_assoc($probation_alert_result);

    $probationAlert = $probation_alert_result->num_rows > 0;
    if ($probationAlert) {
        $probationLevel = $probation_alert['ProbationLevel'];
        $probationStartDate = date('n/j/Y', strtotime($probation_alert['StartDate']));
        $probationEndDate = date('n/j/Y', strtotime($probation_alert['EndDate']));
    }
?>
<div class="member-header">
    <div class="photo-wrapper" onclick="openModal()">
        <?php
            $imgSrc = $MemberPhoto ? "../../MemberPhotos/$MemberPhoto" : "../images/noprofilepic.jpeg";
            $altText = "$LastName, $FirstName photo";
            echo "<img src='$imgSrc' alt='$altText' class='photo' title='$altText'>";
        ?>
    </div>
    <div class="member-info">
        <div class="member-switch">
            <button class="dropbtn" onclick="toggleDropdown()"><?php echo $LastName . ', ' . $FirstName . ' ' . $Suffix; ?> <img src="../images/caret-down-solid.svg" alt="Down arrow icon"></button>
            <div class="dropdown-content">
                <input type="text" id="switch-search" class="search-box" onkeyup="filterMembers()" placeholder="Search members">
                <?php
                    foreach ($toggle_members as $member) {
                        if ($member['MemberId'] != $current_id) {
                            echo '<a href="?id=' . $member['MemberId'] . '" class="member-item">' . $member['LastName'] . ', ' . $member['FirstName'] . ' ' . $member['Suffix'] . ' (' . $member['GradeLevel'] . ')</a>';
                        }
                    }
                ?>
            </div>
        </div>
        <span class="member-details">
            <?php
                echo 'Grade: ' . $GradeLevel .'';
                echo '&nbsp;&nbsp;&nbsp;&nbsp;';
                echo 'Membership: ' . $MembershipTier . '';
                echo '&nbsp;&nbsp;&nbsp;&nbsp;';
                echo 'Status: ';
                switch ($MemberStatus) {
                    case 1: echo 'Active'; break;
                    case 2: echo 'Probation'; break;
                    case 3: echo 'Terminated'; break;
                    case 4: echo 'Dropped'; break;
                    case 5: echo 'Alumni'; break;
                    case 6: echo 'Pre-Registered'; break;
                }
                echo '&nbsp;&nbsp;&nbsp;&nbsp;';
                if ($Position) {
                    echo '<span class="alert"><img src="../images/dot.gif" class="icon16 user-gray-icon">&nbsp;'.$Position.'</span>&nbsp;&nbsp;&nbsp;&nbsp;';
                }
                if (!empty($Birthdate) && strtotime($Birthdate) !== false) {
                    if (date('m-d') == date('m-d', strtotime($Birthdate))) {
                        echo '<img src="../images/dot.gif" class="icon16 cake-icon" alt="Birthday alert" title="Today is ' . htmlspecialchars($FirstName) . '\'s Birthday">&nbsp;&nbsp;&nbsp;&nbsp;';
                    }
                }
                if ($cumulative_demerits->num_rows AND $TotalDemeritPoints['CumulativePoints'] >= 1) {
                    echo '<span title="'.$TotalDemeritPoints['CumulativePoints'].' accumulated demerit points."><img src="../images/dot.gif" class="icon16 error-icon"></span>&nbsp;&nbsp;&nbsp;&nbsp;';
                }
                if ($probationAlert) {
                    echo '<span title="('.$probationLevel.') From '.$probationStartDate.' to '.$probationEndDate.'"><img src="../images/dot.gif" class="icon16 bell-go-icon"></span>';
                } elseif ($MemberStatus == 2) {
                    echo '<span title="Probation status"><img src="../images/dot.gif" class="icon16 bell-icon"></span>';
                }
            ?>
        </span>
    </div>
    <?php if ($total_members > 1): ?>
        <div>
            <a href="?id=<?= $prev_id ?>">← Prev</a>
            &nbsp;
            <?php echo " $current_position of $total_members "; ?>
            &nbsp;
            <a href="?id=<?= $next_id ?>">Next →</a>
        </div>
    <?php endif; ?>
</div>
<div id="photoModal" class="modal" onclick="closeModal()">
    <span class="close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>
<script>
    function openModal() {
        const modal = document.getElementById("photoModal");
        const modalImg = document.getElementById("modalImage");
        const photo = document.querySelector(".photo");

        modal.style.display = "block";
        modalImg.src = photo.src;
        modalImg.alt = photo.alt;
    }

    function closeModal() {
        document.getElementById("photoModal").style.display = "none";
    }
    
    function toggleDropdown() {
        var dropdownContent = document.querySelector('.dropdown-content');
        dropdownContent.style.display = (dropdownContent.style.display === 'block') ? 'none' : 'block';
    }

    window.onclick = function(event) {
        if (!event.target.matches('.dropbtn') && !event.target.matches('.dropdown-content') && !event.target.matches('.search-box')) {
            var dropdowns = document.querySelectorAll('.dropdown-content');
            for (var i = 0; i < dropdowns.length; i++) {
                dropdowns[i].style.display = 'none';
            }
        }
    }

    function filterMembers() {
        var input = document.getElementById('switch-search');
        var filter = input.value.toUpperCase();
        var items = document.querySelectorAll('.dropdown-content .member-item');

        items.forEach(function(item) {
            var text = item.textContent || item.innerText;
            if (text.toUpperCase().indexOf(filter) > -1) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }
</script>
