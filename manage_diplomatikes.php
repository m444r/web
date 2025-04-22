<?php
session_start();
require 'config.php';

if (!isset($_SESSION["userid"]) || $_SESSION["role"] !== 'teacher') {
    header("Location: register.php");
    exit;
}

$teacher_id = $_SESSION["userid"];

// Φέρνουμε διπλωματικές υπό ανάθεση που επιβλέπει ο χρήστης
$stmt = $db->prepare("
SELECT t.id AS topic_id, t.title, t.status, s.name AS student_name, s.surname AS student_surname
FROM topics t
JOIN users s ON s.id = t.assigned_to
WHERE t.teacher_id = ? 

");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$topics = $stmt->get_result();


?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Προβολή Προσκλήσεων Τριμελούς</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; }
        .topic { margin-bottom: 30px; padding: 15px; border: 1px solid #ccc; border-radius: 10px; }
        h3 { margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f9f9f9; }
        .status-accept { color: green; }
        .status-reject { color: red; }
        .status-pending { color: orange; }
    </style>
</head>
<body>

<h2>Διαχείριση Τριμελών για Θέματα Υπό Ανάθεση</h2>

<?php if ($topics->num_rows === 0): ?>
    <p>Δεν υπάρχουν θέματα υπό ανάθεση για προβολή.</p>
<?php else: ?>
    <?php while ($topic = $topics->fetch_assoc()): ?>
        <div class="topic">


            <?php
            // Φέρνουμε τις προσκλήσεις για το θέμα
            $stmt2 = $db->prepare("
            SELECT 
            t.id AS topic_id,
            t.title,
            s.name AS student_name,
            s.surname AS student_surname,
            cr.status AS status, 
            cr.requested_at AS requested_at, 
            cr.responded_at AS responded_at
            FROM topics t
            JOIN users s ON s.id = t.assigned_to
            JOIN committee_requests cr ON cr.topic_id = t.id
            WHERE t.teacher_id = ? AND t.status = 'awaiting_committee'


            ");

           

            $stmt2->bind_param("i", $topic['topic_id']);
            $stmt2->execute();
            $members = $stmt2->get_result();
            ?>

            <?php if ($members->num_rows > 0): ?>
                <table>
    <thead>
        
            <th>Θέμα</th>
            <th>Φοιτητής</th>
            <th>Προσκλήσεις</th>
            <th>Κατάσταση Διπλωματικής </th>
        
    </thead>
    <tbody>
        <?php while ($t = $topics->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($topic['title']) ?></td>
                <td><?= htmlspecialchars($topic['student_name'] . ' ' . $topic['student_surname']) ?></td>
                <td>
                    <ul>
                        <?php
                        // Για κάθε θέμα, πάρε τις προσκλήσεις
                        $stmt2 = $db->prepare("
                            SELECT cr.status, cr.requested_at, cr.responded_at, u.name, u.surname
                            FROM committee_requests cr
                            JOIN users u ON u.id = cr.teacher_id
                            WHERE cr.topic_id = ?
                        ");
                        $stmt2->bind_param("i", $topic['topic_id']);
                        $stmt2->execute();
                        $results = $stmt2->get_result();
                        while ($cr = $results->fetch_assoc()):
                        ?>
                            <li>
                                <?= htmlspecialchars($cr['name'] . " " . $cr['surname']) ?> -
                                <?= ucfirst($cr['status']) ?> |
                                Πρόσκληση: <?= date("d/m/Y", strtotime($cr['requested_at'])) ?> |
                                <?= $cr['responded_at'] ? "Απάντηση: " . date("d/m/Y", strtotime($cr['responded_at'])) : "Καμία απάντηση" ?> 
                                
 
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </td>
                <td> <?= ucfirst($t['status']) ?> </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>
            <?php else: ?>
                <p>Δεν έχουν σταλεί προσκλήσεις ακόμα.</p>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

</body>
</html>
