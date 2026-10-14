<?php
include "auth.php";
$page_title = "Dispatches";
$msg = "";

// INSERT a new dispatch (the trigger blocks busy vehicles / drivers)
if (isset($_POST['add'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO Dispatch (Incident_ID, Vehicle_ID, Driver_ID, Route_ID, Dispatch_Time, Status)
                                VALUES (?, ?, ?, ?, NOW(), 'Dispatched')");
        $stmt->bind_param("iiii", $_POST['incident'], $_POST['vehicle'], $_POST['driver'], $_POST['route']);
        $stmt->execute();
        $msg = "<div class='ok'>Dispatch created.</div>";
    } catch (mysqli_sql_exception $e) {
        $msg = "<div class='error'>Dispatch failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// UPDATE the status of a dispatch
if (isset($_POST['set_status'])) {
    $stmt = $conn->prepare("UPDATE Dispatch SET Status = ? WHERE Dispatch_ID = ?");
    $stmt->bind_param("si", $_POST['status'], $_POST['dispatch_id']);
    $stmt->execute();
    $msg = "<div class='ok'>Status updated.</div>";
}

// DELETE
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM Dispatch WHERE Dispatch_ID = ?");
    $stmt->bind_param("i", $_GET['delete']);
    $stmt->execute();
    $msg = "<div class='ok'>Dispatch deleted (its transport records were deleted too).</div>";
}

$incidents = $conn->query("SELECT Incident_ID, Incident_Type, Location FROM Incident");
$vehicles = $conn->query("SELECT Vehicle_ID, Vehicle_Type, Reg_Number FROM Vehicle");
$drivers = $conn->query("SELECT Driver_ID, Name FROM Driver");
$routes = $conn->query("SELECT Route_ID, Start_Location, End_Location FROM Route");

$res = $conn->query("SELECT d.*, i.Incident_Type, v.Reg_Number, dr.Name AS Driver_Name, r.Start_Location, r.End_Location
                     FROM Dispatch d
                     JOIN Incident i ON d.Incident_ID = i.Incident_ID
                     JOIN Vehicle v ON d.Vehicle_ID = v.Vehicle_ID
                     JOIN Driver dr ON d.Driver_ID = dr.Driver_ID
                     JOIN Route r ON d.Route_ID = r.Route_ID
                     ORDER BY d.Dispatch_ID");

$statuses = ['Dispatched', 'En Route', 'Completed', 'Cancelled'];

include "header.php";
?>

<section class="page-intro">
    <div>
        <p class="page-eyebrow">EMERGENCY OPERATIONS</p>
        <h2>Dispatch management</h2>
        <p>Coordinate incidents, vehicles, drivers and response routes.</p>
    </div>
    <div class="page-count">
        <span>Total dispatches</span>
        <strong><?php echo $res->num_rows; ?></strong>
    </div>
</section>

<?php echo $msg; ?>

<section class="panel">
    <div class="panel-heading">
        <div>
            <h3>Create a dispatch</h3>
            <p>Assign an incident, available vehicle, driver and route.</p>
        </div>
        <span class="panel-step">NEW DISPATCH</span>
    </div>

    <form method="post" class="box victim-form">
        <div class="form-grid">
            <div class="form-field">
                <label for="dispatch-incident">Incident</label>
                <select id="dispatch-incident" name="incident" required>
                    <?php while ($r = $incidents->fetch_assoc()) { ?>
                        <option value="<?php echo $r['Incident_ID']; ?>">
                            <?php echo htmlspecialchars($r['Incident_ID'] . " - " . $r['Incident_Type'] . ", " . $r['Location']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-field">
                <label for="dispatch-vehicle">Vehicle</label>
                <select id="dispatch-vehicle" name="vehicle" required>
                    <?php while ($r = $vehicles->fetch_assoc()) { ?>
                        <option value="<?php echo $r['Vehicle_ID']; ?>">
                            <?php echo htmlspecialchars($r['Reg_Number'] . " (" . $r['Vehicle_Type'] . ")"); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-field">
                <label for="dispatch-driver">Driver</label>
                <select id="dispatch-driver" name="driver" required>
                    <?php while ($r = $drivers->fetch_assoc()) { ?>
                        <option value="<?php echo $r['Driver_ID']; ?>">
                            <?php echo htmlspecialchars($r['Name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <div class="form-field">
                <label for="dispatch-route">Route</label>
                <select id="dispatch-route" name="route" required>
                    <?php while ($r = $routes->fetch_assoc()) { ?>
                        <option value="<?php echo $r['Route_ID']; ?>">
                            <?php echo htmlspecialchars($r['Start_Location'] . " to " . $r['End_Location']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <input type="submit" name="add" value="Create dispatch">
        </div>
    </form>
</section>

<section class="panel">
    <div class="panel-heading">
        <div>
            <h3>Dispatch records</h3>
            <p>Review assignments, timings and current status. Update status directly below.</p>
        </div>
        <span class="record-count"><?php echo $res->num_rows; ?> records</span>
    </div>

    <div class="table-scroll">
        <table class="records-table dispatch-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Incident</th>
                    <th>Vehicle</th>
                    <th>Driver</th>
                    <th>Route</th>
                    <th>Dispatch time</th>
                    <th>Arrival</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res->num_rows > 0) { ?>
                    <?php while ($row = $res->fetch_assoc()) { ?>
                        <tr>
                            <td class="record-id">
                                <?php echo htmlspecialchars((string)$row['Dispatch_ID']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['Incident_Type']); ?></td>
                            <td><?php echo htmlspecialchars($row['Reg_Number']); ?></td>
                            <td class="person-name"><?php echo htmlspecialchars($row['Driver_Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Start_Location'] . " to " . $row['End_Location']); ?></td>
                            <td class="date-value"><?php echo htmlspecialchars((string)$row['Dispatch_Time']); ?></td>
                            <td class="date-value"><?php echo htmlspecialchars((string)($row['Arrival_Time'] ?? '-')); ?></td>
                            <td>
                                <form method="post" class="status-edit-form">
                                    <input type="hidden" name="dispatch_id"
                                           value="<?php echo htmlspecialchars((string)$row['Dispatch_ID']); ?>">
                                    <select name="status" aria-label="Dispatch status">
                                        <?php foreach ($statuses as $s) { ?>
                                            <option value="<?php echo htmlspecialchars($s); ?>"
                                                <?php echo ($s == $row['Status']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($s); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <button type="submit" name="set_status" class="small-button">Save</button>
                                </form>
                            </td>
                            <td class="action-cell">
                                <a class="action-link action-delete"
                                   href="dispatch.php?delete=<?php echo urlencode((string)$row['Dispatch_ID']); ?>"
                                   onclick="return confirm('Delete this dispatch?')">Delete</a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="9" class="empty-table">No dispatches have been created yet.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<?php include "footer.php"; ?>
