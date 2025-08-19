<?php
require_once 'auth_check.php';

// Only allow the main admin to manage other admins
if ($_SESSION['username'] !== 'admin') {
    $_SESSION['error_message'] = 'Only the main admin can manage other admins';
    header('Location: dashboard.php');
    exit();
}

// Handle search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$params = [];

if (!empty($search_query)) {
    $where_clause = "WHERE username LIKE ? OR email LIKE ?";
    $search_param = "%$search_query%";
    $params = [$search_param, $search_param];
}

// Get all admins
$query = "SELECT * FROM admins $where_clause ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$admins = $stmt->fetchAll();

// Handle admin deletion
if (isset($_GET['delete'])) {
    $admin_id = $_GET['delete'];
    
    // Prevent deleting the main admin
    if ($admin_id == 1) {
        $_SESSION['error_message'] = 'Cannot delete the main admin account';
        header('Location: admins.php');
        exit();
    }
    
    $stmt = $pdo->prepare("DELETE FROM admins WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    
    $_SESSION['success_message'] = 'Admin deleted successfully!';
    header('Location: admins.php');
    exit();
}

// Handle admin activation/deactivation
if (isset($_GET['toggle_status'])) {
    $admin_id = $_GET['toggle_status'];
    
    // Prevent deactivating the main admin
    if ($admin_id == 1) {
        $_SESSION['error_message'] = 'Cannot deactivate the main admin account';
        header('Location: admins.php');
        exit();
    }
    
    $stmt = $pdo->prepare("UPDATE admins SET is_active = NOT is_active WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    
    $_SESSION['success_message'] = 'Admin status updated successfully!';
    header('Location: admins.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - MMA Gym Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.tailwindcss.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .card-hover:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .action-btn {
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            transform: scale(1.1);
        }
        .status-badge {
            transition: all 0.2s ease;
        }
        .status-badge:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content ml-64 flex-1 p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800 animate__animated animate__fadeIn">Admin Management</h1>
            <div class="flex items-center space-x-4">
                <button id="sidebar-toggle" class="text-gray-600 hover:text-gray-900 focus:outline-none transition-transform hover:scale-110">
                    <i class="fas fa-bars"></i>
                </button>
                <form method="get" action="admins.php" class="relative">
                    <div class="relative">
                        <input type="text" name="search" placeholder="Search admins..." 
                               class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 w-64"
                               value="<?php echo htmlspecialchars($search_query); ?>">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        <?php if (!empty($search_query)): ?>
                            <a href="admins.php" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
                <a href="add_admin.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-all duration-200 hover:shadow-md">
                    <i class="fas fa-plus"></i>
                    <span>Add Admin</span>
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 animate__animated animate__fadeIn">
                <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 animate__animated animate__fadeIn">
                <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Admins Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden animate__animated animate__fadeIn">
            <div class="overflow-x-auto">
                <table id="admins-table" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($admins as $admin): ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center shadow-inner">
                                        <span class="text-gray-600"><?php echo strtoupper(substr($admin['username'], 0, 1)); ?></span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($admin['username']); ?></div>
                                        <?php if ($admin['admin_id'] == 1): ?>
                                            <span class="text-xs text-gray-500">Main Admin</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($admin['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-badge <?php echo $admin['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-4">
                                    <?php if ($admin['admin_id'] != 1): ?>
                                        <a href="admins.php?toggle_status=<?php echo $admin['admin_id']; ?>" 
                                           class="text-indigo-600 hover:text-indigo-900 action-btn" 
                                           title="<?php echo $admin['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                            <i class="fas <?php echo $admin['is_active'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                        </a>
                                        <a href="admins.php?delete=<?php echo $admin['admin_id']; ?>" 
                                           class="text-red-600 hover:text-red-900 action-btn" 
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this admin?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                        <a href="edit_admin.php?id=<?php echo $admin['admin_id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900 action-btn" 
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">Main Admin</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($admins)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-user-shield text-4xl mb-3 text-gray-300"></i>
                    <p class="text-lg">No admins found</p>
                    <?php if (!empty($search_query)): ?>
                        <p class="mt-2">Try a different search term or <a href="admins.php" class="text-blue-600 hover:underline">clear search</a></p>
                    <?php else: ?>
                        <p class="mt-2">Add your first admin using the "Add Admin" button</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.tailwindcss.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#admins-table').DataTable({
                responsive: true,
                order: [[2, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [4] }
                ],
                dom: '<"flex justify-between items-center mb-4"<"text-sm text-gray-600"i><"text-sm"f>><"bg-white rounded-lg shadow overflow-hidden"rt><"flex justify-between items-center mt-4"<"text-sm text-gray-600"l><"text-sm"p>>',
                language: {
                    search: "",
                    searchPlaceholder: "Search within table...",
                    lengthMenu: "Show _MENU_ admins",
                    info: "Showing _START_ to _END_ of _TOTAL_ admins",
                    infoEmpty: "No admins found",
                    infoFiltered: "(filtered from _MAX_ total admins)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Prev"
                    }
                },
                initComplete: function() {
                    $('.dataTables_filter input').addClass('pl-8 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500');
                    $('.dataTables_filter').prepend('<i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>');
                    $('.dataTables_filter').addClass('relative');
                }
            });
            
            // Add animation to table rows
            $('#admins-table tbody tr').each(function(i) {
                $(this).delay(i * 50).fadeIn();
            });
        });
    </script>
    <script src="js/sidebar.js"></script>
</body>
</html>