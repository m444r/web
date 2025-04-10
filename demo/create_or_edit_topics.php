<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: register.php");
    exit;
}

$teacher_id = $_SESSION["userid"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $summary = $_POST['summary'];

    $pdf_path = null;

    if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $filename = uniqid() . "-" . basename($_FILES["pdf"]["name"]);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES["pdf"]["tmp_name"], $target_file)) {
            $pdf_path = $target_file;
        } else {
            $message = "⚠️ Αποτυχία στην αποθήκευση του αρχείου PDF.";
        }
    }

    $stmt = $db->prepare("INSERT INTO topics (title, summary, pdf_path, teacher_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $title, $summary, $pdf_path, $teacher_id);

    if ($stmt->execute()) {
        $message = "✅ Το θέμα καταχωρήθηκε με επιτυχία!";
    } else {
        $message = "❌ Σφάλμα κατά την καταχώρηση.";
    }
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Καταχώρηση Θέματος</title>
    <style>
        form {
            width: 60%;
            margin: 40px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 12px;
            background-color: #f9f9f9;
        }
        label {
            font-weight: bold;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 8px;
            margin-top: 6px;
            margin-bottom: 16px;
        }
        .submit-btn {
            background-color: #0077cc;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .submit-btn:hover {
            background-color: #005fa3;
        }
        .message {
            text-align: center;
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<h2 style="text-align: center;">Καταχώρηση Νέου Θέματος</h2>

<?php if ($message): ?>
    <p class="message"><?= $message ?></p>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>Τίτλος Θέματος:</label><br>
    <input type="text" name="title" required><br>

    <label>Περίληψη:</label><br>
    <textarea name="summary" rows="5"></textarea><br>

    <label>Ανέβασμα Αρχείου PDF (προαιρετικό):</label><br>
    <input type="file" name="pdf" accept=".pdf"><br><br>

    <div style="text-align: center;">
        <button type="submit" class="submit-btn"> Καταχώρηση</button>
    </div>
   
</form>
<h2 style="text-align:center;">Τα θέματα μου</h2>

<table border="1" width="80%" align="center" cellpadding="10">
    <td>
        <a href="edit_topic.php?id=<?= $row['id'] ?>">✏️ Επεξεργασία</a> |
        <a href="delete_topic.php?id=<?= $row['id'] ?>" onclick="return confirm('Είστε σίγουρος ότι θέλετε να διαγράψετε το θέμα;')">🗑️ Διαγραφή</a>
    </td>
    <tr>
        <th>Τίτλος</th>
        <th>Περίληψη</th>
        <th>Αρχείο</th>
        <th>Ημ/νία</th>
    </tr>
    <?php
    $stmt = $db->prepare("SELECT * FROM topics WHERE teacher_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()): ?>
        
        <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['summary']) ?></td>
            <td>
                <?php if ($row['pdf_path']): ?>
                    <a href="<?= $row['pdf_path'] ?>" target="_blank">Προβολή</a>
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td><?= $row['created_at'] ?></td>
        </tr>
    <?php endwhile; ?>
</table>
            

</body>
</html>

</body>
</html>
