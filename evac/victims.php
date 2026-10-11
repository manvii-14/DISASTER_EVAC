<?php
include "auth.php";
$page_title = "Victims";
$msg = "";

// INSERT
if (isset($_POST['add'])) {
    try {
        $stmt = $conn->prepare("INSERT INTO Victim (Name, Age, Priority_Level, Incident_ID) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sisi", $_POST['name'], $_POST['age'], $_POST['priority'], $_POST['incident']);
        $stmt->execute();
        $msg = "<div class='ok'>Victim added.</div>";
    } catch (mysqli_sql_exception $e) {
        $msg = "<div class='error'>Could not add victim: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// DELETE
if (isset($_GET['delete'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM Victim WHERE Victim_ID = ?");
        $stmt->bind_param("i", $_GET['delete']);
        $stmt->execute();
        $msg = "<div class='ok'>Victim deleted.</div>";
    } catch (mysqli_sql_exception $e) {
        $msg = "<div class='error'>Could not delete: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

include "header.php";
echo $msg;
$incidents = $conn->query("SELECT Incident_ID, Incident_Type, Location FROM Incident");
?>
<form method="post" class="box">
    <b>Add a new victim</b>
    <label>Name</label><input type="text" name="name" required>
    <label>Age</label><input type="number" name="age" min="0" max="120" required>
    <label>Priority level</label>
    <select name="priority">
        <option>Red</option>
        <option>Yellow</option>
        <option>Green</option>
    </select>
    <label>Incident</label>
    <select name="incident">
        <?php while ($i = $incidents->fetch_assoc()) { ?>
            <option value="<?php echo $i['Incident_ID']; ?>">
                <?php echo $i['Incident_ID'] . " - " . htmlspecialchars($i['Incident_Type']) . " (" . htmlspecialchars($i['Location']) . ")"; ?>
            </option>
        <?php } ?>
    </select>
    <input type="submit" name="add" value="Add victim">
</form>

<table>
<tr><th>ID</th><th>Name</th><th>Age</th><th>Priority</th><th>Incident</th><th>Action</th></tr>
<?php
$res = $conn->query("SELECT v.Victim_ID, v.Name, v.Age, v.Priority_Level, i.Incident_Type
                     FROM Victim v JOIN Incident i ON v.Incident_ID = i.Incident_ID ORDER BY v.Victim_ID");
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Victim_ID'] . "</td>";
    echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
    echo "<td>" . $row['Age'] . "</td>";
    echo "<td>" . $row['Priority_Level'] . "</td>";
    echo "<td>" . htmlspecialchars($row['Incident_Type']) . "</td>";
    echo "<td><a href='edit_victim.php?id=" . $row['Victim_ID'] . "'>Edit</a> | ";
    echo "<a href='victims.php?delete=" . $row['Victim_ID'] . "' onclick=\"return confirm('Delete this victim?')\">Delete</a></td>";
    echo "</tr>";
}
?>
</table>
<?php include "footer.php"; ?>
