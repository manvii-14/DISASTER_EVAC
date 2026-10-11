<?php
include "auth.php";
$page_title = "Edit Victim";
$msg = "";
$id = (int)$_GET['id'];

// UPDATE
if (isset($_POST['update'])) {
    try {
        $stmt = $conn->prepare("UPDATE Victim SET Name = ?, Age = ?, Priority_Level = ?, Incident_ID = ? WHERE Victim_ID = ?");
        $stmt->bind_param("sisii", $_POST['name'], $_POST['age'], $_POST['priority'], $_POST['incident'], $id);
        $stmt->execute();
        $msg = "<div class='ok'>Victim updated. <a href='victims.php'>Back to list</a></div>";
    } catch (mysqli_sql_exception $e) {
        $msg = "<div class='error'>Could not update: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

$stmt = $conn->prepare("SELECT * FROM Victim WHERE Victim_ID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$v = $stmt->get_result()->fetch_assoc();

include "header.php";
echo $msg;
if (!$v) {
    echo "<p>Victim not found.</p>";
    include "footer.php";
    exit;
}
$incidents = $conn->query("SELECT Incident_ID, Incident_Type, Location FROM Incident");
?>
<form method="post" class="box">
    <label>Name</label><input type="text" name="name" value="<?php echo htmlspecialchars($v['Name']); ?>" required>
    <label>Age</label><input type="number" name="age" value="<?php echo $v['Age']; ?>" required>
    <label>Priority level</label>
    <select name="priority">
        <?php foreach (['Red','Yellow','Green'] as $p) {
            $sel = ($p == $v['Priority_Level']) ? "selected" : "";
            echo "<option $sel>$p</option>";
        } ?>
    </select>
    <label>Incident</label>
    <select name="incident">
        <?php while ($i = $incidents->fetch_assoc()) {
            $sel = ($i['Incident_ID'] == $v['Incident_ID']) ? "selected" : "";
            echo "<option value='" . $i['Incident_ID'] . "' $sel>" . $i['Incident_ID'] . " - " . htmlspecialchars($i['Incident_Type']) . "</option>";
        } ?>
    </select>
    <input type="submit" name="update" value="Update victim">
</form>
<a href="victims.php">Back</a>
<?php include "footer.php"; ?>
