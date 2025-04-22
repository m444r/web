<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

$student_id = $_SESSION["userid"];
$message = "";

// ================== 1. ΥΠΟΒΟΛΗ ΕΠΙΤΡΟΠΗΣ ==================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['topic_id'], $_POST['committee'])) {
    $topic_id = $_POST['topic_id'];
    $committee = $_POST['committee'];

    if (count($committee) < 2) {
        $message = "❌ Πρέπει να επιλέξετε τουλαχιστον δύο μέλη.";
    } else {
        $stmt = $db->prepare("INSERT INTO committee_requests (topic_id, teacher_id, status) VALUES (?, ?, 'pending')");
        foreach ($committee as $teacher_id) {
            $stmt->bind_param("ii", $topic_id, $teacher_id);
            $stmt->execute();
        }

        $stmt = $db->prepare("UPDATE topics SET status = 'awaiting_committee' WHERE id = ? AND assigned_to = ?");
        $stmt->bind_param("ii", $topic_id, $student_id);
        $stmt->execute();

        $message = "✅ Οι αιτήσεις στάλθηκαν.";
    }
}
//gia na leitourghsoyn ta parakatw prepei h ergasia na paei ypo eksetasi apo ton didaskwn
// ================== 2. ΔΙΑΧΕΙΡΙΣΗ ΕΞΕΤΑΣΗΣ ==================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['draft_pdf'])) {
    $target_dir = "uploads/";
    $filename = basename($_FILES["draft_pdf"]["name"]);
    $target_file = $target_dir . time() . "_" . $filename;

    if (move_uploaded_file($_FILES["draft_pdf"]["tmp_name"], $target_file)) {
        $stmt = $db->prepare("UPDATE topics SET draft_pdf_path = ? WHERE assigned_to = ? AND status = 'for examination'");
        $stmt->bind_param("si", $target_file, $student_id);
        $stmt->execute();
        $message = "✅ Το αρχείο ανέβηκε με επιτυχία.";
    } else {
        $message = "❌ Σφάλμα στο ανέβασμα αρχείου.";
    }
}

if (isset($_POST['exam_datetime'], $_POST['mode'], $_POST['location'], $_POST['links'])) {
    $datetime = $_POST['exam_datetime'];
    $mode = $_POST['mode'];
    $location = $_POST['location'];
    $links = $_POST['links'];

    $stmt = $db->prepare("UPDATE topics SET exam_datetime = ?, exam_mode = ?, exam_location = ?, extra_links = ? WHERE assigned_to = ? AND status = 'for examination'");
    $stmt->bind_param("ssssi", $datetime, $mode, $location, $links, $student_id);
    $stmt->execute();
    $message = "✅ Στοιχεία εξέτασης αποθηκεύτηκαν.";
}

// ================== 3. ΦΕΡΝΟΥΜΕ ΘΕΜΑΤΑ ==================
$stmt = $db->prepare("SELECT id, title FROM topics WHERE assigned_to = ? AND status = 'temporary'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$topics = $stmt->get_result();

$examination = $db->prepare("SELECT * FROM topics WHERE assigned_to = ? AND status = 'for examination'");
$examination->bind_param("i", $student_id);
$examination->execute();
$exam_topic = $examination->get_result()->fetch_assoc();

// ================== 4. ΦΕΡΝΟΥΜΕ ΚΑΘΗΓΗΤΕΣ ==================
$teachers = $db->query("SELECT id, name, surname FROM users WHERE role = 'teacher'");
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Διαχείριση Επιτροπής & Εξέτασης</title>
    <style>
        body { font-family: Arial; padding: 30px; }
        label { display: block; margin-top: 10px; }
        .checkboxes label { margin-right: 15px; display: inline-block; }
        .message { font-weight: bold; margin-top: 20px; color: green; }
        .section { margin-bottom: 40px; border-bottom: 1px solid #ccc; padding-bottom: 30px; }
    </style>
</head>
<body>

<h2>Διαχείριση Τριμελούς Επιτροπής</h2>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="section">
    <form method="POST">
        <label for="topic_id">Επιλέξτε Θέμα:</label>
        <select name="topic_id" required>
            <?php while ($row = $topics->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['title']) ?></option>
            <?php endwhile; ?>
        </select>

        <label>Επιλέξτε 2 Μέλη Τριμελούς Επιτροπής:</label>
        <div class="checkboxes">
            <?php while ($teacher = $teachers->fetch_assoc()): ?>
                <label>
                    <input type="checkbox" name="committee[]" value="<?= $teacher['id'] ?>">
                    <?= htmlspecialchars($teacher['name'] . " " . $teacher['surname']) ?>
                </label>
            <?php endwhile; ?>
        </div>

        <br>
        <button type="submit">Αποστολή Αιτήματος</button>
    </form>
</div>

<?php if ($exam_topic): ?>
    <div class="section">
        <h3>Στοιχεία Υπό Εξέταση Διπλωματικής</h3>

        <form method="POST" enctype="multipart/form-data">
            <label>Ανέβασμα Πρόχειρου Κειμένου (.pdf):</label>
            <input type="file" name="draft_pdf" required>
            <button type="submit">Ανέβασμα</button>
        </form>

        <form method="POST">
            <label>Ημερομηνία και Ώρα Εξέτασης:</label>
            <input type="datetime-local" name="exam_datetime" value="<?= htmlspecialchars($exam_topic['exam_datetime'] ?? '') ?>"><br>

            <label>Τρόπος Εξέτασης:</label>
            <select name="mode">
                <option value="onsite" <?= ($exam_topic['exam_mode'] === 'onsite' ? 'selected' : '') ?>>Δια ζώσης</option>
                <option value="online" <?= ($exam_topic['exam_mode'] === 'online' ? 'selected' : '') ?>>Διαδικτυακά</option>
            </select><br>

            <label>Τοποθεσία ή Σύνδεσμος:</label>
            <input type="text" name="location" value="<?= htmlspecialchars($exam_topic['exam_location'] ?? '') ?>"><br>

            <label>Επιπλέον Σύνδεσμοι (π.χ. YouTube, Drive):</label>
            <textarea name="links" rows="4" cols="50"><?= htmlspecialchars($exam_topic['extra_links'] ?? '') ?></textarea><br>

            <button type="submit">Καταχώρηση Στοιχείων</button>
        </form>
    </div>
<?php endif; ?>

</body>
</html>
