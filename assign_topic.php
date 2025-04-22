<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

$teacher_id = $_SESSION["userid"];
$message = "";

// ➤ Αν έγινε υποβολή φόρμας ανάθεσης
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['student_query'], $_POST['topic_id'])) {
    $student_input = $_POST['student_query'];
    $topic_id = $_POST['topic_id'];

    $stmt = $db->prepare("SELECT id FROM users WHERE role = 'student' AND (am = ? OR CONCAT(name, ' ', surname) LIKE ?)");
    $search_term = "%$student_input%";
    $stmt->bind_param("ss", $student_input, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $message = "❌ Δεν βρέθηκε φοιτητής με αυτά τα στοιχεία.";
    } else {
        $student = $result->fetch_assoc();
        $student_id = $student['id'];

        $stmt = $db->prepare("UPDATE topics SET assigned_to = ?, status = 'temporary', assigned_time = NOW() WHERE id = ? AND teacher_id = ?");

        $stmt->bind_param("iii", $student_id, $topic_id, $teacher_id);

      //  $stmt = $db->prepare ("INSERT INTO committee_requests (topic_id, teacher_id, status) VALUES (? , ? ,'pending')"); //???


        if ($stmt->execute()) {
            $message = "✅ Το θέμα ανατέθηκε προσωρινά.";
        } else {
            $message = "❌ Σφάλμα κατά την ανάθεση.";
        }
    }
}

// ➤ Λήψη όλων των θεμάτων του διδάσκοντα
$stmt = $db->prepare("SELECT t.id, t.title, t.status, u.am AS student_am, u.name AS student_name, u.surname 
                        FROM topics t 
                        LEFT JOIN users u ON t.assigned_to = u.id
                        WHERE t.teacher_id = ?");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$topics = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Διαχείριση Θεμάτων</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .form-section, .list-section { margin-top: 30px; }
        table { border-collapse: collapse; width: 100%; margin-top: 15px; }
        table, th, td { border: 1px solid #ccc; }
        th, td { padding: 10px; text-align: left; }
        .message { margin-top: 20px; font-weight: bold; }
        .edit-button { background-color: #ffc107; padding: 5px 10px; border: none; cursor: pointer; }
    </style>
</head>
<body>

<h2>Ανάθεση Θέματος σε Φοιτητή</h2>

<div style="position: absolute; top: 20px; right: 20px; display: flex; gap: 10px; align-items: center;">
    <a href="logout.php" style="
        background-color: #dc3545;
        color: white;
        padding: 6px 12px;
        font-size: 14px;
        border: none;
        border-radius: 4px;
        text-decoration: none;
    ">Αποσύνδεση</a>
    </div>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="form-section">
    <form method="POST">
        <label>Επιλογή Θέματος:</label>
        <select name="topic_id" required>
            <?php
            mysqli_data_seek($topics, 0); // Επαναφορά pointer
            while ($row = $topics->fetch_assoc()) {
                if ($row['status'] === 'available' || $row['status'] === NULL) {
                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['title']) . '</option>';
                }
            }
            ?>
        </select>
        <br><br>
        <label>Φοιτητής (ΑΜ ή Όνομα):</label>
        <input type="text" name="student_query" required>
        <button type="submit">Ανάθεση</button>
    </form>
</div>

<div class="list-section">
    <h3>Τα Θέματά μου</h3>
    <table>
        <thead>
            <tr>
                <th>Τίτλος</th>
                <th>Κατάσταση</th>
                <th>Ανατέθηκε σε</th>
                <th>Ενέργειες</th>
            </tr>
        </thead>
        <tbody>
            <?php
            mysqli_data_seek($topics, 0);
            while ($row = $topics->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                echo "<td>" . htmlspecialchars($row['status'] ?? 'διαθέσιμο') . "</td>";

                // Εμφάνιση του ΑΜ αν είναι "temporary"
                if ($row['status'] === 'temporary' && $row['student_am']) {
                    echo "<td>ΑΜ: " . htmlspecialchars($row['student_am']) . "</td>";
                } elseif ($row['student_name']) {
                    echo "<td>" . htmlspecialchars($row['student_name'] . ' ' . $row['surname']) . "</td>";
                } else {
                    echo "<td>-</td>";
                }

                echo '<td><a href="edit_topic.php?id=' . $row['id'] . '"><button class="edit-button">Επεξεργασία</button></a></td>';
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>
