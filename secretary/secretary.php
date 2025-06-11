<?php
require_once 'auth.php';
require_once 'db.php';

// Enhanced security checks
if (!isLoggedIn() || $_SESSION['role'] !== 'secretary') {
    $_SESSION['redirect_message'] = 'You must be logged in as secretary to access this page';
    header('Location: login.php');
    exit;
}

// Set security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Database connection
$db = new Database();
$pdo = $db->getPDO();

// Handle all form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Cancel Thesis
        if (isset($_POST['cancel_thesis'])) {
            $assignmentId = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
            $assemblyNumber = filter_input(INPUT_POST, 'assembly_number', FILTER_SANITIZE_STRING);
            $assemblyYear = filter_input(INPUT_POST, 'assembly_year', FILTER_VALIDATE_INT);
            $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING);
            
            if (!$assignmentId || !$assemblyNumber || !$assemblyYear || !$reason) {
                throw new Exception('Invalid input data');
            }
            
            $stmt = $pdo->prepare("UPDATE thesis_assignments SET status = 'canceled', 
                                 assembly_number = ?, assembly_year = ?, cancellation_reason = ?
                                 WHERE id = ?");
            $stmt->execute([$assemblyNumber, $assemblyYear, $reason, $assignmentId]);
            
            $_SESSION['message'] = "Thesis assignment canceled successfully";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }
        
        // Complete Thesis
        elseif (isset($_POST['complete_thesis'])) {
            $assignmentId = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
            
            if (!$assignmentId) {
                throw new Exception('Invalid thesis ID');
            }
            
            // Check if all committee members have submitted grades
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM committee_members 
                                 WHERE assignment_id = ? AND grade IS NULL");
            $stmt->execute([$assignmentId]);
            $missingGrades = $stmt->fetchColumn();
            
            if ($missingGrades > 0) {
                throw new Exception('Cannot complete - some committee members haven\'t submitted grades');
            }
            
            $stmt = $pdo->prepare("UPDATE thesis_assignments SET status = 'completed' WHERE id = ?");
            $stmt->execute([$assignmentId]);
            
            $_SESSION['message'] = "Thesis marked as completed successfully";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }
        
        // Import Data
        elseif (isset($_POST['import_data'])) {
            if ($_FILES['json_file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload error');
            }
            
            $json = file_get_contents($_FILES['json_file']['tmp_name']);
            $data = json_decode($json, true);
            
            if (!$data) {
                throw new Exception('Invalid JSON file');
            }
            
            // Begin transaction
            $pdo->beginTransaction();
            
            try {
                // Import Students
                if (!empty($data['students'])) {
                    $studentStmt = $pdo->prepare("INSERT INTO users 
                        (username, password, email, role, first_name, last_name, phone, created_at)
                        VALUES (?, ?, ?, 'student', ?, ?, ?, NOW())");
                    
                    $studentLinkStmt = $pdo->prepare("INSERT INTO students (user_id, student_id) VALUES (?, ?)");
                    
                    foreach ($data['students'] as $student) {
                        // Generate username from email
                        $username = strtok($student['email'], '@');
                        // Create random password
                        $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                        
                        $studentStmt->execute([
                            $username,
                            $password,
                            $student['email'],
                            $student['first_name'],
                            $student['last_name'],
                            $student['phone'] ?? null
                        ]);
                        
                        $studentId = $pdo->lastInsertId();
                        $studentLinkStmt->execute([$studentId, $student['student_id']]);
                    }
                }
                
                // Import Professors
                if (!empty($data['professors'])) {
                    $profStmt = $pdo->prepare("INSERT INTO users 
                        (username, password, email, role, first_name, last_name, created_at)
                        VALUES (?, ?, ?, 'professor', ?, ?, NOW())");
                    
                    $profLinkStmt = $pdo->prepare("INSERT INTO professors (user_id, department) VALUES (?, ?)");
                    
                    foreach ($data['professors'] as $professor) {
                        // Generate username from email
                        $username = strtok($professor['email'], '@');
                        // Create random password
                        $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                        
                        $profStmt->execute([
                            $username,
                            $password,
                            $professor['email'],
                            $professor['first_name'],
                            $professor['last_name']
                        ]);
                        
                        $profId = $pdo->lastInsertId();
                        $profLinkStmt->execute([$profId, $professor['department'] ?? null]);
                    }
                }
                
                $pdo->commit();
                $_SESSION['message'] = "Data imported successfully - ".count($data['students'] ?? [])." students and ".count($data['professors'] ?? [])." professors added";
                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw new Exception('Import failed: '.$e->getMessage());
            }
        }
        
        // Enter Assembly Approval
        elseif (isset($_POST['enter_assembly'])) {
            $assignmentId = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
            $assemblyNumber = filter_input(INPUT_POST, 'assembly_number', FILTER_SANITIZE_STRING);
            $assemblyYear = filter_input(INPUT_POST, 'assembly_year', FILTER_VALIDATE_INT);
            
            if (!$assignmentId || !$assemblyNumber || !$assemblyYear) {
                throw new Exception('Invalid input data');
            }
            
            $stmt = $pdo->prepare("UPDATE thesis_assignments 
                                 SET assembly_number = ?, assembly_year = ?
                                 WHERE id = ? AND status = 'active'");
            $stmt->execute([$assemblyNumber, $assemblyYear, $assignmentId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Failed to update assembly approval - thesis may not be active');
            }
            
            $_SESSION['message'] = "Assembly approval recorded successfully";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

// Get all theses for secretary view
$theses = $pdo->query("
    SELECT ta.id, tt.title, tt.description, tt.pdf_path,
           s.first_name AS student_first, s.last_name AS student_last,
           p.first_name AS prof_first, p.last_name AS prof_last,
           ta.start_date, ta.end_date, ta.status, ta.assembly_number, ta.assembly_year,
           ta.cancellation_reason, ta.final_grade, ta.library_link
    FROM thesis_assignments ta
    JOIN thesis_topics tt ON ta.thesis_id = tt.id
    JOIN students st ON ta.student_id = st.user_id
    JOIN users s ON st.user_id = s.id
    JOIN professors pr ON ta.supervisor_id = pr.user_id
    JOIN users p ON pr.user_id = p.id
    WHERE ta.status IN ('active', 'under_review', 'completed', 'canceled')
    ORDER BY 
        CASE ta.status
            WHEN 'active' THEN 1
            WHEN 'under_review' THEN 2
            WHEN 'completed' THEN 3
            WHEN 'canceled' THEN 4
        END,
        ta.start_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get committee members for all theses
$committeeMembers = [];
if (!empty($theses)) {
    $thesisIds = array_column($theses, 'id');
    $placeholders = implode(',', array_fill(0, count($thesisIds), '?'));
    
    $stmt = $pdo->prepare("
        SELECT cm.assignment_id, u.first_name, u.last_name, cm.status AS member_status, cm.grade
        FROM committee_members cm
        JOIN users u ON cm.professor_id = u.id
        WHERE cm.assignment_id IN ($placeholders)
    ");
    $stmt->execute($thesisIds);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $committeeMembers[$row['assignment_id']][] = $row;
    }
}

// Get presentation details for all theses
$presentations = [];
if (!empty($theses)) {
    $stmt = $pdo->prepare("
        SELECT assignment_id, presentation_date, location, online_link, announcement_text
        FROM thesis_presentations
        WHERE assignment_id IN ($placeholders)
    ");
    $stmt->execute($thesisIds);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $presentations[$row['assignment_id']] = $row;
    }
}

// Calculate statistics
$stats = [
    'total' => count($theses),
    'active' => 0,
    'under_review' => 0,
    'completed' => 0,
    'canceled' => 0,
    'pending_actions' => 0
];

foreach ($theses as $thesis) {
    $stats[$thesis['status']]++;
    
    // Count theses under review with all grades submitted but not completed
    if ($thesis['status'] === 'under_review') {
        $allGraded = true;
        if (isset($committeeMembers[$thesis['id']])) {
            foreach ($committeeMembers[$thesis['id']] as $member) {
                if ($member['grade'] === null) {
                    $allGraded = false;
                    break;
                }
            }
        }
        if ($allGraded) $stats['pending_actions']++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Support System - Secretary Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .thesis-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .thesis-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #0d6efd;
        }
        .detail-label {
            font-weight: 600;
            color: #495057;
        }
        .action-buttons .btn {
            margin-right: 5px;
            margin-bottom: 5px;
        }
        @media (max-width: 768px) {
            .action-buttons .btn {
                width: 100%;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <!-- Messages -->
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
        
        <h1 class="mb-4">Secretary Dashboard</h1>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Active Theses</h5>
                        <h2 class="card-text"><?= $stats['active'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Under Review</h5>
                        <h2 class="card-text"><?= $stats['under_review'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Completed</h5>
                        <h2 class="card-text"><?= $stats['completed'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title">Pending Actions</h5>
                        <h2 class="card-text"><?= $stats['pending_actions'] ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="row">
            <div class="col-md-8">
                <!-- Thesis List -->
                <div class="card mb-4">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#allTheses">All Theses</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#importData">Import Data</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- All Theses Tab -->
                            <div class="tab-pane fade show active" id="allTheses">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Student</th>
                                                <th>Supervisor</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($theses as $thesis): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($thesis['title']) ?></td>
                                                <td><?= htmlspecialchars($thesis['student_first'].' '.htmlspecialchars($thesis['student_last']) ?></td>
                                                <td><?= htmlspecialchars($thesis['prof_first'].' '.htmlspecialchars($thesis['prof_last']) ?></td>
                                                <td>
                                                    <span class="badge 
                                                        <?= $thesis['status'] === 'active' ? 'bg-primary' : 
                                                           ($thesis['status'] === 'under_review' ? 'bg-warning text-dark' : 
                                                           ($thesis['status'] === 'completed' ? 'bg-success' : 'bg-danger')) ?>
                                                        status-badge">
                                                        <?= ucfirst(str_replace('_', ' ', $thesis['status'])) ?>
                                                    </span>
                                                </td>
                                                <td class="action-buttons">
                                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" 
                                                        data-bs-target="#detailsModal<?= $thesis['id'] ?>">
                                                        <i class="bi bi-eye"></i> View
                                                    </button>
                                                    
                                                    <?php if ($thesis['status'] === 'active'): ?>
                                                        <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" 
                                                            data-bs-target="#assemblyModal<?= $thesis['id'] ?>">
                                                            <i class="bi bi-file-earmark-text"></i> Assembly
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                            data-bs-target="#cancelModal<?= $thesis['id'] ?>">
                                                            <i class="bi bi-x-circle"></i> Cancel
                                                        </button>
                                                    <?php elseif ($thesis['status'] === 'under_review'): ?>
                                                        <?php 
                                                        $allGraded = true;
                                                        if (isset($committeeMembers[$thesis['id']])) {
                                                            foreach ($committeeMembers[$thesis['id']] as $member) {
                                                                if ($member['grade'] === null) {
                                                                    $allGraded = false;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                        <?php if ($allGraded): ?>
                                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" 
                                                                data-bs-target="#completeModal<?= $thesis['id'] ?>">
                                                                <i class="bi bi-check-circle"></i> Complete
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Import Data Tab -->
                            <div class="tab-pane fade" id="importData">
                                <form method="POST" enctype="multipart/form-data" class="mb-4">
                                    <div class="mb-3">
                                        <label for="json_file" class="form-label">Select JSON File</label>
                                        <input class="form-control" type="file" id="json_file" name="json_file" accept=".json" required>
                                    </div>
                                    <button type="submit" name="import_data" class="btn btn-primary">
                                        <i class="bi bi-upload"></i> Import Data
                                    </button>
                                </form>
                                
                                <div class="card">
                                    <div class="card-header">
                                        <h5>JSON Format Example</h5>
                                    </div>
                                    <div class="card-body">
                                        <pre><code>{
    "students": [
        {
            "student_id": "UP123456",
            "first_name": "John",
            "last_name": "Doe",
            "email": "john.doe@upatras.gr",
            "phone": "1234567890"
        }
    ],
    "professors": [
        {
            "first_name": "Jane",
            "last_name": "Smith",
            "email": "jane.smith@upatras.gr",
            "department": "Computer Science"
        }
    ]
}</code></pre>
                                        <p class="mt-2">The system will automatically create user accounts with random passwords.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <a href="thesis_announcements.php" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-megaphone"></i> Thesis Announcements
                        </a>
                        <a href="generate_reports.php" class="btn btn-outline-secondary w-100 mb-2">
                            <i class="bi bi-file-earmark-pdf"></i> Generate Reports
                        </a>
                        <a href="add_student.php" class="btn btn-outline-success w-100 mb-2">
                            <i class="bi bi-person-plus"></i> Add New Student
                        </a>
                        <a href="add_professor.php" class="btn btn-outline-info w-100">
                            <i class="bi bi-person-plus"></i> Add New Professor
                        </a>
                    </div>
                </div>
                
                <!-- Statistics Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Thesis Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Thesis Detail Modals -->
    <?php foreach ($theses as $thesis): ?>
    <div class="modal fade" id="detailsModal<?= $thesis['id'] ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thesis Details: <?= htmlspecialchars($thesis['title']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Basic Information</h6>
                            <p><span class="detail-label">Student:</span> 
                                <?= htmlspecialchars($thesis['student_first'].' '.$thesis['student_last']) ?></p>
                            <p><span class="detail-label">Supervisor:</span> 
                                <?= htmlspecialchars($thesis['prof_first'].' '.$thesis['prof_last']) ?></p>
                            <p><span class="detail-label">Start Date:</span> 
                                <?= date('d/m/Y', strtotime($thesis['start_date'])) ?></p>
                            <p><span class="detail-label">Status:</span> 
                                <span class="badge 
                                    <?= $thesis['status'] === 'active' ? 'bg-primary' : 
                                       ($thesis['status'] === 'under_review' ? 'bg-warning text-dark' : 
                                       ($thesis['status'] === 'completed' ? 'bg-success' : 'bg-danger')) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $thesis['status'])) ?>
                                </span>
                            </p>
                            
                            <?php if ($thesis['status'] === 'completed' && $thesis['final_grade']): ?>
                                <p><span class="detail-label">Final Grade:</span> 
                                    <?= $thesis['final_grade'] ?></p>
                            <?php endif; ?>
                            
                            <?php if ($thesis['pdf_path']): ?>
                                <p><span class="detail-label">Description PDF:</span> 
                                    <a href="<?= htmlspecialchars($thesis['pdf_path']) ?>" target="_blank">View</a></p>
                            <?php endif; ?>
                            
                            <?php if ($thesis['library_link']): ?>
                                <p><span class="detail-label">Library Link:</span> 
                                    <a href="<?= htmlspecialchars($thesis['library_link']) ?>" target="_blank">View in Library</a></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <?php if (!empty($committeeMembers[$thesis['id']])): ?>
                                <h6>Committee Members</h6>
                                <ul class="list-group mb-3">
                                    <?php foreach ($committeeMembers[$thesis['id']] as $member): ?>
                                    <li class="list-group-item">
                                        <?= htmlspecialchars($member['first_name'].' '.$member['last_name']) ?>
                                        <span class="badge bg-secondary float-end">
                                            <?= ucfirst($member['member_status']) ?>
                                        </span>
                                        <?php if ($member['grade'] !== null): ?>
                                            <br><small>Grade: <?= $member['grade'] ?></small>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            
                            <?php if (!empty($presentations[$thesis['id']])): ?>
                                <h6>Presentation Details</h6>
                                <p><span class="detail-label">Date:</span> 
                                    <?= date('d/m/Y H:i', strtotime($presentations[$thesis['id']]['presentation_date'])) ?></p>
                                <?php if ($presentations[$thesis['id']]['location']): ?>
                                    <p><span class="detail-label">Location:</span> 
                                        <?= htmlspecialchars($presentations[$thesis['id']]['location']) ?></p>
                                <?php endif; ?>
                                <?php if ($presentations[$thesis['id']]['online_link']): ?>
                                    <p><span class="detail-label">Online Link:</span> 
                                        <a href="<?= htmlspecialchars($presentations[$thesis['id']]['online_link']) ?>" target="_blank">Join</a></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($thesis['description']): ?>
                        <hr>
                        <h6>Description</h6>
                        <p><?= nl2br(htmlspecialchars($thesis['description'])) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($thesis['status'] === 'canceled' && $thesis['cancellation_reason']): ?>
                        <hr>
                        <h6>Cancellation Details</h6>
                        <p><span class="detail-label">Reason:</span> <?= htmlspecialchars($thesis['cancellation_reason']) ?></p>
                        <?php if ($thesis['assembly_number'] && $thesis['assembly_year']): ?>
                            <p><span class="detail-label">Assembly Approval:</span> 
                                No. <?= htmlspecialchars($thesis['assembly_number']) ?> / <?= $thesis['assembly_year'] ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Assembly Approval Modal -->
    <div class="modal fade" id="assemblyModal<?= $thesis['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enter Assembly Approval</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="assignment_id" value="<?= $thesis['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Assembly Number</label>
                            <input type="text" class="form-control" name="assembly_number" 
                                   value="<?= htmlspecialchars($thesis['assembly_number'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assembly Year</label>
                            <input type="number" class="form-control" name="assembly_year" 
                                   value="<?= htmlspecialchars($thesis['assembly_year'] ?? date('Y')) ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="enter_assembly" class="btn btn-primary">Save Approval</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Cancel Thesis Modal -->
    <div class="modal fade" id="cancelModal<?= $thesis['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Thesis Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="assignment_id" value="<?= $thesis['id'] ?>">
                        <p>You are about to cancel this thesis assignment. Please provide the following details:</p>
                        <div class="mb-3">
                            <label class="form-label">Assembly Number</label>
                            <input type="text" class="form-control" name="assembly_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assembly Year</label>
                            <input type="number" class="form-control" name="assembly_year" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason for Cancellation</label>
                            <textarea class="form-control" name="reason" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="cancel_thesis" class="btn btn-danger">Confirm Cancellation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Complete Thesis Modal -->
    <div class="modal fade" id="completeModal<?= $thesis['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mark Thesis as Completed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="assignment_id" value="<?= $thesis['id'] ?>">
                        <p>Are you sure you want to mark this thesis as completed?</p>
                        <p>All committee members have submitted their grades.</p>
                        
                        <?php 
                        $totalGrade = 0;
                        $gradeCount = 0;
                        if (!empty($committeeMembers[$thesis['id']])) {
                            foreach ($committeeMembers[$thesis['id']] as $member) {
                                if ($member['grade'] !== null) {
                                    $totalGrade += $member['grade'];
                                    $gradeCount++;
                                }
                            }
                        }
                        $averageGrade = $gradeCount > 0 ? $totalGrade / $gradeCount : 0;
                        ?>
                        <p><strong>Average Grade:</strong> <?= number_format($averageGrade, 1) ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="complete_thesis" class="btn btn-success">Mark as Completed</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Status Chart
        const ctx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Under Review', 'Completed', 'Canceled'],
                datasets: [{
                    data: [
                        <?= $stats['active'] ?>,
                        <?= $stats['under_review'] ?>,
                        <?= $stats['completed'] ?>,
                        <?= $stats['canceled'] ?>
                    ],
                    backgroundColor: [
                        '#0d6efd',
                        '#ffc107',
                        '#198754',
                        '#dc3545'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>
