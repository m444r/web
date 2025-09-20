<?php
session_start();
require '../config.php';

if (!isset($_SESSION["userid"])) {
    header("Location: ../login_page.php");
    exit;
}

$student_id = $_SESSION["userid"];
$topic_id = intval($_GET['topic_id'] ?? 0);

if ($topic_id <= 0) {
    header("Location: StudentManageThesis.php");
    exit;
}

// Verify the topic belongs to the student and is completed
$stmt = $db->prepare("
    SELECT t.id, t.title, t.status, u.name as student_name, u.surname as student_surname,
           supervisor.name as supervisor_name, supervisor.surname as supervisor_surname
    FROM topics t
    JOIN users u ON u.id = t.assigned_to
    JOIN users supervisor ON supervisor.id = t.teacher_id
    WHERE t.id = ? AND t.assigned_to = ? AND t.status = 'completed'
");
$stmt->bind_param("ii", $topic_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: StudentManageThesis.php");
    exit;
}

$topic = $result->fetch_assoc();

// Get committee members
$stmt = $db->prepare("
    SELECT u.name, u.surname, cr.status
    FROM committee_requests cr
    JOIN users u ON u.id = cr.teacher_id
    WHERE cr.topic_id = ? AND cr.status = 'accepted'
");
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$committee = $stmt->get_result();

// Get grades
$stmt = $db->prepare("
    SELECT cg.grade, cg.submitted_at, u.name, u.surname
    FROM committee_grades cg
    JOIN users u ON u.id = cg.teacher_id
    WHERE cg.topic_id = ?
    ORDER BY cg.submitted_at DESC
");
$stmt->bind_param("i", $topic_id);
$stmt->execute();
$grades = $stmt->get_result();

// Calculate final grade
$final_grade = 0;
$grade_count = 0;
while ($grade_row = $grades->fetch_assoc()) {
    $final_grade += $grade_row['grade'];
    $grade_count++;
}
if ($grade_count > 0) {
    $final_grade = $final_grade / $grade_count;
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Πρακτικό Εξέτασης - <?= htmlspecialchars($topic['title']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }
        .protocol-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .protocol-header {
            background: linear-gradient(135deg, #6A90C7 0%, #5a7fb7 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .protocol-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .protocol-header .subtitle {
            margin-top: 10px;
            opacity: 0.9;
            font-size: 16px;
        }
        .protocol-content {
            padding: 40px;
        }
        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .section:last-child {
            border-bottom: none;
        }
        .section h2 {
            color: #6A90C7;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .section h2 i {
            margin-right: 10px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }
        .info-value {
            color: #212529;
            font-size: 16px;
        }
        .committee-list {
            list-style: none;
            padding: 0;
        }
        .committee-list li {
            padding: 10px 0;
            border-bottom: 1px solid #f1f3f4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .committee-list li:last-child {
            border-bottom: none;
        }
        .grade-display {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }
        .final-grade {
            font-size: 36px;
            font-weight: 700;
            color: #28a745;
            margin: 10px 0;
        }
        .grade-scale {
            color: #6c757d;
            font-size: 14px;
        }
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #6A90C7;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 15px 20px;
            font-size: 16px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(106, 144, 199, 0.3);
            transition: all 0.3s ease;
        }
        .print-btn:hover {
            background: #5a7fb7;
            transform: translateY(-2px);
        }
        @media print {
            .print-btn {
                display: none;
            }
            body {
                background: white;
                padding: 0;
            }
            .protocol-container {
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="protocol-container">
        <div class="protocol-header">
            <h1>ΠΡΑΚΤΙΚΟ ΕΞΕΤΑΣΗΣ</h1>
            <div class="subtitle">Διπλωματική Εργασία</div>
        </div>
        
        <div class="protocol-content">
            <!-- Basic Information -->
            <div class="section">
                <h2><i class="fas fa-info-circle"></i> Βασικές Πληροφορίες</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Τίτλος Διπλωματικής</div>
                        <div class="info-value"><?= htmlspecialchars($topic['title']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Φοιτητής/τρια</div>
                        <div class="info-value"><?= htmlspecialchars($topic['student_name'] . ' ' . $topic['student_surname']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Επιβλέπων</div>
                        <div class="info-value"><?= htmlspecialchars($topic['supervisor_name'] . ' ' . $topic['supervisor_surname']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Ημερομηνία Εξέτασης</div>
                        <div class="info-value"><?= date('d/m/Y', strtotime('now')) ?></div>
                    </div>
                </div>
            </div>

            <!-- Committee Members -->
            <div class="section">
                <h2><i class="fas fa-users"></i> Τριμελής Επιτροπή</h2>
                <ul class="committee-list">
                    <li>
                        <span><strong><?= htmlspecialchars($topic['supervisor_name'] . ' ' . $topic['supervisor_surname']) ?></strong> (Επιβλέπων)</span>
                        <span class="badge bg-primary">Επιβλέπων</span>
                    </li>
                    <?php 
                    $committee->data_seek(0); // Reset result pointer
                    while ($member = $committee->fetch_assoc()): ?>
                    <li>
                        <span><?= htmlspecialchars($member['name'] . ' ' . $member['surname']) ?></span>
                        <span class="badge bg-success">Μέλος Επιτροπής</span>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- Grades -->
            <div class="section">
                <h2><i class="fas fa-chart-line"></i> Βαθμολογία</h2>
                <div class="grade-display">
                    <div class="final-grade"><?= number_format($final_grade, 2) ?></div>
                    <div class="grade-scale">Βαθμός από 0 έως 10</div>
                </div>
                
                <?php 
                $grades->data_seek(0); // Reset result pointer
                if ($grades->num_rows > 0): ?>
                <h4 style="margin-top: 20px;">Αναλυτική Βαθμολογία</h4>
                <ul class="committee-list">
                    <?php while ($grade_row = $grades->fetch_assoc()): ?>
                    <li>
                        <span><?= htmlspecialchars($grade_row['name'] . ' ' . $grade_row['surname']) ?></span>
                        <span><strong><?= number_format($grade_row['grade'], 2) ?></strong></span>
                    </li>
                    <?php endwhile; ?>
                </ul>
                <?php endif; ?>
            </div>

            <!-- Conclusion -->
            <div class="section">
                <h2><i class="fas fa-check-circle"></i> Συμπέρασμα</h2>
                <p style="font-size: 16px; line-height: 1.6;">
                    Η διπλωματική εργασία με τίτλο <strong>"<?= htmlspecialchars($topic['title']) ?>"</strong> 
                    του φοιτητή/τριας <strong><?= htmlspecialchars($topic['student_name'] . ' ' . $topic['student_surname']) ?></strong> 
                    εξετάστηκε από την τριμελή επιτροπή και αξιολογήθηκε με βαθμό <strong><?= number_format($final_grade, 2) ?></strong>.
                </p>
                <p style="font-size: 16px; line-height: 1.6;">
                    Η διπλωματική εργασία θεωρείται <strong><?= $final_grade >= 5 ? 'επιτυχημένη' : 'μη επιτυχημένη' ?></strong>.
                </p>
            </div>
        </div>
    </div>

    <button class="print-btn" onclick="window.print()">
        <i class="fas fa-print"></i> Εκτύπωση
    </button>

    <script>
        // Auto-print when page loads (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
