<div id="sidebar-left">
    <nav>
        <ul>
            <?php if (in_array('Attendance', $userPermissions)): ?>
                <li>
                    <a href="../members/attendance.php?id=<?php echo $getMemberId; ?>">
                        <img src="../images/calendar-edit.png" class="icon16" alt="Attendance icon">
                        <span>Attendance</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (in_array('Member BAA Progress', $userPermissions)): ?>
                <li>
                    <a href="../members/baaprogress.php?id=<?php echo $getMemberId; ?>">
                        <img src="../images/chart-bar.png" class="icon16" alt="BAA Progress icon">
                        <span>BAA Progress</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (in_array('Member Contacts', $userPermissions)): ?>
                <li>
                    <a href="../members/contacts.php?id=<?php echo $getMemberId; ?>">
                        <img src="../images/group.png" class="icon16" alt="Contacts icon">
                        <span>Contacts</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (in_array('Demerits', $userPermissions)): ?>
                <li>
                    <a href="../members/demerits.php?id=<?php echo $getMemberId; ?>">
                        <img src="../images/report-magnify.png" class="icon16" alt="Demerits icon">
                        <span>Demerits</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (in_array('Member Communication', $userPermissions)): ?>
                <li>
                    <a href="../members/email.php?id=<?php echo $getMemberId; ?>">
                        <img src="../images/email.png" class="icon16" alt="Email Communication icon">
                        <span>Email Communication</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (in_array('Member Timeline', $userPermissions)): ?>
                <li>
                    <a href="../members/timeline.php?id=<?php echo $getMemberId; ?>">
                        <img src="../images/timeline.png" class="icon16" alt="Historical Timeline icon">
                        <span>Historical Timeline</span>
                    </a>
                </li>
            <?php endif; ?>
            <li>
                <a href="../members/lookup.php?id=<?php echo $getMemberId; ?>">
                    <img src="../images/user-profile.png" class="icon16" alt="Lookup icon">
                    <span>Lookup</span>
                </a>
            </li>
            <?php if (in_array('Member Membership', $userPermissions)): ?>
                <li>
                    <a href="../members/membership.php?id=<?php echo $getMemberId; ?>">
                        <img src="../images/card.png" class="icon16" alt="Membership icon">
                        <span>Membership</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (in_array('Reports', $userPermissions)): ?>
                <li>
                    <a href="../members/printreport.php?id=<?php echo $getMemberId; ?>">
                        <img src="../images/chart-pie.png" class="icon16" alt="Reports icon">
                        <span>Reports</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php if (in_array('Community Services', $userPermissions)): ?>
                <li>
                    <a href="../members/services.php?id=<?php echo $getMemberId; ?>">
                        <img src="../images/money-dollar.png" class="icon16" alt="Services & Fundraising icon">
                        <span>Services & Fundraising</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
