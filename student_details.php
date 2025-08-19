<?php
require_once 'auth_check.php';

if (!isset($_GET['id'])) {
    header('Location: students.php');
    exit();
}

$student_id = $_GET['id'];

// Get student details
$stmt = $pdo->prepare("SELECT s.*, m.type_name, m.duration_days, m.price 
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

// Get payment history
$payments = $pdo->prepare("SELECT p.*, pm.method_name, a.username as received_by 
                           FROM payments p 
                           JOIN payment_methods pm ON p.payment_method_id = pm.method_id 
                           JOIN admins a ON p.received_by = a.admin_id 
                           WHERE p.student_id = ? 
                           ORDER BY p.payment_date DESC");
$payments->execute([$student_id]);

// Get last payment
$last_payment = $pdo->prepare("SELECT p.payment_date 
                               FROM payments p 
                               WHERE p.student_id = ? 
                               ORDER BY p.payment_date DESC 
                               LIMIT 1");
$last_payment->execute([$student_id]);
$last_payment_date = $last_payment->fetchColumn();

// Calculate expiry date
$expiry_date = null;
if ($last_payment_date && $student['duration_days']) {
    $expiry_date = date('Y-m-d', strtotime($last_payment_date . ' + ' . $student['duration_days'] . ' days'));
}

// Check if membership is expired
$is_expired = false;
if ($expiry_date) {
    $is_expired = strtotime($expiry_date) < time();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - MMA Gym Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content ml-64 flex-1 p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Student Details</h1>
            <div class="flex items-center space-x-4">
                <button id="sidebar-toggle" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="students.php" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Students</span>
                </a>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Student Info -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0 h-16 w-16 rounded-full bg-gray-300 flex items-center justify-center">
                                <span class="text-gray-600 text-2xl"><?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?></span>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($student['first_name'] . ' ' . htmlspecialchars($student['last_name'])); ?></h2>
                                <p class="text-gray-600"><?php echo htmlspecialchars($student['email']); ?></p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $student['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <div>
                            <p class="text-sm text-gray-500">Phone</p>
                            <p class="font-medium"><?php echo htmlspecialchars($student['phone'] ?: 'N/A'); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Date of Birth</p>
                            <p class="font-medium"><?php echo $student['date_of_birth'] ? date('M d, Y', strtotime($student['date_of_birth'])) : 'N/A'; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Join Date</p>
                            <p class="font-medium"><?php echo date('M d, Y', strtotime($student['join_date'])); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Membership</p>
                            <p class="font-medium"><?php echo htmlspecialchars($student['type_name'] ?: 'None'); ?></p>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <p class="text-sm text-gray-500">Address</p>
                        <p class="font-medium"><?php echo htmlspecialchars($student['address'] ?: 'N/A'); ?></p>
                    </div>
                    
                    <?php if ($student['notes']): ?>
                    <div class="mt-6">
                        <p class="text-sm text-gray-500">Notes</p>
                        <p class="font-medium"><?php echo htmlspecialchars($student['notes']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-6 flex space-x-3">
                        <a href="edit_student.php?id=<?php echo $student['student_id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                            <i class="fas fa-edit"></i>
                            <span>Edit</span>
                        </a>
                        <?php if ($student['is_active']): ?>
                            <a href="deactivate_student.php?id=<?php echo $student['student_id']; ?>" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                                <i class="fas fa-user-slash"></i>
                                <span>Deactivate</span>
                            </a>
                        <?php else: ?>
                            <a href="activate_student.php?id=<?php echo $student['student_id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                                <i class="fas fa-user-check"></i>
                                <span>Activate</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Membership Status -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Membership Status</h3>
                    
                    <div class="space-y-4">
                        <?php if ($student['type_name']): ?>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Membership Type</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($student['type_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Duration</p>
                                    <p class="font-medium"><?php echo $student['duration_days']; ?> days</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Price</p>
                                    <p class="font-medium">$<?php echo number_format($student['price'], 2); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($last_payment_date): ?>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Last Payment</p>
                                        <p class="font-medium"><?php echo date('M d, Y', strtotime($last_payment_date)); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Expiry Date</p>
                                        <p class="font-medium <?php echo $is_expired ? 'text-red-600' : ''; ?>">
                                            <?php echo date('M d, Y', strtotime($expiry_date)); ?>
                                            <?php if ($is_expired): ?>
                                                <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded ml-2">Expired</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Days Remaining</p>
                                        <p class="font-medium">
                                            <?php 
                                            if ($expiry_date) {
                                                $days_remaining = floor((strtotime($expiry_date) - time()) / (60 * 60 * 24));
                                                echo $days_remaining > 0 ? $days_remaining . ' days' : 'Expired';
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-yellow-700">
                                                No payment recorded for this membership.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="pt-4">
                                <a href="record_payment.php?student_id=<?php echo $student['student_id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 inline-block">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Record Payment</span>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            This student doesn't have an active membership. Assign a membership to enable payment tracking.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Payment History -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Payment History</h3>
                        <a href="record_payment.php?student_id=<?php echo $student['student_id']; ?>" class="text-blue-600 hover:text-blue-800 flex items-center space-x-1">
                            <i class="fas fa-plus"></i>
                            <span>Add Payment</span>
                        </a>
                    </div>
                    
                    <?php if ($payments->rowCount() > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Received By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($payment = $payments->fetch()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            $<?php echo number_format($payment['amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($payment['method_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($payment['received_by']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            <?php echo htmlspecialchars($payment['notes'] ?: 'N/A'); ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <p class="text-gray-500">No payment history found for this student.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="space-y-6">
                <!-- Record Payment -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                    
                    <div class="space-y-3">
                        <a href="record_payment.php?student_id=<?php echo $student['student_id']; ?>" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center space-x-2">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>Record Payment</span>
                        </a>
                        
                        <a href="edit_student.php?id=<?php echo $student['student_id']; ?>" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center justify-center space-x-2">
                            <i class="fas fa-edit"></i>
                            <span>Edit Profile</span>
                        </a>
                        
                        <?php if ($student['is_active']): ?>
                            <a href="deactivate_student.php?id=<?php echo $student['student_id']; ?>" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg flex items-center justify-center space-x-2">
                                <i class="fas fa-user-slash"></i>
                                <span>Deactivate</span>
                            </a>
                        <?php else: ?>
                            <a href="activate_student.php?id=<?php echo $student['student_id']; ?>" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center justify-center space-x-2">
                                <i class="fas fa-user-check"></i>
                                <span>Activate</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Membership Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Membership Info</h3>
                    
                    <div class="space-y-2">
                        <div>
                            <p class="text-sm text-gray-500">Current Membership</p>
                            <p class="font-medium"><?php echo htmlspecialchars($student['type_name'] ?: 'None'); ?></p>
                        </div>
                        
                        <?php if ($student['type_name']): ?>
                            <div>
                                <p class="text-sm text-gray-500">Duration</p>
                                <p class="font-medium"><?php echo $student['duration_days']; ?> days</p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-500">Price</p>
                                <p class="font-medium">$<?php echo number_format($student['price'], 2); ?></p>
                            </div>
                            
                            <?php if ($expiry_date): ?>
                                <div>
                                    <p class="text-sm text-gray-500">Status</p>
                                    <p class="font-medium <?php echo $is_expired ? 'text-red-600' : ''; ?>">
                                        <?php echo $is_expired ? 'Expired' : 'Active'; ?>
                                    </p>
                                </div>
                                
                                <div>
                                    <p class="text-sm text-gray-500">Expires On</p>
                                    <p class="font-medium"><?php echo date('M d, Y', strtotime($expiry_date)); ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Check-ins (Optional) -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Check-ins</h3>
                    
                    <?php
                    $check_ins = $pdo->prepare("SELECT * FROM check_ins 
                                               WHERE student_id = ? 
                                               ORDER BY check_in_time DESC 
                                               LIMIT 5");
                    $check_ins->execute([$student_id]);
                    ?>
                    
                    <?php if ($check_ins->rowCount() > 0): ?>
                        <div class="space-y-3">
                            <?php while ($check_in = $check_ins->fetch()): ?>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div>
                                        <p class="font-medium"><?php echo date('M d, Y', strtotime($check_in['check_in_time'])); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo date('h:i A', strtotime($check_in['check_in_time'])); ?></p>
                                    </div>
                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Checked In</span>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <p class="text-gray-500">No recent check-ins found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/sidebar.js"></script>
</body>
</html>