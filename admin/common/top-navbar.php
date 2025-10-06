<div id="top-nav">
    <a href="../home/" class="logo">
        <img src="../images/fbla-logo.png" alt="FBLA Membership logo" title="FBLA Membership">
    </a>
    <nav>
        <ul>
            <?php if (in_array('Attendance', $userPermissions)): ?>
                <li>
                    <a href="../attendance/">Attendance</span></a>
                </li>
            <?php endif; ?>
            <?php if (in_array('Community Services', $userPermissions)): ?>
                <li>
                    <a href="../services/">Community Services</span></a>
                </li>
            <?php endif; ?>
            <?php if (in_array('Demerits', $userPermissions)): ?>
                <li>
                    <a href="../demerits/">Demerits</span></a>
                </li>
            <?php endif; ?>
            <?php if (in_array('Reports', $userPermissions)): ?>
                <li>
                    <a href="../reports/">Reports</span></a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="nav-actions">
        <?php if (in_array('Global Search', $userPermissions)): include("quicksearch.php"); endif; ?>
        <div class="user-profile">
            <div class="user-avatar" onclick="toggleProfile()">
                <?php
                    $first_initial = strtoupper(substr($_SESSION['FirstName'], 0, 1));
                    $last_initial = strtoupper(substr($_SESSION['LastName'], 0, 1));
                    echo $first_initial . $last_initial;
                ?>
            </div>
            <ul class="profile-dropdown" id="profileDropdown">
                <li><a href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</div>
<script>
    function toggleProfile() {
        const dropdown = document.getElementById("profileDropdown");
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    window.addEventListener("click", function(event) {
        const avatar = document.querySelector(".user-avatar");
        const dropdown = document.getElementById("profileDropdown");

        if (!avatar.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.style.display = "none";
        }
    });
</script>
