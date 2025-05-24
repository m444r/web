<?php
require_once 'auth.php';
require_once 'db.php';
requireRole('secretary');

$db = new Database();
$pdo = $db->getPDO();

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$supervisor = $_GET['supervisor'] ?? '';
$year = $_GET['year'] ?? '';

// Base query
$query = "SELECT ta.id, tt.title, 
          s.first_name AS student_first, s.last_name AS student_last,
          p.first_name AS prof_first, p.last_name AS prof_last,
          ta.start_date, ta.end_date, ta.status
          FROM thesis_assignments ta
          JOIN thesis_topics tt ON ta.thesis_id = tt.id
          JOIN students st ON ta.student_id = st.user_id
          JOIN users s ON st.user_id = s.id
          JOIN professors pr ON ta.supervisor_id = pr.user_id
          JOIN users p ON pr.user_id = p.id";

// Add conditions based on filters
$conditions = [];
$params = [];

if ($status !== 'all') {
    $conditions[] = "ta.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $conditions[] = "(tt.title LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}

if (!empty($supervisor)) {
    $conditions[] = "(p.first_name LIKE ? OR p.last_name LIKE ?)";
    $params = array_merge($params, ["%$supervisor%", "%$supervisor%"]);
}

if (!empty($year)) {
    $conditions[] = "YEAR(ta.start_date) = ?";
    $params[] = $year;
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY ta.start_date DESC";

// Prepare and execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$theses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct years for filter
$years = $pdo->query("SELECT DISTINCT YEAR(start_date) as year FROM thesis_assignments ORDER BY year DESC")
             ->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Theses - Thesis Support System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>Thesis Management</h2>
            </div>
            <div class="col-md-6 text-end">
                <a href="export_theses.php?<?= http_build_query($_GET) ?>" class="btn btn-outline-primary">
                    <i class="bi bi-download"></i> Export
                </a>
            </div>
        </div>
        
        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Statuses</option>
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="under_review" <?= $status === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="canceled" <?= $status === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Year</label>
                        <select name="year" class="form-select">
                            <option value="">All Years</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Title or student..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Supervisor</label>
                        <input type="text" name="supervisor" class="form-control" placeholder="Supervisor name..." 
                               value="<?= htmlspecialchars($supervisor) ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="view_theses.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Theses Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Student</th>
                                <th>Supervisor</th>
                                <th>Start Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($theses)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No theses found matching your criteria</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($theses as $thesis): ?>
                                <tr>
                                    <td><?= htmlspecialchars($thesis['title']) ?></td>
                                    <td><?= htmlspecialchars($thesis['student_first'].' '.$thesis['student_last']) ?></td>
                                    <td><?= htmlspecialchars($thesis['prof_first'].' '.$thesis['prof_last']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($thesis['start_date'])) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?= $thesis['status'] === 'active' ? 'bg-primary' : 
                                               ($thesis['status'] === 'under_review' ? 'bg-warning text-dark' : 
                                               ($thesis['status'] === 'completed' ? 'bg-success' : 'bg-danger')) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $thesis['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="thesis_details.php?id=<?= $thesis['id'] ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
