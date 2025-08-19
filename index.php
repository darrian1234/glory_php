<?php
require_once 'auth_check.php';

// Get admin details
$admin_id = $_SESSION['admin_id'];
$stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch();

// Get counts for dashboard
$total_students = $pdo->query("SELECT COUNT(*) FROM students WHERE is_active = 1")->fetchColumn();
$active_memberships = $pdo->query("SELECT COUNT(*) FROM students WHERE is_active = 1 AND membership_type_id IS NOT NULL")->fetchColumn();
$expiring_soon = $pdo->query("SELECT COUNT(DISTINCT s.student_id) FROM students s 
                             JOIN payments p ON s.student_id = p.student_id 
                             WHERE s.is_active = 1 
                             AND p.payment_date = (SELECT MAX(payment_date) FROM payments WHERE student_id = s.student_id)
                             AND DATEDIFF(DATE_ADD(p.payment_date, INTERVAL (SELECT duration_days FROM membership_types WHERE type_id = s.membership_type_id) DAY), CURDATE()) BETWEEN 0 AND 7")->fetchColumn();
$inactive_students = $pdo->query("SELECT COUNT(*) FROM students WHERE is_active = 0")->fetchColumn();

// Handle search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_results = [];
if (!empty($search_query)) {
    $search_stmt = $pdo->prepare("SELECT s.*, m.type_name FROM students s 
                                 LEFT JOIN membership_types m ON s.membership_type_id = m.type_id 
                                 WHERE (s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ? OR s.phone LIKE ? OR m.type_name LIKE ?)
                                 ORDER BY s.first_name, s.last_name LIMIT 10");
    $search_param = "%$search_query%";
    $search_stmt->execute([$search_param, $search_param, $search_param, $search_param, $search_param]);
    $search_results = $search_stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MMA Gym Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.tailwindcss.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .sidebar {
            transition: all 0.3s ease-in-out;
        }
        .sidebar.collapsed {
            width: 80px;
        }
        .sidebar.collapsed .sidebar-text {
            display: none;
        }
        .sidebar.collapsed .logo-text {
            display: none;
        }
        .sidebar.collapsed .menu-item {
            justify-content: center;
        }
        .main-content {
            transition: all 0.3s ease-in-out;
        }
        .sidebar.collapsed + .main-content {
            margin-left: 80px;
        }
        .active-menu {
            background-color: #3B82F6;
            color: white;
        }
        .active-menu:hover {
            background-color: #3B82F6 !important;
            color: white !important;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .search-dropdown {
            display: none;
            position: absolute;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <div class="sidebar bg-gray-800 text-white h-screen fixed top-0 left-0 w-64 overflow-y-auto overflow-x-hidden z-10 shadow-lg">
            <div class="p-4 flex items-center space-x-2 border-b border-gray-700">
                <i class="fas fa-fist-raised text-2xl text-blue-400 animate-pulse"></i>
                <span class="logo-text text-xl font-bold">Glory MMa</span>
            </div>
            
            <div class="p-4">
                <div class="flex items-center space-x-3 mb-6 animate__animated animate__fadeIn">
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center shadow-md">
                        <span class="text-white font-bold"><?php echo strtoupper(substr($admin['username'], 0, 1)); ?></span>
                    </div>
                    <div>
                        <div class="font-medium"><?php echo htmlspecialchars($admin['username']); ?></div>
                        <div class="text-xs text-gray-400">Admin</div>
                    </div>
                </div>
                
                <nav class="space-y-1">
                    <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-3 py-2 rounded hover:bg-gray-700 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active-menu' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="sidebar-text">Dashboard</span>
                    </a>
                    <a href="students.php" class="menu-item flex items-center space-x-3 px-3 py-2 rounded hover:bg-gray-700 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active-menu' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span class="sidebar-text">Students</span>
                    </a>
                    <a href="payments.php" class="menu-item flex items-center space-x-3 px-3 py-2 rounded hover:bg-gray-700 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active-menu' : ''; ?>">
                        <i class="fas fa-money-bill-wave"></i>
                        <span class="sidebar-text">Payments</span>
                    </a>
                    
                    </a>
                    <a href="admins.php" class="menu-item flex items-center space-x-3 px-3 py-2 rounded hover:bg-gray-700 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active-menu' : ''; ?>">
                        <i class="fas fa-user-shield"></i>
                        <span class="sidebar-text">Admins</span>
                    </a>
                    <a href="logout.php" class="menu-item flex items-center space-x-3 px-3 py-2 rounded hover:bg-gray-700 transition duration-200">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="sidebar-text">Logout</span>
                    </a>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content ml-64 flex-1 p-8 transition-all duration-300">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800 animate__animated animate__fadeIn">Dashboard Overview</h1>
                <div class="flex items-center space-x-4">
                    <button id="sidebar-toggle" class="text-gray-600 hover:text-gray-900 focus:outline-none transition-transform hover:scale-110">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="relative w-64">
                        <form method="get" action="dashboard.php" class="relative">
                            <input type="text" name="search" placeholder="Search students..." 
                                   class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                                   value="<?php echo htmlspecialchars($search_query); ?>"
                                   id="search-input">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </form>
                        <?php if (!empty($search_query) && !empty($search_results)): ?>
                        <div class="search-dropdown bg-white shadow-lg rounded-lg mt-1 border border-gray-200 animate__animated animate__fadeIn">
                            <div class="p-2 border-b border-gray-100 bg-gray-50">
                                <p class="text-sm font-medium text-gray-700">Search Results</p>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <?php foreach ($search_results as $student): ?>
                                <a href="student_details.php?id=<?php echo $student['student_id']; ?>" class="block p-3 hover:bg-blue-50 transition duration-150">
                                    <div class="flex items-center space-x-3">
                                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-gray-600 text-sm"><?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?></span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['first_name'] . ' ' . htmlspecialchars($student['last_name'])); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($student['type_name'] ?? 'No membership'); ?></p>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6 flex items-center space-x-4 card-hover transition-all duration-300 hover:shadow-md animate__animated animate__fadeIn animate__delay-1s">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 shadow-inner">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Total Students</p>
                        <h3 class="text-2xl font-bold"><?php echo $total_students; ?></h3>
                        <a href="students.php" class="text-xs text-blue-600 hover:underline">View all</a>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 flex items-center space-x-4 card-hover transition-all duration-300 hover:shadow-md animate__animated animate__fadeIn animate__delay-2s">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 shadow-inner">
                        <i class="fas fa-id-card text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Active Memberships</p>
                        <h3 class="text-2xl font-bold"><?php echo $active_memberships; ?></h3>
                        <a href="students.php?status=active" class="text-xs text-green-600 hover:underline">View active</a>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 flex items-center space-x-4 card-hover transition-all duration-300 hover:shadow-md animate__animated animate__fadeIn animate__delay-3s">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 shadow-inner">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Expiring Soon</p>
                        <h3 class="text-2xl font-bold"><?php echo $expiring_soon; ?></h3>
                        <a href="payments.php?filter=expiring" class="text-xs text-yellow-600 hover:underline">View expiring</a>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 flex items-center space-x-4 card-hover transition-all duration-300 hover:shadow-md animate__animated animate__fadeIn animate__delay-4s">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 shadow-inner">
                        <i class="fas fa-user-slash text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Inactive Students</p>
                        <h3 class="text-2xl font-bold"><?php echo $inactive_students; ?></h3>
                        <a href="students.php?status=inactive" class="text-xs text-red-600 hover:underline">View inactive</a>
                    </div>
                </div>
            </div>
            
            <!-- Recent Students -->
            <div class="bg-white rounded-lg shadow p-6 mb-8 animate__animated animate__fadeIn">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Recent Students</h2>
                    <div class="flex space-x-2">
                        <a href="students.php" class="text-blue-600 hover:text-blue-800 flex items-center space-x-1">
                            <span>View All</span>
                            <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                        <a href="add_student.php" class="text-green-600 hover:text-green-800 flex items-center space-x-1">
                            <span>Add New</span>
                            <i class="fas fa-plus text-xs"></i>
                        </a>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membership</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Join Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $stmt = $pdo->query("SELECT s.*, m.type_name FROM students s 
                                                  LEFT JOIN membership_types m ON s.membership_type_id = m.type_id 
                                                  ORDER BY s.join_date DESC LIMIT 5");
                            while ($student = $stmt->fetch()):
                            ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center shadow-inner">
                                            <span class="text-gray-600"><?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?></span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['first_name'] . ' ' . htmlspecialchars($student['last_name'])); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($student['email']); ?></div>
                                        </div>
                                    </div>
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
                                    <div class="flex space-x-3">
                                        <a href="student_details.php?id=<?php echo $student['student_id']; ?>" class="text-blue-600 hover:text-blue-900 transition duration-200" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_student.php?id=<?php echo $student['student_id']; ?>" class="text-indigo-600 hover:text-indigo-900 transition duration-200" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($student['is_active']): ?>
                                            <a href="deactivate_student.php?id=<?php echo $student['student_id']; ?>" class="text-yellow-600 hover:text-yellow-900 transition duration-200" title="Deactivate">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="activate_student.php?id=<?php echo $student['student_id']; ?>" class="text-green-600 hover:text-green-900 transition duration-200" title="Activate">
                                                <i class="fas fa-user-check"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Expiring Memberships -->
            <div class="bg-white rounded-lg shadow p-6 animate__animated animate__fadeIn">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Expiring Memberships</h2>
                    <div class="flex space-x-2">
                        <a href="payments.php" class="text-blue-600 hover:text-blue-800 flex items-center space-x-1">
                            <span>View All</span>
                            <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                        <a href="memberships.php" class="text-purple-600 hover:text-purple-800 flex items-center space-x-1">
                            <span>Manage Memberships</span>
                            <i class="fas fa-cog text-xs"></i>
                        </a>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Membership</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Payment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires In</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $stmt = $pdo->query("SELECT s.student_id, s.first_name, s.last_name, m.type_name, 
                                                  MAX(p.payment_date) as last_payment, 
                                                  m.duration_days,
                                                  DATEDIFF(DATE_ADD(MAX(p.payment_date), INTERVAL m.duration_days DAY), CURDATE()) as days_remaining
                                                  FROM students s 
                                                  JOIN payments p ON s.student_id = p.student_id 
                                                  JOIN membership_types m ON s.membership_type_id = m.type_id 
                                                  WHERE s.is_active = 1 
                                                  GROUP BY s.student_id 
                                                  HAVING days_remaining BETWEEN 0 AND 7 
                                                  ORDER BY days_remaining ASC 
                                                  LIMIT 5");
                            while ($student = $stmt->fetch()):
                                $days_remaining = $student['days_remaining'];
                                $expiry_class = $days_remaining <= 3 ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800';
                            ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center shadow-inner">
                                            <span class="text-gray-600"><?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?></span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['first_name'] . ' ' . htmlspecialchars($student['last_name'])); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($student['type_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($student['last_payment'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $expiry_class; ?>">
                                        <?php echo $days_remaining == 0 ? 'Today' : ($days_remaining . ' day' . ($days_remaining == 1 ? '' : 's')); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-3">
                                        <a href="record_payment.php?student_id=<?php echo $student['student_id']; ?>" class="text-green-600 hover:text-green-900 transition duration-200" title="Record Payment">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                        <a href="student_details.php?id=<?php echo $student['student_id']; ?>" class="text-blue-600 hover:text-blue-900 transition duration-200" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.tailwindcss.min.js"></script>
    <script>
        // Toggle sidebar
        $('#sidebar-toggle').click(function() {
            $('.sidebar').toggleClass('collapsed');
            $('.main-content').toggleClass('ml-64');
        });
        
        // Auto-expand sidebar on larger screens
        function handleSidebar() {
            if (window.innerWidth < 768) {
                $('.sidebar').addClass('collapsed');
                $('.main-content').removeClass('ml-64');
            } else {
                $('.sidebar').removeClass('collapsed');
                $('.main-content').addClass('ml-64');
            }
        }
        
        // Run on load and resize
        $(document).ready(handleSidebar);
        $(window).resize(handleSidebar);
        
        // Search functionality
        $(document).ready(function() {
            const searchInput = $('#search-input');
            const searchDropdown = $('.search-dropdown');
            
            // Show/hide dropdown based on input
            searchInput.on('focus input', function() {
                if ($(this).val().length > 0) {
                    searchDropdown.fadeIn(200);
                } else {
                    searchDropdown.fadeOut(200);
                }
            });
            
            // Hide dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.relative').length) {
                    searchDropdown.fadeOut(200);
                }
            });
            
            // Add animation to cards on hover
            $('.card-hover').hover(
                function() {
                    $(this).addClass('transform transition duration-300 hover:-translate-y-1 hover:shadow-lg');
                },
                function() {
                    $(this).removeClass('transform transition duration-300 hover:-translate-y-1 hover:shadow-lg');
                }
            );
        });
    </script>
</body>
</html>