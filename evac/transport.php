<?php
include "auth.php";
$page_title = "Transport of Victims";
$msg = "";

// calls the stored procedure sp_assign_victim, the trigger checks the rules
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

// calls the stored procedure sp_complete_dispatch
if (isset($_GET['complete'])) {
    $stmt = $conn->prepare("CALL sp_complete_dispatch(?)");
    $stmt->bind_param("i", $_GET['complete']);
    $stmt->execute();
    $stmt->close();
    $msg = "<div class='ok'>Dispatch marked as completed.</div>";
}

// remove a victim from a transport
if (isset($_GET['remove_d']) && isset($_GET['remove_v'])) {
    $stmt = $conn->prepare("DELETE FROM Transport WHERE Dispatch_ID = ? AND Victim_ID = ?");
    $stmt->bind_param("ii", $_GET['remove_d'], $_GET['remove_v']);
    $stmt->execute();
    $msg = "<div class='ok'>Transport record removed.</div>";
}

include "header.php";
echo $msg;

$dispatches = $conn->query("SELECT d.Dispatch_ID, i.Incident_Type, d.Status FROM Dispatch d JOIN Incident i ON d.Incident_ID = i.Incident_ID");
$victims = $conn->query("SELECT Victim_ID, Name, Incident_ID FROM Victim
                         WHERE Victim_ID NOT IN (SELECT Victim_ID FROM Transport)");
$facilities = $conn->query("SELECT Facility_ID, Name, Type, fn_beds_left(Facility_ID) AS Left_Places FROM Facility");
?>
<form method="post" class="box">
    <b>Assign a victim to a dispatch</b>
    <label>Dispatch</label>
    <select name="dispatch">
        <?php while ($r = $dispatches->fetch_assoc()) {
            echo "<option value='" . $r['Dispatch_ID'] . "'>Dispatch " . $r['Dispatch_ID'] . " - " . htmlspecialchars($r['Incident_Type']) . " (" . $r['Status'] . ")</option>";
        } ?>
    </select>
    <label>Victim (not yet transported)</label>
    <select name="victim">
        <?php while ($r = $victims->fetch_assoc()) {
            echo "<option value='" . $r['Victim_ID'] . "'>" . htmlspecialchars($r['Name']) . " (incident " . $r['Incident_ID'] . ")</option>";
        } ?>
    </select>
    <label>Drop at facility</label>
    <select name="facility">
        <?php while ($r = $facilities->fetch_assoc()) {
            echo "<option value='" . $r['Facility_ID'] . "'>" . htmlspecialchars($r['Name']) . " - " . $r['Type'] . " (" . $r['Left_Places'] . " places left)</option>";
        } ?>
    </select>
    <input type="submit" name="assign" value="Assign">
</form>

<table>
<tr><th>Dispatch</th><th>Victim</th><th>Priority</th><th>Facility</th><th>Pickup</th><th>Dropoff</th><th>Action</th></tr>
<?php
$res = $conn->query("SELECT t.Dispatch_ID, t.Victim_ID, v.Name, v.Priority_Level, f.Name AS Facility, t.Pickup_Time, t.Dropoff_Time
                     FROM Transport t
                     JOIN Victim v ON t.Victim_ID = v.Victim_ID
                     JOIN Facility f ON t.Facility_ID = f.Facility_ID
                     ORDER BY t.Dispatch_ID, t.Victim_ID");
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Dispatch_ID'] . "</td>";
    echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
    echo "<td>" . $row['Priority_Level'] . "</td>";
    echo "<td>" . htmlspecialchars($row['Facility']) . "</td>";
    echo "<td>" . $row['Pickup_Time'] . "</td>";
    echo "<td>" . ($row['Dropoff_Time'] ?? '-') . "</td>";
    echo "<td><a href='transport.php?remove_d=" . $row['Dispatch_ID'] . "&remove_v=" . $row['Victim_ID'] . "' onclick=\"return confirm('Remove this record?')\">Remove</a></td>";
    echo "</tr>";
}
?>
</table>

<h3>Complete a dispatch</h3>
<p>Marks the dispatch as Completed and fills the missing drop-off times (uses sp_complete_dispatch).</p>
<?php
$active = $conn->query("SELECT Dispatch_ID FROM Dispatch WHERE Status IN ('Dispatched','En Route')");
if ($active->num_rows == 0) {
    echo "<p>No active dispatches.</p>";
}
while ($r = $active->fetch_assoc()) {
    echo "<a href='transport.php?complete=" . $r['Dispatch_ID'] . "'>Complete dispatch " . $r['Dispatch_ID'] . "</a><br>";
}
include "footer.php";
?>
