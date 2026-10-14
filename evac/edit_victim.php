<?php
include "auth.php";
$page_title = "Edit Victim";
$msg = "";
$id = (int)($_GET['id'] ?? 0);

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
?>
    <section class="panel">
        <div class="empty-state">
            <h3>Victim record not found</h3>
            <p>This record may have been removed or the ID is invalid.</p>
            <a href="victims.php" class="button-link">Back to victims</a>
        </div>
    </section>
<?php
    include "footer.php";
    exit;
}

$incidents = $conn->query("SELECT Incident_ID, Incident_Type, Location FROM Incident");
?>

<section class="page-intro">
    <div>
        <p class="page-eyebrow">VICTIM MANAGEMENT</p>
        <h2>Edit victim details</h2>
        <p>Update personal information, priority level or the associated incident.</p>
    </div>
    <div class="page-count">
        <span>Victim ID</span>
        <strong>#<?php echo htmlspecialchars((string)$v['Victim_ID']); ?></strong>
    </div>
</section>

<section class="panel edit-form-panel">
    <div class="panel-heading">
        <div>
            <h3>Victim information</h3>
            <p>Review the existing details before saving your changes.</p>
        </div>
        <span class="panel-step">EDIT RECORD</span>
    </div>

    <form method="post" class="box victim-form">
        <div class="form-grid">
            <div class="form-field">
                <label for="edit-victim-name">Full name</label>
                <input id="edit-victim-name" type="text" name="name"
                       value="<?php echo htmlspecialchars($v['Name']); ?>" required>
            </div>

            <div class="form-field">
                <label for="edit-victim-age">Age</label>
                <input id="edit-victim-age" type="number" name="age"
                       min="0" max="120"
                       value="<?php echo htmlspecialchars((string)$v['Age']); ?>" required>
            </div>

            <div class="form-field">
                <label for="edit-victim-priority">Priority level</label>
                <select id="edit-victim-priority" name="priority" required>
                    <?php foreach (['Red', 'Yellow', 'Green'] as $p) { ?>
                        <option value="<?php echo $p; ?>"
                            <?php echo ($p == $v['Priority_Level']) ? 'selected' : ''; ?>>
                            <?php echo $p; ?> priority
                        </option>
                    <?php } ?>
                </select>
                <small class="field-hint">Choose the appropriate triage priority.</small>
            </div>

            <div class="form-field">
                <label for="edit-victim-incident">Associated incident</label>
                <select id="edit-victim-incident" name="incident" required>
                    <?php while ($i = $incidents->fetch_assoc()) { ?>
                        <option value="<?php echo $i['Incident_ID']; ?>"
                            <?php echo ($i['Incident_ID'] == $v['Incident_ID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($i['Incident_ID'] . " - " . $i['Incident_Type'] . " (" . $i['Location'] . ")"); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-actions edit-form-actions">
            <a href="victims.php" class="button-link button-secondary">Cancel</a>
            <input type="submit" name="update" value="Save changes">
        </div>
    </form>
</section>

<?php include "footer.php"; ?>
