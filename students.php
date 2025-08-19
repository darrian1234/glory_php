<?php
require_once 'auth_check.php';

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'active';

// Base query
$query = "SELECT s.*, m.type_name FROM students s 
          LEFT JOIN membership_types m ON s.membership_type_id = m.type_id 
          WHERE 1=1";

// Add search conditions
if (!empty($search)) {
    $query .= " AND (s.first_name LIKE :search OR s.last_name LIKE :search OR s.email LIKE :search OR s.phone LIKE :search)";
}

// Add status filter
if ($status_filter === 'active') {
    $query .= " AND s.is_active = 1";
} elseif ($status_filter === 'inactive') {
    $query .= " AND s.is_active = 0";
}

$query .= " ORDER BY s.first_name, s.last_name";

// Prepare and execute
$stmt = $pdo->prepare($query);

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindParam(':search', $search_param, PDO::PARAM_STR);
}

$stmt->execute();
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - MMA Gym Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.tailwindcss.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content ml-64 flex-1 p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Student Management</h1>
            <div class="flex items-center space-x-4">
                <button id="sidebar-toggle" class="text-gray-600 hover:text-gray-900 focus:outline-none">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="add_student.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2">
                    <i class="fas fa-plus"></i>
                    <span>Add Student</span>
                </a>
            </div>
        </div>
        
        <!-- Filters and Search -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="get" action="students.php" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <div class="relative">
                        <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Search students...">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg w-full">
                        Filter
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Students Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table id="students-table" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membership</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Join Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                    <span class="text-gray-600"><?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?></span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['first_name'] . ' ' . htmlspecialchars($student['last_name'])); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($student['email']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($student['phone']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($student['type_name'] ?? 'None'); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($student['join_date'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $student['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="student_details.php?id=<?php echo $student['student_id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                            <a href="edit_student.php?id=<?php echo $student['student_id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                            <?php if ($student['is_active']): ?>
                                <a href="deactivate_student.php?id=<?php echo $student['student_id']; ?>" class="text-yellow-600 hover:text-yellow-900 mr-3">Deactivate</a>
                            <?php else: ?>
                                <a href="activate_student.php?id=<?php echo $student['student_id']; ?>" class="text-green-600 hover:text-green-900 mr-3">Activate</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.tailwindcss.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#students-table').DataTable({
                responsive: true,
                order: [[3, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [5] }
                ]
            });
        });
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>