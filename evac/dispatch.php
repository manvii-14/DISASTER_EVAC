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

include "header.php";
echo $msg;
$incidents = $conn->query("SELECT Incident_ID, Incident_Type, Location FROM Incident");
$vehicles = $conn->query("SELECT Vehicle_ID, Vehicle_Type, Reg_Number FROM Vehicle");
$drivers = $conn->query("SELECT Driver_ID, Name FROM Driver");
$routes = $conn->query("SELECT Route_ID, Start_Location, End_Location FROM Route");
?>
<form method="post" class="box">
    <b>New dispatch</b>
    <label>Incident</label>
    <select name="incident">
        <?php while ($r = $incidents->fetch_assoc()) {
            echo "<option value='" . $r['Incident_ID'] . "'>" . $r['Incident_ID'] . " - " . htmlspecialchars($r['Incident_Type'] . ", " . $r['Location']) . "</option>";
        } ?>
    </select>
    <label>Vehicle</label>
    <select name="vehicle">
        <?php while ($r = $vehicles->fetch_assoc()) {
            echo "<option value='" . $r['Vehicle_ID'] . "'>" . htmlspecialchars($r['Reg_Number'] . " (" . $r['Vehicle_Type'] . ")") . "</option>";
        } ?>
    </select>
    <label>Driver</label>
    <select name="driver">
        <?php while ($r = $drivers->fetch_assoc()) {
            echo "<option value='" . $r['Driver_ID'] . "'>" . htmlspecialchars($r['Name']) . "</option>";
        } ?>
    </select>
    <label>Route</label>
    <select name="route">
        <?php while ($r = $routes->fetch_assoc()) {
            echo "<option value='" . $r['Route_ID'] . "'>" . htmlspecialchars($r['Start_Location'] . " to " . $r['End_Location']) . "</option>";
        } ?>
    </select>
    <input type="submit" name="add" value="Dispatch">
</form>

<table>
<tr><th>ID</th><th>Incident</th><th>Vehicle</th><th>Driver</th><th>Route</th><th>Dispatch time</th><th>Arrival</th><th>Status</th><th>Action</th></tr>
<?php
$res = $conn->query("SELECT d.*, i.Incident_Type, v.Reg_Number, dr.Name AS Driver_Name, r.Start_Location, r.End_Location
                     FROM Dispatch d
                     JOIN Incident i ON d.Incident_ID = i.Incident_ID
                     JOIN Vehicle v ON d.Vehicle_ID = v.Vehicle_ID
                     JOIN Driver dr ON d.Driver_ID = dr.Driver_ID
                     JOIN Route r ON d.Route_ID = r.Route_ID
                     ORDER BY d.Dispatch_ID");
$statuses = ['Dispatched', 'En Route', 'Completed', 'Cancelled'];
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Dispatch_ID'] . "</td>";
    echo "<td>" . htmlspecialchars($row['Incident_Type']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Reg_Number']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Driver_Name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['Start_Location'] . " to " . $row['End_Location']) . "</td>";
    echo "<td>" . $row['Dispatch_Time'] . "</td>";
    echo "<td>" . ($row['Arrival_Time'] ?? '-') . "</td>";
    echo "<td><form method='post' style='margin:0'>";
    echo "<input type='hidden' name='dispatch_id' value='" . $row['Dispatch_ID'] . "'>";
    echo "<select name='status'>";
    foreach ($statuses as $s) {
        $sel = ($s == $row['Status']) ? "selected" : "";
        echo "<option $sel>$s</option>";
    }
    echo "</select> <input type='submit' name='set_status' value='Save' style='margin:0'></form></td>";
    echo "<td><a href='dispatch.php?delete=" . $row['Dispatch_ID'] . "' onclick=\"return confirm('Delete this dispatch?')\">Delete</a></td>";
    echo "</tr>";
}
?>
</table>
<?php include "footer.php"; ?>
