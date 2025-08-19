<?php
// activate_student.php
require_once 'auth_check.php';

if (!isset($_GET['id'])) {
    header('Location: students.php');
    exit();
}

$student_id = $_GET['id'];

// Activate student
$stmt = $pdo->prepare("UPDATE students SET is_active = 1 WHERE student_id = ?");
$stmt->execute([$student_id]);

$_SESSION['success_message'] = 'Student activated successfully!';
header('Location: student_details.php?id=' . $student_id);
exit();
?>
