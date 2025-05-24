<?php
require_once 'auth.php';
require_once 'db.php';
requireRole('secretary');

$db = new Database();
$pdo = $db->getPDO();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_announcement'])) {
    $thesisId = filter_input(INPUT_POST, 'thesis_id', FILTER_VALIDATE_INT);
    $announcementText = filter_input(INPUT_POST, 'announcement_text', FILTER_SANITIZE_STRING);
    
    if ($thesisId && $announcementText) {
        try {
            $stmt = $pdo->prepare("UPDATE thesis_presentations SET announcement_text = ? WHERE assignment_id = ?");
            $stmt->execute([$announcementText, $thesisId]);
            
            $_SESSION['message'] = "Announcement updated successfully";
            header("Location: thesis_announcements.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = "Failed to update announcement: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Invalid input data";
    }
}

// Get upcoming presentations
$upcoming = $pdo->query("
    SELECT tp.*, tt.title, 
           s.first_name AS student_first, s.last_name AS student_last,
           p.first_name AS prof_first, p.last_name AS prof_last
    FROM thesis_presentations tp
    JOIN thesis_assignments ta ON tp.assignment_id = ta.id
    JOIN thesis_topics tt ON ta.thesis_id = tt.id
    JOIN students st ON ta.student_id = st.user_id
    JOIN users s ON st.user_id = s.id
    JOIN professors pr ON ta.supervisor_id = pr.user_id
    JOIN users p ON pr.user_id = p.id
    WHERE tp.presentation_date >= NOW()
    ORDER BY tp.presentation_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Get recent announcements
$recent = $pdo->query("
    SELECT tp.*, tt.title, 
           s.first_name AS student_first, s.last_name AS student_last
    FROM thesis_presentations tp
    JOIN thesis_assignments ta ON tp.assignment_id = ta.id
    JOIN thesis_topics tt ON ta.thesis_id = tt.id
    JOIN students st ON ta.student_id = st.user_id
    JOIN users s ON st.user_id = s.id
    WHERE tp.announcement_text IS NOT NULL
    ORDER BY tp.presentation_date DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Announcements - Thesis Support System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .announcement-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        .announcement-card:hover {
            transform: translateY(-3px);
        }
        .presentation-date {
            font-weight: bold;
            color: #0d6efd;
        }
        .edit-announcement {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Thesis Announcements</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="secretary.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Announcements</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="row">
            <!-- Upcoming Presentations -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Upcoming Presentations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($upcoming)): ?>
                            <p>No upcoming presentations scheduled.</p>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($upcoming as $presentation): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?= htmlspecialchars($presentation['title']) ?></h5>
                                        <small class="presentation-date">
                                            <?= date('d/m/Y H:i', strtotime($presentation['presentation_date'])) ?>
                                        </small>
                                    </div>
                                    <p class="mb-1">Student: <?= htmlspecialchars($presentation['student_first'].' '.$presentation['student_last']) ?></p>
                                    <p class="mb-1">Supervisor: <?= htmlspecialchars($presentation['prof_first'].' '.$presentation['prof_last']) ?></p>
                                    
                                    <?php if ($presentation['location']): ?>
                                        <p class="mb-1">Location: <?= htmlspecialchars($presentation['location']) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($presentation['online_link']): ?>
                                        <p class="mb-1">Online Link: 
                                            <a href="<?= htmlspecialchars($presentation['online_link']) ?>" target="_blank">Join Presentation</a>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="mt-3">
                                        <?php if ($presentation['announcement_text']): ?>
                                            <div class="card announcement-card">
                                                <div class="card-body">
                                                    <h6>Current Announcement</h6>
                                                    <p><?= nl2br(htmlspecialchars($presentation['announcement_text'])) ?></p>
                                                    <button class="btn btn-sm btn-outline-primary edit-announcement" 
                                                            data-bs-toggle="modal" data-bs-target="#editAnnouncementModal"
                                                            data-thesis-id="<?= $presentation['assignment_id'] ?>"
                                                            data-announcement-text="<?= htmlspecialchars($presentation['announcement_text']) ?>">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-primary edit-announcement" 
                                                    data-bs-toggle="modal" data-bs-target="#editAnnouncementModal"
                                                    data-thesis-id="<?= $presentation['assignment_id'] ?>">
                                                <i class="bi bi-plus"></i> Add Announcement
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                   
