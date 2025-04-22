<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"]) || $_SESSION["role"] !== 'teacher') {
    header("Location: register.php");
    exit;
}

$teacher_id = $_SESSION["userid"];
$message = "";




if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = ($_POST['action'] === 'accept') ? 'accepted' : 'rejected';
 
    $stmt = $db->prepare("UPDATE committee_requests SET status = ?, responded_at = NOW() WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("sii", $action, $request_id, $teacher_id);
    $stmt->execute();

    // Πάρε το topic_id για αυτήν την πρόσκληση
    $stmt = $db->prepare("SELECT topic_id FROM committee_requests WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->bind_result($topic_id);
    $stmt->fetch();
    $stmt->close();

    // Μέτρησε πόσες accepted υπάρχουν για αυτό το topic
    $stmt = $db->prepare("SELECT COUNT(*) FROM committee_requests WHERE topic_id = ? AND status = 'accepted'");
    $stmt->bind_param("i", $topic_id);
    $stmt->execute();
    $stmt->bind_result($accepted_count);
    $stmt->fetch();
    $stmt->close();

    if ($accepted_count >= 2) {
        // Κάνε update το status του θέματος
        $stmt = $db->prepare("UPDATE topics SET status = 'confirmed' WHERE id = ?");
        $stmt->bind_param("i", $topic_id);
        $stmt->execute();

        // (Προαιρετικό) ακύρωσε όλες τις άλλες pending προσκλήσεις
        $stmt = $db->prepare("UPDATE committee_requests SET status = 'cancelled' WHERE topic_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $topic_id);
        $stmt->execute();
    }

    $message = "✅ Η ενέργεια ολοκληρώθηκε.";
}


// Φέρνουμε προσκλήσεις προς τον διδάσκοντα που είναι ακόμα εκκρεμείς
$stmt = $db->prepare("
    SELECT cr.id AS request_id, cr.status, t.title, s.name AS student_name, s.surname AS student_surname
    FROM committee_requests cr
    JOIN topics t ON cr.topic_id = t.id
    JOIN users s ON t.assigned_to = s.id
    WHERE cr.teacher_id = ? AND cr.status = 'pending'
");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$invitations = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Προσκλήσεις Τριμελούς</title>
    <style>
        body { font-family: Arial; padding: 30px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 10px; }
        .message { font-weight: bold; margin-bottom: 20px; }
        form { display: inline; }
        .accept-btn { background-color: #4CAF50; color: white; border: none; padding: 6px 10px; cursor: pointer; }
        .reject-btn { background-color: #f44336; color: white; border: none; padding: 6px 10px; cursor: pointer; }
    </style>
</head>
<body>

<h2>Προσκλήσεις Συμμετοχής σε Τριμελείς Επιτροπές</h2>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($invitations->num_rows === 0): ?>
    <p>Δεν υπάρχουν ενεργές προσκλήσεις.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Θέμα</th>
                <th>Φοιτητής</th>
                <th>Ενέργειες</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $invitations->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['student_name'] . ' ' . $row['student_surname']) ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                            <button type="submit" name="action" value="accept" class="accept-btn">Αποδοχή</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                            <button type="submit" name="action" value="reject" class="reject-btn">Απόρριψη</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>
