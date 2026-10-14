<?php
include "auth.php";
$page_title = "Transport of Victims";
$msg = "";

// Calls the stored procedure sp_assign_victim; the trigger checks the rules
if (isset($_POST['assign'])) {
    try {
        $stmt = $conn->prepare("CALL sp_assign_victim(?, ?, ?)");
        $stmt->bind_param("iii", $_POST['dispatch'], $_POST['victim'], $_POST['facility']);
        $stmt->execute();
        $stmt->close();
        $msg = "<div class='ok'>Victim assigned to the dispatch.</div>";
    } catch (mysqli_sql_exception $e) {
        $msg = "<div class='error'>Could not assign: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Calls the stored procedure sp_complete_dispatch
if (isset($_GET['complete'])) {
    $stmt = $conn->prepare("CALL sp_complete_dispatch(?)");
    $stmt->bind_param("i", $_GET['complete']);
    $stmt->execute();
    $stmt->close();
    $msg = "<div class='ok'>Dispatch marked as completed.</div>";
}

// Remove a victim from a transport
if (isset($_GET['remove_d']) && isset($_GET['remove_v'])) {
    $stmt = $conn->prepare("DELETE FROM Transport WHERE Dispatch_ID = ? AND Victim_ID = ?");
    $stmt->bind_param("ii", $_GET['remove_d'], $_GET['remove_v']);
    $stmt->execute();
    $msg = "<div class='ok'>Transport record removed.</div>";
}

$dispatches = $conn->query("SELECT d.Dispatch_ID, i.Incident_Type, d.Status FROM Dispatch d JOIN Incident i ON d.Incident_ID = i.Incident_ID");
$victims = $conn->query("SELECT Victim_ID, Name, Incident_ID FROM Victim
                         WHERE Victim_ID NOT IN (SELECT Victim_ID FROM Transport)");
$facilities = $conn->query("SELECT Facility_ID, Name, Type, fn_beds_left(Facility_ID) AS Left_Places FROM Facility");

$res = $conn->query("SELECT t.Dispatch_ID, t.Victim_ID, v.Name, v.Priority_Level, f.Name AS Facility, t.Pickup_Time, t.Dropoff_Time
                     FROM Transport t
                     JOIN Victim v ON t.Victim_ID = v.Victim_ID
                     JOIN Facility f ON t.Facility_ID = f.Facility_ID
                     ORDER BY t.Dispatch_ID, t.Victim_ID");

$active = $conn->query("SELECT Dispatch_ID FROM Dispatch WHERE Status IN ('Dispatched','En Route')");

include "header.php";
?>

<section class="page-intro">
    <div>
        <p class="page-eyebrow">VICTIM MOVEMENT</p>
        <h2>Transport coordination</h2>
        <p>Assign victims to active dispatches and track their facility transfers.</p>
    </div>
    <div class="page-count">
        <span>Transport records</span>
        <strong><?php echo $res->num_rows; ?></strong>
    </div>
</section>

<?php echo $msg; ?>

<section class="panel">
    <div class="panel-heading">
        <div>
            <h3>Assign a victim</h3>
            <p>Select a dispatch, an unassigned victim and a destination facility.</p>
        </div>
        <span class="panel-step">NEW ASSIGNMENT</span>
    </div>

    <form method="post" class="box victim-form">
        <div class="form-grid">
            <div class="form-field">
                <label for="transport-dispatch">Dispatch</label>
                <select id="transport-dispatch" name="dispatch" required>
                    <?php while ($r = $dispatches->fetch_assoc()) { ?>
                        <option value="<?php echo $r['Dispatch_ID']; ?>">
                            <?php echo htmlspecialchars("Dispatch " . $r['Dispatch_ID'] . " - " . $r['Incident_Type'] . " (" . $r['Status'] . ")"); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-field">
                <label for="transport-victim">Victim not yet transported</label>
                <select id="transport-victim" name="victim" required>
                    <?php while ($r = $victims->fetch_assoc()) { ?>
                        <option value="<?php echo $r['Victim_ID']; ?>">
                            <?php echo htmlspecialchars($r['Name'] . " (incident " . $r['Incident_ID'] . ")"); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-field form-field-wide">
                <label for="transport-facility">Destination facility</label>
                <select id="transport-facility" name="facility" required>
                    <?php while ($r = $facilities->fetch_assoc()) { ?>
                        <option value="<?php echo $r['Facility_ID']; ?>">
                            <?php echo htmlspecialchars($r['Name'] . " - " . $r['Type'] . " (" . $r['Left_Places'] . " places left)"); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <input type="submit" name="assign" value="Assign victim">
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-heading">
        <div>
            <h3>Current transport records</h3>
            <p>Review destinations, priority levels and pickup/drop-off times.</p>
        </div>
        <span class="record-count"><?php echo $res->num_rows; ?> records</span>
    </div>

    <div class="table-scroll">
        <table class="records-table">
            <thead>
                <tr>
                    <th>Dispatch</th>
                    <th>Victim</th>
                    <th>Priority</th>
                    <th>Facility</th>
                    <th>Pickup time</th>
                    <th>Drop-off time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res->num_rows > 0) { ?>
                    <?php while ($row = $res->fetch_assoc()) {
                        $priority = strtolower((string)$row['Priority_Level']);
                    ?>
                        <tr>
                            <td class="record-id">
                                <?php echo htmlspecialchars((string)$row['Dispatch_ID']); ?>
                            </td>
                            <td class="person-name"><?php echo htmlspecialchars($row['Name']); ?></td>
                            <td>
                                <span class="priority-badge priority-<?php echo in_array($priority, ['red', 'yellow', 'green'], true) ? $priority : 'neutral'; ?>">
                                    <span class="priority-dot"></span>
                                    <?php echo htmlspecialchars($row['Priority_Level']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['Facility']); ?></td>
                            <td class="date-value"><?php echo htmlspecialchars((string)$row['Pickup_Time']); ?></td>
                            <td class="date-value"><?php echo htmlspecialchars((string)($row['Dropoff_Time'] ?? '-')); ?></td>
                            <td class="action-cell">
                                <a class="action-link action-delete"
                                   href="transport.php?remove_d=<?php echo urlencode((string)$row['Dispatch_ID']); ?>&amp;remove_v=<?php echo urlencode((string)$row['Victim_ID']); ?>"
                                   onclick="return confirm('Remove this record?')">Remove</a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="7" class="empty-table">No transport records yet. New assignments will appear here.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel completion-panel">
    <div class="panel-heading">
        <div>
            <h3>Complete a dispatch</h3>
            <p>Uses <code>sp_complete_dispatch</code> to mark a dispatch completed and fill missing drop-off times.</p>
        </div>
        <span class="panel-step">DISPATCH CLOSURE</span>
    </div>

    <?php if ($active->num_rows === 0) { ?>
        <p class="empty-state">There are no active dispatches to complete.</p>
    <?php } else { ?>
        <div class="completion-list">
            <?php while ($r = $active->fetch_assoc()) { ?>
                <div class="completion-item">
                    <div>
                        <strong>Dispatch #<?php echo htmlspecialchars((string)$r['Dispatch_ID']); ?></strong>
                        <span>Currently active</span>
                    </div>
                    <a class="button-link"
                       href="transport.php?complete=<?php echo urlencode((string)$r['Dispatch_ID']); ?>"
                       onclick="return confirm('Mark dispatch #<?php echo (int)$r['Dispatch_ID']; ?> as completed?')">
                        Complete dispatch
                    </a>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</section>

<?php include "footer.php"; ?>
