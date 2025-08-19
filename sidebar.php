<?php
// This is included at the top of each page
?>
<div class="sidebar bg-gray-800 text-white h-screen fixed top-0 left-0 w-64 overflow-y-auto overflow-x-hidden z-10">
    <div class="p-4 flex items-center space-x-2 border-b border-gray-700">
        <i class="fas fa-fist-raised text-2xl text-blue-400"></i>
        <span class="logo-text text-xl font-bold">MMA Gym</span>
    </div>
    
    <div class="p-4">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center">
                <span class="text-white font-bold"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></span>
            </div>
            <div>
                <div class="font-medium"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <div class="text-xs text-gray-400">Admin</div>
            </div>
        </div>
        
        <nav class="space-y-1">
            <a href="dashboard.php" class="menu-item flex items-center space-x-3 px-3 py-2 rounded hover:bg-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active-menu' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span class="sidebar-text">Dashboard</span>
            </a>
            <a href="students.php" class="menu-item flex items-center space-x-3 px-3 py-2 rounded hover:bg-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active-menu' : ''; ?>">
                <i class="fas fa-users"></i>
                <span class="sidebar-text">Students</span>
            </a>
            <a href="payments.php" class="menu-item flex items-center space-x-3 px-3 py-2 rounded hover:bg-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active-menu' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span class="sidebar-text">Payments</span>
            </a>
            <a href="memberships.php" class="menu-item flex items-center space-x-3 px-3 py-2 rounded hover:bg-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == 'memberships.php' ? 'active-menu' : ''; ?>">
                <i class="fas fa-id-card"></i>
                <span class="sidebar-text">Memberships</span>
            </a>
            <?php if ($_SESSION['username'] === 'admin'): ?>
                <a href="admins.php" class="menu-item flex items-center space-x-3 px-3 py-2 rounded hover:bg-gray-700 transition <?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active-menu' : ''; ?>">
                    <i class="fas fa-user-shield"></i>
                    <span class="sidebar-text">Admins</span>
                </a>
            <?php endif; ?>
            <a href="logout.php" class="menu-item flex items-center space-x-3 px-3 py-2 rounded hover:bg-gray-700 transition">
                <i class="fas fa-sign-out-alt"></i>
                <span class="sidebar-text">Logout</span>
            </a>
        </nav>
    </div>
</div>