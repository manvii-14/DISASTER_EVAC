<?php
include "auth.php";
$page_title = "Dashboard";
include "header.php";

// small helper to get a single count
function count_of($conn, $sql) {
    $r = $conn->query($sql)->fetch_row();
    return $r[0];
}
?>
<div class="card"><b><?php echo count_of($conn, "SELECT COUNT(*) FROM Incident"); ?></b>Incidents</div>
<div class="card"><b><?php echo count_of($conn, "SELECT COUNT(*) FROM Victim"); ?></b>Victims</div>
<div class="card"><b><?php echo count_of($conn, "SELECT COUNT(*) FROM Dispatch WHERE Status IN ('Dispatched','En Route')"); ?></b>Active dispatches</div>
<div class="card"><b><?php echo count_of($conn, "SELECT COUNT(*) FROM Vehicle WHERE Vehicle_ID NOT IN (SELECT Vehicle_ID FROM Dispatch WHERE Status IN ('Dispatched','En Route'))"); ?></b>Free vehicles</div>
<div class="card"><b><?php echo count_of($conn, "SELECT COUNT(*) FROM Driver"); ?></b>Drivers</div>

<h3>Recent incidents</h3>
<?php
$res = $conn->query("SELECT i.Incident_ID, i.Incident_Type, i.Severity_Level, i.Location, i.Date_Reported, r.Name AS Reported_By
                     FROM Incident i JOIN Reporter r ON i.Reporter_ID = r.Reporter_ID
                     ORDER BY i.Date_Reported DESC, i.Time_Reported DESC");
show_table($res);
include "footer.php";
?>
