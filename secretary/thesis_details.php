<?php
require_once 'auth.php';
require_once 'db.php';
requireRole('secretary');

$thesisId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$thesisId) {
    header('Location: view_theses.php');
    exit;
}

$db = new Database();
$pdo = $db->getPDO();

// Get thesis basic info
$stmt = $pdo->prepare("
    SELECT ta.*, tt.title, tt.description, tt.pdf_path,
           s.first_name AS student_first, s.last_name AS student_last, s.email AS student_email,
           p.first_name AS prof_first, p.last_name AS prof_last, p.email AS prof_email
    FROM thesis_assignments ta
    JOIN thesis_topics tt ON ta.thesis_id = tt.id
    JOIN students st ON ta.student_id = st.user_id
    JOIN users s ON st.user_id = s.id
    JOIN professors pr ON ta.supervisor_id = pr.user_id
    JOIN users p ON pr.user_id = p.id
    WHERE ta.id = ?
");
$stmt->execute([$thesisId]);
$thesis = $stmt->fetch();

if (!$thesis) {
    $_SESSION['error'] = "Thesis not found";
    header('Location: view_theses.php');
    exit;
}

// Get committee members
$stmt = $pdo->prepare("
    SELECT u.first_name, u.last_name, u.email, cm.status, cm.grade, cm.invited_at, cm.responded_at
    FROM committee_members cm
    JOIN users u ON cm.professor_id = u.id
    WHERE cm.assignment_id = ?
    ORDER BY cm.responded_at DESC
");
$stmt->execute([$thesisId]);
$committee = $stmt->fetchAll();

// Get presentation details
$stmt = $pdo->prepare("
    SELECT * FROM thesis_presentations
    WHERE assignment_id = ?
");
$stmt->execute([$thesisId]);
$presentation = $stmt->fetch();

// Get timeline events
$stmt = $pdo->prepare("
    SELECT * FROM thesis_timeline
    WHERE assignment_id = ?
    ORDER BY event_date DESC
");
$stmt->execute([$thesisId]);
$timeline = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thesis Details - <?= htmlspecialchars($thesis['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .detail-card {
            border-left: 4px solid #0d6efd;
            margin-bottom: 20px;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #0d6efd;
            border: 2px solid white;
        }
        .document-preview {
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2><?= htmlspecialchars($thesis['title']) ?></h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="secretary.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="view_theses.php">Theses</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Details</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Thesis Info Card -->
                <div class="card detail-card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="card-title">Basic Information</h5>
                                <p><strong>Student:</strong> <?= htmlspecialchars($thesis['student_first'].' '.$thesis['student_last']) ?></p>
                                <p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($thesis['student_email']) ?>"><?= htmlspecialchars($thesis['student_email']) ?></a></p>
                                <p><strong>Supervisor:</strong> <?= htmlspecialchars($thesis['prof_first'].' '.$thesis['prof_last']) ?></p>
                                <p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($thesis['prof_email']) ?>"><?= htmlspecialchars($thesis['prof_email']) ?></a></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Start Date:</strong> <?= date('d/m/Y', strtotime($thesis['start_date'])) ?></p>
                                <?php if ($thesis['end_date']): ?>
                                    <p><strong>End Date:</strong> <?= date('d/m/Y', strtotime($thesis['end_date'])) ?></p>
                                <?php endif; ?>
                                <p><strong>Status:</strong> 
                                    <span class="badge 
                                        <?= $thesis['status'] === 'active' ? 'bg-primary' : 
                                           ($thesis['status'] === 'under_review' ? 'bg-warning text-dark' : 
                                           ($thesis['status'] === 'completed' ? 'bg-success' : 'bg-danger')) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $thesis['status'])) ?>
                                    </span>
                                </p>
                                <?php if ($thesis['status'] === 'completed' && $thesis['final_grade']): ?>
                                    <p><strong>Final Grade:</strong> <?= $thesis['final_grade'] ?></p>
                                <?php endif; ?>
                                <?php if ($thesis['library_link']): ?>
                                    <p><strong>Library Link:</strong> 
                                        <a href="<?= htmlspecialchars($thesis['library_link']) ?>" target="_blank">View in Library</a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($thesis['description']): ?>
                            <hr>
                            <h5>Description</h5>
                            <p><?= nl2br(htmlspecialchars($thesis['description'])) ?></p>
                        <?php endif; ?>
                        
                        <?php if ($thesis['pdf_path']): ?>
                            <hr>
                            <h5>Thesis Document</h5>
                            <div class="document-preview">
                                <embed src="<?= htmlspecialchars($thesis['pdf_path']) ?>" type="application/pdf" width="100%" height="500px">
                                <div class="mt-2">
                                    <a href="<?= htmlspecialchars($thesis['pdf_path']) ?>" class="btn btn-sm btn-primary" download>
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Committee Card -->
                <div class="card detail-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Committee Members</h5>
                        <?php if (empty($committee)): ?>
                            <p>No committee members assigned yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Grade</th>
                                            <th>Invited</th>
                                            <th>Responded</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($committee as $member): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($member['first_name'].' '.$member['last_name']) ?></td>
                                            <td><a href="mailto:<?= htmlspecialchars($member['email']) ?>"><?= htmlspecialchars($member['email']) ?></a></td>
                                            <td>
                                                <span class="badge 
                                                    <?= $member['status'] === 'accepted' ? 'bg-success' : 
                                                       ($member['status'] === 'rejected' ? 'bg-danger' : 'bg-secondary') ?>">
                                                    <?= ucfirst($member['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= $member['grade'] ?? '-' ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($member['invited_at'])) ?></td>
                                            <td>
                                                <?= $member['responded_at'] ? date('d/m/Y H:i', strtotime($member['responded_at'])) : 'Pending' ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Presentation Card -->
                <?php if ($presentation): ?>
                <div class="card detail-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Presentation Details</h5>
                        <p><strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($presentation['presentation_date'])) ?></p>
                        <?php if ($presentation['location']): ?>
                            <p><strong>Location:</strong> <?= htmlspecialchars($presentation['location']) ?></p>
                        <?php endif; ?>
                        <?php if ($presentation['online_link']): ?>
                            <p><strong>Online Link:</strong> 
                                <a href="<?= htmlspecialchars($presentation['online_link']) ?>" target="_blank">Join Presentation</a>
                            </p>
                        <?php endif; ?>
                        <?php if ($presentation['announcement_text']): ?>
                            <hr>
                            <h6>Announcement Text</h6>
                            <p><?= nl2br(htmlspecialchars($presentation['announcement_text'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Timeline Card -->
                <div class="card detail-card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Timeline</h5>
                        <?php if (empty($timeline)): ?>
                            <p>No timeline events recorded yet.</p>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach ($timeline as $event): ?>
                                <div class="timeline-item">
                                    <h6><?= htmlspecialchars($event['event_title']) ?></h6>
                                    <p class="text-muted small"><?= date('d/m/Y H:i', strtotime($event['event_date'])) ?></p>
                                    <p><?= nl2br(htmlspecialchars($event['event_description'])) ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions Card -->
                <div class="card detail-card">
                    <div class="card-body">
                        <h5 class="card-title">Actions</h5>
                        <div class="d-grid gap-2">
                            <?php if ($thesis['status'] === 'active'): ?>
                                <a href="enter_assembly.php?id=<?= $thesis['id'] ?>" class="btn btn-secondary">
                                    <i class="bi bi-file-earmark-text"></i> Enter Assembly Approval
                                </a>
                                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                    <i class="bi bi-x-circle"></i> Cancel Thesis
                                </button>
                            <?php elseif ($thesis['status'] === 'under_review'): ?>
                                <?php 
                                $allGraded = true;
                                foreach ($committee as $member) {
                                    if ($member['grade'] === null && $member['status'] === 'accepted') {
                                        $allGraded = false;
                                        break;
                                    }
                                }
                                ?>
                                <?php if ($allGraded): ?>
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#completeModal">
                                        <i class="bi bi-check-circle"></i> Mark as Completed
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline-secondary" disabled>
                                        Waiting for all grades
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="edit_thesis.php?id=<?= $thesis['id'] ?>" class="btn btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Thesis Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancel Thesis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="cancel_thesis.php" method="POST">
                    <input type="hidden" name="thesis_id" value="<?= $thesis['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Assembly Number</label>
                            <input type="text" class="form-control" name="assembly_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assembly Year</label>
                            <input type="number" class="form-control" name="assembly_year" value="<?= date('Y') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason for Cancellation</label>
                            <textarea class="form-control" name="reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Complete Thesis Modal -->
    <div class="modal fade" id="completeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Thesis</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="complete_thesis.php" method="POST">
                    <input type="hidden" name="thesis_id" value="<?= $thesis['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="modal-body">
                        <p>Are you sure you want to mark this thesis as completed?</p>
                        <p>This action cannot be undone.</p>
                        <div class="mb-3">
                            <label class="form-label">Library Repository Link</label>
                            <input type="url" class="form-control" name="library_link" 
                                   value="<?= htmlspecialchars($thesis['library_link'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Mark as Completed</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
