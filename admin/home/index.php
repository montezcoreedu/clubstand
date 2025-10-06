<?php
    include("../../dbconnection.php");
    include("../common/session.php");
    include("../common/permissions.php");

    // Members DB
    $query = "SELECT MemberId, LastName, FirstName, Suffix, GradeLevel, MembershipTier, EmailAddress, MemberPhoto FROM members WHERE MemberStatus IN (1, 2, 6) ORDER BY LastName asc, FirstName asc";
    $result = mysqli_query($conn, $query);
    $members = mysqli_fetch_all($result, MYSQLI_ASSOC);

    // Recent Searches DB
    $recentSearchesQuery = "SELECT m.MemberId, m.LastName, m.FirstName, m.Suffix, m.EmailAddress, m.MemberPhoto FROM recent_searches r INNER JOIN members m ON r.MemberId = m.MemberId WHERE r.AdminId = $accountId AND r.SearchTime >= NOW() - INTERVAL 1 DAY ORDER BY r.SearchTime desc, m.LastName asc, m.FirstName asc";
    $recentSearchesResult = mysqli_query($conn, $recentSearchesQuery);

    // Quick Select
    $qs_query = "SELECT q.SelectId, m.MemberId, m.LastName, m.FirstName, m.Suffix, m.EmailAddress, m.GradeLevel, m.MembershipTier, m.MemberPhoto FROM quick_select q INNER JOIN members m ON q.MemberId = m.MemberId WHERE q.AdminId = $accountId AND q.AddedOn >= NOW() - INTERVAL 1 DAY ORDER BY m.LastName asc, m.FirstName asc";
    $qs_result = $conn->query($qs_query);
    $qs_count = $qs_result->num_rows;

    // Birthday Query
    $birthdays_query = "SELECT MemberId, LastName, FirstName, Suffix, Birthdate, MemberPhoto FROM members WHERE MONTH(DATE(Birthdate)) = MONTH(CURDATE()) AND MemberStatus IN (1, 2, 6) ORDER BY DAYOFMONTH(DATE(Birthdate)) asc, LastName asc, FirstName asc";
    $birthdays_result = mysqli_query($conn, $birthdays_query);

    // Grade Level Trends
    $levels_trend_result = $conn->query($query);
    $grade9Count = 0;
    $grade10Count = 0;
    $grade11Count = 0;
    $grade12Count = 0;

    // At Risk Query
    $at_risk_query = "SELECT
                        SUM(CASE
                            WHEN COALESCE(a.absent_tardy_count, 0) > 6
                            OR COALESCE(ms.total_service_hours, 0) <= 7
                            OR COALESCE(d.total_demerit_points, 0) >= 7
                            THEN 1 ELSE 0 END
                        ) AS at_risk_count,
                        COUNT(*) AS total_members
                    FROM members m
                    LEFT JOIN (
                        SELECT MemberId, COUNT(*) AS absent_tardy_count
                        FROM attendance
                        WHERE Status IN ('Absent', 'Tardy')
                        AND Archived = 0
                        GROUP BY MemberId
                    ) a ON m.MemberId = a.MemberId
                    LEFT JOIN (
                        SELECT MemberId, SUM(ServiceHours) AS total_service_hours
                        FROM memberservicehours
                        WHERE Archived = 0
                        GROUP BY MemberId
                    ) ms ON m.MemberId = ms.MemberId
                    LEFT JOIN (
                        SELECT MemberId, SUM(DemeritPoints) AS total_demerit_points
                        FROM demerits
                        WHERE Archived = 0
                        GROUP BY MemberId
                    ) d ON m.MemberId = d.MemberId
                    WHERE m.MemberStatus IN (1, 2)";
    $at_risk_result = $conn->query($at_risk_query);
    $atRisk = $notAtRisk = 0;
    if ($at_risk_result && $row = $at_risk_result->fetch_assoc()) {
        $atRisk = (int) $row['at_risk_count'];
        $total = (int) $row['total_members'];
        $notAtRisk = $total - $atRisk;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>ClubStand Membership Cloud</title>
    <?php include("../common/head.php"); ?>
    <script>
        function addToQuickSelect(MemberId, event) {
            if (event) event.preventDefault();

            fetch('../quickselect/addmembermanual.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: MemberId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateQuickSelectCounter(data.count);
                    
                    document.querySelector(`#quickselect-${MemberId}`).innerHTML = `
                        <a href="#" onclick="removeFromQuickSelect(${MemberId}, event)">
                            <img src="../images/dot.gif" class="icon14 minus-icon" alt="Remove from Quick Select icon" title="Remove from Quick Select" style="display: flex;">
                        </a>
                    `;
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
            });
        }

        function removeFromQuickSelect(SelectId, event) {
            if (event) event.preventDefault();

            fetch('../quickselect/removemembermanual.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ select_id: SelectId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateQuickSelectCounter(data.count);

                    const row = document.querySelector(`#quickselect-row-${SelectId}`);
                    if (row) {
                        row.innerHTML = `
                            <a href="#" onclick="addToQuickSelect(${data.member_id}, event)">
                                <img src="../images/dot.gif" class="icon14 plus-icon" alt="Add to Quick Select icon" title="Add to Quick Select" style="display: flex;">
                            </a>
                        `;
                    }
                } else {
                    alert('Error removing: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
            });
        }

        function updateQuickSelectCounter(count) {
            const quickSelectLink = document.querySelector('a[href="../quickselect/"] span');
            if (quickSelectLink) {
                quickSelectLink.textContent = `Quick Select (${count})`;
            }
        }
    </script>
</head>
<body>
    <?php include("../common/top-navbar.php"); ?>
    <div id="main-wrapper">
        <h2 class="welcome-user">Welcome, <?php echo $_SESSION['FirstName']; ?>!</h2>
        <div class="quick-links">
            <?php if (!empty(array_intersect(['Import Members','Assign Officers','Returning Members Form','Store Membership Data','Transition Members','Clear Records'], $userPermissions))): ?>
                <a href="../manage/" class="quick-link">
                    <i class="fa-solid fa-users"></i>
                    <span>Manage Membership</span>
                </a>
            <?php endif; ?>
            <?php if (in_array('Progress Report', $userPermissions)): ?>
                <a href="progress_report.php" class="quick-link">
                    <i class="fa-solid fa-bars-progress"></i>
                    <span>Progress Report</span>
                </a>
            <?php endif; ?>
            <?php if (in_array('Accounts Security', $userPermissions)): ?>
                <a href="../accounts/" class="quick-link">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                    <span>Accounts & Security</span>
                </a>
            <?php endif; ?>
            <?php if (in_array('Chapter Settings', $userPermissions)): ?>
                <a href="settings.php" class="quick-link">
                    <i class="fa-solid fa-gears"></i>
                    <span>Chapter Settings</span>
                </a>
            <?php endif; ?>
        </div>
        <div id="search-container">
            <div id="search-tags-container">
                <input type="text" id="search-input" placeholder="Search Members">
                <button type="button" id="search-button"><i class="fa-solid fa-magnifying-glass"></i></button>
                <a href="javascript:void(0);" class="btn-link" onclick="openFieldList()" title="View Field List"><i class="fa-regular fa-circle-question"></i></a>
                <button type="button" id="clear-tags">Clear All</button>
            </div>
            <form id="search-form" method="GET" action="search.php">
                <input type="hidden" name="query" id="search-query">
            </form>
        </div>
        <div class="browse-box">
            <!-- Browse by Last Name -->
            <div class="browse-section">
                <?php foreach (range('A', 'Z') as $letter): ?>
                    <a href="browse.php?LastName=<?= $letter ?>"><?= $letter ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Browse by Filters -->
            <div class="browse-section">
                <a href="browse.php?GradeLevel=9">9</a>
                <a href="browse.php?GradeLevel=10">10</a>
                <a href="browse.php?GradeLevel=11">11</a>
                <a href="browse.php?GradeLevel=12">12</a>
                <a href="browse.php?Gender=Female">F</a>
                <a href="browse.php?Gender=Male">M</a>
                <a href="browse.php?Officers=true">Officers</a>
                <a href="browse.php?Alumni=true">Alumni</a>
            </div>

            <!-- Add/Quick Access Links -->
            <div class="browse-section">
                <a href="../members/add.php" style="display: inline-flex; align-items: center;">
                    <img src="../images/dot.gif" class="icon14 user-add-icon" alt="Add Member icon">&nbsp;Add Member
                </a>
                <a href="../quickselect/" style="display: inline-flex; align-items: center;">
                    <img src="../images/dot.gif" class="icon14 advanced-search-icon" alt="Quick Select icon">
                    &nbsp;Quick Select (<?= $qs_count; ?>)
                </a>
            </div>
        </div>
        <div id="active-tags"></div>
        <div class="recent-searches" id="recent-searches-section">
            <h3><i class="fa-solid fa-magnifying-glass"></i>&nbsp;&nbsp;Recent Searches</h3>
            <div class="wrapper">
                <?php
                    if ($recentSearchesResult->num_rows) {
                        echo '<table>';
                            echo '<tbody>';
                            while ($search = $recentSearchesResult->fetch_assoc()) {
                                $memberPhoto = !empty($search['MemberPhoto'])
                                    ? "<img src='../../MemberPhotos/{$search['MemberPhoto']}' alt='Member Photo' class='member-photo'>"
                                    : "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";

                                echo "<tr>
                                        <td><a href='../members/lookup.php?id={$search['MemberId']}' class='member-name'>{$memberPhoto} {$search['LastName']}, {$search['FirstName']} {$search['Suffix']}</a></td>
                                        <td align='right'><a href='mailto:{$search['EmailAddress']}'>{$search['EmailAddress']}</a></td>
                                    </tr>";
                            }
                            echo '</tbody>';
                        echo '</table>';
                    } else {
                        echo '<div class="message comment">No searches recently made yet.</div>';
                    }
                ?>
            </div>
        </div>
        <table class="members-table" id="search-results" style="display: none;">
            <thead>
                <th></th>
                <th align="left">Member Name</th>
                <th align="left">Grade Level</th>
                <th align="left">Membership</th>
                <th align="left">Email Address</th>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <div id="side-drawer">
        <div class="drawer-content">
            <div class="card">
                <span class="title"><?php echo ''.date('F').''; ?> Birthdays</span>
                <div class="content">
                    <?php
                        if ($birthdays_result->num_rows) {
                            echo '<table>';
                                echo '<tbody>';
                                while ($row = $birthdays_result->fetch_assoc()) {
                                    $Birthday = date("M j", strtotime($row['Birthdate']));
                                    
                                    if (!empty($row['MemberPhoto'])) {
                                        $memberPhoto = "<img src='../../MemberPhotos/{$row['MemberPhoto']}' alt='Member Photo' class='member-photo'>";
                                    } else {
                                        $memberPhoto = "<img src='../images/noprofilepic.jpeg' alt='Member Photo' class='member-photo'>";
                                    }
                                
                                    if ($Birthday == date('M j')) {
                                        $checkBirthday = '<img src="../images/dot.gif" class="icon14 cake-icon" alt="Birthday icon">&nbsp;&nbsp;<b>' . $Birthday . '</b>';
                                    } else {
                                        $checkBirthday = $Birthday;
                                    }
                                
                                    echo "<tr>
                                            <td>{$checkBirthday}</td>
                                            <td>
                                                <a href='../members/lookup.php?id={$row['MemberId']}' class='member-name'>
                                                    {$memberPhoto}
                                                    {$row['FirstName']} {$row['LastName']} {$row['Suffix']}
                                                </a>
                                            </td>
                                        </tr>";
                                }
                                echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<span>No birthdays this month.</span>';
                        }
                    ?>
                </div>
            </div>
            <div class="card">
                <span class="title">Grade Level Breakdown</span>
                <div class="content">
                    <?php
                        while ($level = $levels_trend_result->fetch_assoc()) {
                            $gradeLevel = $level['GradeLevel'];
                    
                            if ($gradeLevel == '9') {
                                $grade9Count++;
                            } elseif ($gradeLevel == '10') {
                                $grade10Count++;
                            } elseif ($gradeLevel == '11') {
                                $grade11Count++;
                            } elseif ($gradeLevel == '12') {
                                $grade12Count++;
                            }
                        }
                    
                        $levels = [$grade9Count, $grade10Count, $grade11Count, $grade12Count];
                        $levelLabels = ['9th Grade', '10th Grade', '11th Grade', '12th Grade'];
                        $levelsJSON = json_encode($levels);
                        $levelLabelsJSON = json_encode($levelLabels);

                        if ($result->num_rows) {
                            echo '<div><canvas id="gradeBreakdown"></canvas></div>';
                        } else {
                            echo '<span>No active members.</span>';
                        }
                    ?>
                </div>
            </div>
            <div class="card">
                <span class="title">At Risk Members</span>
                <div class="content">
                    <?php
                        if ($result->num_rows) {
                            echo '<div><canvas id="atRiskChart"></canvas></div>';
                        } else {
                            echo '<span>No active members.</span>';
                        }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function openFieldList() {
            const fieldListWindow = window.open('', 'View Field List', 'width=350,height=400,resizable,scrollbars');

            // Add CSS styles to the new window
            fieldListWindow.document.write(`
                <style>
                    @font-face {
                        font-family: avenir-regular;
                        src: url(../../fonts/avenir-regular.otf);
                    }

                    @font-face {
                        font-family: avenir-bold;
                        src: url(../../fonts/avenir-bold.otf);
                    }

                    body {
                        font-family: avenir-regular, sans-serif;
                        font-size: 0.920rem;
                        color: rgb(48, 48, 48);
                        line-height: 1.4;
                        padding: 1rem 3rem;
                        margin: 0;
                    }

                    *, ::after, ::before {
                        box-sizing: border-box;
                        -webkit-box-sizing: border-box;
                        -moz-box-sizing: border-box;
                        font-synthesis: none;
                        text-rendering: optimizeLegibility;
                    }

                    h3 {
                        font-family: avenir-bold, sans-serif;
                        margin-bottom: 10px;
                    }

                    ol {
                        padding: 0;
                    }

                    li {
                        margin-bottom: 10px;
                    }

                    a {
                        color: rgb(10, 46, 127);
                        text-decoration: none;
                        cursor: pointer;
                    }

                    a:hover {
                        color: rgb(18, 64, 93);
                        text-decoration: underline;
                    }

                    button {
                        font-family: inherit;
                        font-size: 0.860rem;
                        color: rgb(255, 255, 255);
                        padding: 4px 8px;
                        background-color: rgb(0, 106, 172);
                        border-radius: 4px;
                        border: none;
                        cursor: pointer;
                    }

                    button:hover {
                        color: rgb(255, 255, 255);
                        background-color: rgb(0, 91, 148);
                    }
                </style>
            `);

            fetch('field_list.php')
                .then(response => response.json())
                .then(fields => {
                    fieldListWindow.document.write('<h3>Select a Field</h3>');
                    fieldListWindow.document.write('<ol>');

                    fields.forEach(field => {
                        fieldListWindow.document.write(`
                            <li><a href="javascript:void(0);" onclick="window.opener.selectField('${field}'); window.close();">${field}</a></li>
                        `);
                    });

                    fieldListWindow.document.write('</ol>');

                    fieldListWindow.document.write('<button onclick="window.close()">Close</button>');
                })
                .catch(error => {
                    console.error('Error fetching field list:', error);
                });
        }

        function selectField(fieldName) {
            document.getElementById('search-input').value = fieldName;
        }

        function handleSearchResults(data) {
            const $resultsContainer = $("#search-results tbody");
            const $resultsTable = $("#search-results");
            const $recentSearchesSection = $("#recent-searches-section");

            $("#no-results-message").remove();
            $resultsContainer.empty();

            if (data.message) {
                $resultsContainer.append("<tr><td colspan='4'>" + data.message + "</td></tr>");
                $resultsTable.show();
                $recentSearchesSection.hide();
            } else if (data.length > 0) {
                data.forEach(item => {
                    const resultHTML = `
                        <tr>
                            <td align="center" id="quickselect-${item.MemberId}">
                                ${
                                    item.SelectId
                                    ? `<a href="#" onclick="removeFromQuickSelect(${item.SelectId}, event)" id="quickselect-row-${item.SelectId}">
                                            <img src="../images/dot.gif" class="icon14 minus-icon" alt="Remove from Quick Select icon" title="Remove from Quick Select" style="display: flex;">
                                        </a>`
                                    : `<a href="#" onclick="addToQuickSelect(${item.MemberId}, event)">
                                            <img src="../images/dot.gif" class="icon14 plus-icon" alt="Add to Quick Select icon" title="Add to Quick Select" style="display: flex;">
                                        </a>`
                                }
                            </td>
                            <td>
                                <a href="log_search.php?id=${item.MemberId}" class="member-name">
                                    ${item.MemberPhoto && item.MemberPhoto.trim() !== '' && item.MemberPhoto !== 'undefined' ? 
                                        `<img src="../../MemberPhotos/${item.MemberPhoto}" alt="Member Photo" class="member-photo">` : 
                                        `<img src="../images/noprofilepic.jpeg" alt="Member Photo" class="member-photo">`}
                                    ${item.LastName}, ${item.FirstName} ${item.Suffix}
                                </a>
                            </td>
                            <td>${item.GradeLevel}</td>
                            <td>${item.MembershipTier}</td>
                            <td><a href="mailto:${item.EmailAddress}">${item.EmailAddress}</a></td>
                        </tr>`;

                    $resultsContainer.append(resultHTML);
                });

                $resultsTable.show();
                $recentSearchesSection.hide();
            } else {
                $resultsTable.hide();
                $recentSearchesSection.hide();

                const messageHTML = `<div id="no-results-message" class="message error">No members found based on your search.</div>`;
                $resultsTable.after(messageHTML);
            }
        }

        $("#search-form").submit(function (e) {
            e.preventDefault();
            const query = $("#search-query").val();

            if (query.trim() === "") {
                $("#search-results").hide();
                $("#recent-searches-section").show();
                $("#no-results-message").remove();
            } else {
                $.get("search.php", { query: query }, function (data) {
                    handleSearchResults(data);
                });
            }
        });

        $(document).ready(function () {
            $("#clear-tags").click(function () {
                $("#search-input").val('');
                $("#search-results").hide();
                $("#recent-searches-section").show();
                $("#no-results-message").remove();
            });
        });

        $(document).ready(function () {
            let tags = [];

            function updateTagsDisplay(triggerSearch = true) {
                const $tagContainer = $("#active-tags");
                $tagContainer.empty();

                if (tags.length > 0) {
                    $("#clear-tags").show();
                } else {
                    $("#clear-tags").hide();
                }

                tags.forEach((tag, index) => {
                    const tagHTML = `
                    <span class="tag" data-index="${index}">
                        <span class="tag-text">${tag}</span>
                        <button class="remove-tag" data-index="${index}"><i class="fa-regular fa-circle-xmark"></i></button>
                    </span>`;
                    $tagContainer.append(tagHTML);
                });

                $("#search-query").val(tags.join(" AND "));
                localStorage.setItem("savedSearch", JSON.stringify(tags));

                if (triggerSearch) {
                    $("#search-form").submit();
                }
            }

            function addTagAndSearch(inputVal) {
                if (!inputVal) return;
                tags.push(inputVal);
                updateTagsDisplay();
                $("#search-input").val("");
            }

            $("#search-button").on("click", function () {
                const input = $("#search-input").val().trim();
                addTagAndSearch(input);
            });

            $("#search-input").on("keypress", function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    const input = $(this).val().trim();
                    addTagAndSearch(input);
                }
            });

            $("#active-tags").on("click", ".remove-tag", function () {
                const index = $(this).data("index");
                tags.splice(index, 1);
                updateTagsDisplay();
            });

            $("#clear-tags").on("click", function () {
                tags = [];
                updateTagsDisplay();
            });

            $("#active-tags").on("click", ".tag-text", function () {
                const $span = $(this);
                const index = $span.parent().data("index");
                const currentValue = tags[index];

                const $input = $('<input type="text">').val(currentValue);
                $span.replaceWith($input);
                $input.focus();

                function finishEdit() {
                    const newVal = $input.val().trim();
                    if (newVal) {
                        tags[index] = newVal;
                        updateTagsDisplay();
                    } else {
                        tags.splice(index, 1);
                        updateTagsDisplay();
                    }
                }

                $input.on("keypress", function (e) {
                    if (e.which === 13) {
                        finishEdit();
                    }
                });

                $input.on("blur", function () {
                    finishEdit();
                });
            });

            const saved = localStorage.getItem("savedSearch");
            if (saved) {
                tags = JSON.parse(saved);
                updateTagsDisplay(false);
            }

            window.addEventListener('beforeunload', function () {
                localStorage.removeItem("savedSearch");
            });
        });

        <?php if ($result->num_rows) { ?>
        // Grade Level Breakdown
        var levels = <?php echo $levelsJSON; ?>;
        var levelLabels = <?php echo $levelLabelsJSON; ?>;

        var ctx = document.getElementById('gradeBreakdown').getContext('2d');
        var gradeBreakdown = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: levelLabels,
                datasets: [{
                    label: 'Grade Level',
                    data: levels,
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)', 
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)', 
                        'rgba(255, 99, 132, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(153, 102, 255, 1)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw;
                            }
                        }
                    }
                }
            }
        });
        <?php } ?>

        <?php if ($result->num_rows) { ?>
        // At Risk Members
        var ctx = document.getElementById('atRiskChart').getContext('2d');
        var atRiskChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['At Risk', 'Not At Risk'],
                datasets: [{
                    label: 'Member Count',
                    data: [<?php echo $atRisk; ?>, <?php echo $notAtRisk; ?>],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(75, 192, 192, 1)',
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'At Risk Members Overview'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        precision: 0
                    }
                }
            }
        });
        <?php } ?>
    </script>
</body>
</html>