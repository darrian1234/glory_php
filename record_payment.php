<?php
require_once 'auth_check.php';

if (!isset($_GET['student_id'])) {
    header('Location: students.php');
    exit();
}

$student_id = $_GET['student_id'];

// Get student details
$stmt = $pdo->prepare("SELECT s.*, m.type_name, m.price 
                       FROM students s 
                       LEFT JOIN membership_types m ON s.membership_type_id = m.type_id 
                       WHERE s.student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error_message'] = 'Student not found';
    header('Location: students.php');
    exit();
}

// Get payment methods
$payment_methods = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $amount = trim($_POST['amount']);
    $payment_date = trim($_POST['payment_date']);
    $payment_method_id = trim($_POST['payment_method_id']);
    $notes = trim($_POST['notes']);
    
    // Validation
    $errors = [];
    if (empty($amount)) $errors[] = 'Amount is required';
    if (empty($payment_date)) $errors[] = 'Payment date is required';
    if (empty($payment_method_id)) $errors[] = 'Payment method is required';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO payments (student_id, amount, payment_date, payment_method_id, received_by, notes) 
                                  VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $student_id,
                $amount,
                $payment_date,
                $payment_method_id,
                $_SESSION['admin_id'],
                $notes
            ]);
            
            $_SESSION['success_message'] = 'Payment recorded successfully!';
            header("Location: student_details.php?id=$student_id");
            exit();
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Payment - MMA Gym Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content ml-64 flex-1 p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Record Payment</h1>
            <div class="flex items-center space-x-4">
                <button id="sidebar-toggle" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="student_details.php?id=<?php echo $student_id; ?>" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Student</span>
                </a>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-lg shadow p-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-800">Student Information</h2>
                <div class="mt-2 flex items-center space-x-3">
                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                        <span class="text-gray-600"><?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?></span>
                    </div>
                    <div>
                        <p class="font-medium"><?php echo htmlspecialchars($student['first_name'] . ' ' . htmlspecialchars($student['last_name'])); ?></p>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($student['email']); ?></p>
                    </div>
                </div>
            </div>
            
            <form method="post" action="record_payment.php?student_id=<?php echo $student_id; ?>" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                        <input type="number" step="0.01" id="amount" name="amount" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ($student['price'] ?? ''); ?>">
                    </div>
                    
                    <div>
                        <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-1">Payment Date *</label>
                        <input type="date" id="payment_date" name="payment_date" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo isset($_POST['payment_date']) ? htmlspecialchars($_POST['payment_date']) : date('Y-m-d'); ?>">
                    </div>
                    
                    <div>
                        <label for="payment_method_id" class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                        <select id="payment_method_id" name="payment_method_id" required
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Select Method --</option>
                            <?php foreach ($payment_methods as $method): ?>
                                <option value="<?php echo $method['method_id']; ?>" <?php echo (isset($_POST['payment_method_id']) && $_POST['payment_method_id'] == $method['method_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($method['method_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="notes" name="notes" rows="3"
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg flex items-center space-x-2">
                        <i class="fas fa-save"></i>
                        <span>Record Payment</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/sidebar.js"></script>
</body>
</html>