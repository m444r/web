<?php
require_once 'auth.php';
require_once 'db.php';
requireRole('secretary');

$db = new Database();
$pdo = $db->getPDO();

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $reportType = filter_input(INPUT_POST, 'report_type', FILTER_SANITIZE_STRING);
    $format = filter_input(INPUT_POST, 'format', FILTER_SANITIZE_STRING);
    $year = filter_input(INPUT_POST, 'year', FILTER_VALIDATE_INT);
    
    if (!$reportType || !$format || !in_array($format, ['pdf', 'csv', 'excel'])) {
        $_SESSION['error'] = "Invalid report parameters";
        header("Location: generate_reports.php");
        exit;
    }
    
    // Generate report based on type
    switch ($reportType) {
        case 'thesis_completion':
            generateThesisCompletionReport($pdo, $format, $year);
            break;
        case 'professor_activity':
            generateProfessorActivityReport($pdo, $format, $year);
            break;
        case 'student_performance':
            generateStudentPerformanceReport($pdo, $format, $year);
            break;
        default:
            $_SESSION['error'] = "Invalid report type";
            header("Location: generate_reports.php");
            exit;
    }
}

// Get distinct years for reports
$years = $pdo->query("SELECT DISTINCT YEAR(start_date) as year FROM thesis_assignments ORDER BY year DESC")
             ->fetchAll(PDO::FETCH_COLUMN);

/**
 * Generate Thesis Completion Report
 */
function generateThesisCompletionReport($pdo, $format, $year) {
    $query = "SELECT 
                ta.id, tt.title, 
                s.first_name AS student_first, s.last_name AS student_last,
                p.first_name AS prof_first, p.last_name AS prof_last,
                ta.start_date, ta.end_date, ta.status, ta.final_grade,
                DATEDIFF(ta.end_date, ta.start_date) AS days_to_complete
              FROM thesis_assignments ta
              JOIN thesis_topics tt ON ta.thesis_id = tt.id
              JOIN students st ON ta.student_id = st.user_id
              JOIN users s ON st.user_id = s.id
              JOIN professors pr ON ta.supervisor_id = pr.user_id
              JOIN users p ON pr.user_id = p.id";
    
    if ($year) {
        $query .= " WHERE YEAR(ta.start_date) = :year";
        $params = [':year' => $year];
    } else {
        $params = [];
    }
    
    $query .= " ORDER BY ta.end_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate report based on format
    switch ($format) {
        case 'pdf':
            generatePDFReport($data, "Thesis Completion Report");
            break;
        case 'csv':
            generateCSVReport($data, "thesis_completion");
            break;
        case 'excel':
            generateExcelReport($data, "Thesis Completion Report");
            break;
    }
}

/**
 * Generate Professor Activity Report
 */
function generateProfessorActivityReport($pdo, $format, $year) {
    $query = "SELECT 
                p.id, u.first_name, u.last_name, 
                COUNT(DISTINCT ta.id) AS total_theses,
                COUNT(DISTINCT CASE WHEN ta.status = 'completed' THEN ta.id END) AS completed_theses,
                COUNT(DISTINCT cm.assignment_id) AS committee_memberships,
                AVG(CASE WHEN ta.status = 'completed' THEN ta.final_grade END) AS avg_grade
              FROM professors p
              JOIN users u ON p.user_id = u.id
              LEFT JOIN thesis_assignments ta ON ta.supervisor_id = p.user_id";
    
    if ($year) {
        $query .= " AND YEAR(ta.start_date) = :year";
    }
    
    $query .= " LEFT JOIN committee_members cm ON cm.professor_id = p.user_id";
    
    if ($year) {
        $query .= " AND EXISTS (
                      SELECT 1 FROM thesis_assignments ta2 
                      WHERE ta2.id = cm.assignment_id 
                      AND YEAR(ta2.start_date) = :year
                    )";
    }
    
    $
