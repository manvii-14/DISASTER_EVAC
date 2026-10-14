
<?php
include "auth.php";
$page_title = "Dashboard";
include "header.php";

function count_of($conn, $sql) {
    $r = $conn->query($sql)->fetch_row();
    return (int)$r[0];
}

$incidents = count_of($conn, "SELECT COUNT(*) FROM Incident");
$victims = count_of($conn, "SELECT COUNT(*) FROM Victim");

$active_dispatches = count_of(
    $conn,
    "SELECT COUNT(*) FROM Dispatch
     WHERE Status IN ('Dispatched', 'En Route')"
);

$free_vehicles = count_of(
    $conn,
    "SELECT COUNT(*) FROM Vehicle
     WHERE Vehicle_ID NOT IN (
         SELECT Vehicle_ID FROM Dispatch
         WHERE Status IN ('Dispatched', 'En Route')
     )"
);

$drivers = count_of($conn, "SELECT COUNT(*) FROM Driver");

$res = $conn->query(
    "SELECT i.Incident_ID, i.Incident_Type, i.Severity_Level,
            i.Location, i.Date_Reported, r.Name AS Reported_By
     FROM Incident i
     JOIN Reporter r ON i.Reporter_ID = r.Reporter_ID
     ORDER BY i.Date_Reported DESC, i.Time_Reported DESC"
);
?>

<section class="dashboard-intro">
    <div class="dashboard-welcome">
        <p class="dashboard-eyebrow">OPERATIONS OVERVIEW</p>
        <h1>Welcome back, <?php
            echo htmlspecialchars($_SESSION['user']);
        ?></h1>
        <p class="dashboard-subtitle">
            Your central workspace for incident monitoring,
            victim records and emergency transport coordination.
        </p>
    </div>

    <div class="dashboard-date-block">
        <span class="date-caption">TODAY</span>
        <span class="dashboard-date"><?php echo date("d M Y"); ?></span>
    </div>
</section>

<section class="dashboard-section">
    <div class="dashboard-section-heading">
        <div>
            <h3>System overview</h3>
            <p>A snapshot of the current system records.</p>
        </div>
    </div>

    <div class="dashboard-stats">

        <a href="queries.php" class="stat-card stat-blue stat-card-link">
            <div class="stat-topline">
                <span class="stat-label">Total incidents</span>
                <span class="stat-symbol">!</span>
            </div>
            <div class="stat-number"><?php echo $incidents; ?></div>
            <div class="stat-note">Reported incidents <span>↗</span></div>
        </a>

        <a href="victims.php" class="stat-card stat-teal stat-card-link">
            <div class="stat-topline">
                <span class="stat-label">Victims</span>
                <span class="stat-symbol">＋</span>
            </div>
            <div class="stat-number"><?php echo $victims; ?></div>
            <div class="stat-note">Registered records <span>↗</span></div>
        </a>

        <a href="dispatch.php" class="stat-card stat-orange stat-card-link">
            <div class="stat-topline">
                <span class="stat-label">Active dispatches</span>
                <span class="stat-symbol">↗</span>
            </div>
            <div class="stat-number"><?php echo $active_dispatches; ?></div>
            <div class="stat-note">Dispatched or en route <span>↗</span></div>
        </a>

        <a href="transport.php" class="stat-card stat-green stat-card-link">
            <div class="stat-topline">
                <span class="stat-label">Available vehicles</span>
                <span class="stat-symbol">→</span>
            </div>
            <div class="stat-number"><?php echo $free_vehicles; ?></div>
            <div class="stat-note">Not on active dispatch <span>↗</span></div>
        </a>

        <a href="drivers.php" class="stat-card stat-slate stat-card-link">
            <div class="stat-topline">
                <span class="stat-label">Drivers</span>
                <span class="stat-symbol">＋</span>
            </div>
            <div class="stat-number"><?php echo $drivers; ?></div>
            <div class="stat-note">Registered drivers <span>↗</span></div>
        </a>

    </div>
</section>

<section class="dashboard-section">
    <div class="dashboard-section-heading incident-heading">
        <div>
            <h3>Incident records</h3>
            <p>Reported incidents, listed with their latest report dates first.</p>
        </div>
        <a class="dashboard-link" href="queries.php">
            Explore records <span aria-hidden="true">→</span>
        </a>
    </div>

    <div class="dashboard-table-wrap">
        <table class="incident-table">
            <thead>
                <tr>
                    <th>Incident ID</th>
                    <th>Type</th>
                    <th>Severity</th>
                    <th>Location</th>
                    <th>Date reported</th>
                    <th>Reported by</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($res && $res->num_rows > 0): ?>
                <?php while ($row = $res->fetch_assoc()): ?>
                    <?php
                    $severity = strtolower(trim((string)$row['Severity_Level']));
                    $severity_class = 'severity-neutral';

                    if (strpos($severity, 'critical') !== false ||
                        strpos($severity, 'high') !== false) {
                        $severity_class = 'severity-high';
                    } elseif (strpos($severity, 'medium') !== false ||
                              strpos($severity, 'moderate') !== false) {
                        $severity_class = 'severity-medium';
                    } elseif (strpos($severity, 'low') !== false) {
                        $severity_class = 'severity-low';
                    }
                    ?>
                    <tr>
                        <td class="incident-id">
                            <?php echo htmlspecialchars((string)$row['Incident_ID']); ?>
                        </td>
                        <td><?php echo htmlspecialchars((string)$row['Incident_Type']); ?></td>
                        <td>
                            <span class="severity-label <?php echo $severity_class; ?>">
                                <?php echo htmlspecialchars((string)$row['Severity_Level']); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars((string)$row['Location']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['Date_Reported']); ?></td>
                        <td><?php echo htmlspecialchars((string)$row['Reported_By']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="empty-table">
                        No incident records found.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include "footer.php"; ?>
