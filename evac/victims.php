
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

$incidents = $conn->query(
    "SELECT Incident_ID, Incident_Type, Location FROM Incident"
);

$res = $conn->query(
    "SELECT v.Victim_ID, v.Name, v.Age, v.Priority_Level, i.Incident_Type
     FROM Victim v
     JOIN Incident i ON v.Incident_ID = i.Incident_ID
     ORDER BY v.Victim_ID"
);
?>

<section class="page-intro">
    <div>
        <p class="page-eyebrow">PEOPLE MANAGEMENT</p>
        <h2>Victim records</h2>
        <p>Register victims, assign their incident and manage their priority information.</p>
    </div>
    <div class="page-count">
        <span>Total registered</span>
        <strong><?php echo $res ? $res->num_rows : 0; ?></strong>
    </div>
</section>

<?php echo $msg; ?>

<section class="panel victim-form-panel">
    <div class="panel-heading">
        <div>
            <h3>Register a victim</h3>
            <p>Enter the victim's details below.</p>
        </div>
        <span class="panel-step">NEW RECORD</span>
    </div>

    <form method="post" class="box victim-form">
        <div class="form-grid">
            <div class="form-field">
                <label for="victim-name">Full name</label>
                <input id="victim-name" type="text" name="name"
                       placeholder="Enter victim's name" required>
            </div>

            <div class="form-field">
                <label for="victim-age">Age</label>
                <input id="victim-age" type="number" name="age"
                       min="0" max="120" placeholder="Age in years" required>
            </div>

            <div class="form-field">
                <label for="victim-priority">Priority level</label>
                <select id="victim-priority" name="priority">
                    <option value="Red">Red — Critical</option>
                    <option value="Yellow">Yellow — Delayed</option>
                    <option value="Green">Green — Minor</option>
                </select>
            </div>

            <div class="form-field">
                <label for="victim-incident">Associated incident</label>
                <select id="victim-incident" name="incident" required>
                    <?php while ($i = $incidents->fetch_assoc()) { ?>
                        <option value="<?php echo htmlspecialchars((string)$i['Incident_ID']); ?>">
                            <?php
                            echo htmlspecialchars(
                                $i['Incident_ID'] . " — " .
                                $i['Incident_Type'] . " (" .
                                $i['Location'] . ")"
                            );
                            ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <input type="submit" name="add" value="Register victim">
        </div>
    </form>
</section>

<section class="panel victim-records-panel">
    <div class="panel-heading">
        <div>
            <h3>Registered victims</h3>
            <p>Review victim details and manage existing records.</p>
        </div>
        <span class="record-count">
            <?php echo $res ? $res->num_rows : 0; ?> records
        </span>
    </div>

    <div class="table-scroll">
        <table class="records-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Victim</th>
                    <th>Age</th>
                    <th>Priority</th>
                    <th>Incident</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res && $res->num_rows > 0) { ?>
                    <?php while ($row = $res->fetch_assoc()) { ?>
                        <?php
                        $priority = strtolower(trim((string)$row['Priority_Level']));
                        $priority_class = 'priority-neutral';

                        if ($priority === 'red') {
                            $priority_class = 'priority-red';
                        } elseif ($priority === 'yellow') {
                            $priority_class = 'priority-yellow';
                        } elseif ($priority === 'green') {
                            $priority_class = 'priority-green';
                        }
                        ?>
                        <tr>
                            <td class="record-id">
                                <?php echo htmlspecialchars((string)$row['Victim_ID']); ?>
                            </td>
                            <td class="person-name">
                                <?php echo htmlspecialchars($row['Name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars((string)$row['Age']); ?></td>
                            <td>
                                <span class="priority-badge <?php echo $priority_class; ?>">
                                    <span class="priority-dot"></span>
                                    <?php echo htmlspecialchars($row['Priority_Level']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['Incident_Type']); ?></td>
                            <td class="action-cell">
                                <a class="action-link"
                                   href="edit_victim.php?id=<?php echo urlencode((string)$row['Victim_ID']); ?>">
                                    Edit
                                </a>
                                <span class="action-separator">·</span>
                                <a class="action-link action-delete"
                                   href="victims.php?delete=<?php echo urlencode((string)$row['Victim_ID']); ?>"
                                   onclick="return confirm('Delete this victim?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="6" class="empty-table">
                            No victim records found. Register a victim using the form above.
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<?php include "footer.php"; ?>

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

$incidents = $conn->query(
    "SELECT Incident_ID, Incident_Type, Location FROM Incident"
);

$res = $conn->query(
    "SELECT v.Victim_ID, v.Name, v.Age, v.Priority_Level, i.Incident_Type
     FROM Victim v
     JOIN Incident i ON v.Incident_ID = i.Incident_ID
     ORDER BY v.Victim_ID"
);
?>

<section class="page-intro">
    <div>
        <p class="page-eyebrow">PEOPLE MANAGEMENT</p>
        <h2>Victim records</h2>
        <p>Register victims, assign their incident and manage their priority information.</p>
    </div>
    <div class="page-count">
        <span>Total registered</span>
        <strong><?php echo $res ? $res->num_rows : 0; ?></strong>
    </div>
</section>

<?php echo $msg; ?>

<section class="panel victim-form-panel">
    <div class="panel-heading">
        <div>
            <h3>Register a victim</h3>
            <p>Enter the victim's details below.</p>
        </div>
        <span class="panel-step">NEW RECORD</span>
    </div>

    <form method="post" class="box victim-form">
        <div class="form-grid">
            <div class="form-field">
                <label for="victim-name">Full name</label>
                <input id="victim-name" type="text" name="name"
                       placeholder="Enter victim's name" required>
            </div>

            <div class="form-field">
                <label for="victim-age">Age</label>
                <input id="victim-age" type="number" name="age"
                       min="0" max="120" placeholder="Age in years" required>
            </div>

            <div class="form-field">
                <label for="victim-priority">Priority level</label>
                <select id="victim-priority" name="priority">
                    <option value="Red">Red — Critical</option>
                    <option value="Yellow">Yellow — Delayed</option>
                    <option value="Green">Green — Minor</option>
                </select>
            </div>

            <div class="form-field">
                <label for="victim-incident">Associated incident</label>
                <select id="victim-incident" name="incident" required>
                    <?php while ($i = $incidents->fetch_assoc()) { ?>
                        <option value="<?php echo htmlspecialchars((string)$i['Incident_ID']); ?>">
                            <?php
                            echo htmlspecialchars(
                                $i['Incident_ID'] . " — " .
                                $i['Incident_Type'] . " (" .
                                $i['Location'] . ")"
                            );
                            ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-actions">
            <input type="submit" name="add" value="Register victim">
        </div>
    </form>
</section>

<section class="panel victim-records-panel">
    <div class="panel-heading">
        <div>
            <h3>Registered victims</h3>
            <p>Review victim details and manage existing records.</p>
        </div>
        <span class="record-count">
            <?php echo $res ? $res->num_rows : 0; ?> records
        </span>
    </div>

    <div class="table-scroll">
        <table class="records-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Victim</th>
                    <th>Age</th>
                    <th>Priority</th>
                    <th>Incident</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res && $res->num_rows > 0) { ?>
                    <?php while ($row = $res->fetch_assoc()) { ?>
                        <?php
                        $priority = strtolower(trim((string)$row['Priority_Level']));
                        $priority_class = 'priority-neutral';

                        if ($priority === 'red') {
                            $priority_class = 'priority-red';
                        } elseif ($priority === 'yellow') {
                            $priority_class = 'priority-yellow';
                        } elseif ($priority === 'green') {
                            $priority_class = 'priority-green';
                        }
                        ?>
                        <tr>
                            <td class="record-id">
                                <?php echo htmlspecialchars((string)$row['Victim_ID']); ?>
                            </td>
                            <td class="person-name">
                                <?php echo htmlspecialchars($row['Name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars((string)$row['Age']); ?></td>
                            <td>
                                <span class="priority-badge <?php echo $priority_class; ?>">
                                    <span class="priority-dot"></span>
                                    <?php echo htmlspecialchars($row['Priority_Level']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($row['Incident_Type']); ?></td>
                            <td class="action-cell">
                                <a class="action-link"
                                   href="edit_victim.php?id=<?php echo urlencode((string)$row['Victim_ID']); ?>">
                                    Edit
                                </a>
                                <span class="action-separator">·</span>
                                <a class="action-link action-delete"
                                   href="victims.php?delete=<?php echo urlencode((string)$row['Victim_ID']); ?>"
                                   onclick="return confirm('Delete this victim?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="6" class="empty-table">
                            No victim records found. Register a victim using the form above.
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>

<?php include "footer.php"; ?>
