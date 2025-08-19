<?php
require_once 'auth_check.php';

if (!isset($_GET['id'])) {
    header('Location: students.php');
    exit();
}

$student_id = $_GET['id'];

// Get student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error_message'] = 'Student not found';
    header('Location: students.php');
    exit();
}

// Get membership types for dropdown
$membership_types = $pdo->query("SELECT * FROM membership_types WHERE is_active = 1")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $date_of_birth = $_POST['date_of_birth'];
    $membership_type_id = $_POST['membership_type_id'] ?: null;
    $join_date = $_POST['join_date'];
    $notes = trim($_POST['notes']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Basic validation
    $errors = [];
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE students 
                                  SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?, 
                                      date_of_birth = ?, membership_type_id = ?, join_date = ?, notes = ?, is_active = ?
                                  WHERE student_id = ?");
            $stmt->execute([
                $first_name, $last_name, $email, $phone, $address, 
                $date_of_birth, $membership_type_id, $join_date, $notes, $is_active,
                $student_id
            ]);
            
            $_SESSION['success_message'] = 'Student updated successfully!';
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
    <title>Edit Student - MMA Gym Management</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content ml-64 flex-1 p-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Edit Student</h1>
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
            <form method="post" action="edit_student.php?id=<?php echo $student_id; ?>" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo htmlspecialchars($student['first_name']); ?>">
                    </div>
                    
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo htmlspecialchars($student['last_name']); ?>">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="email" name="email"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo htmlspecialchars($student['email']); ?>">
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" id="phone" name="phone"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo htmlspecialchars($student['phone']); ?>">
                    </div>
                    
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo htmlspecialchars($student['date_of_birth']); ?>">
                    </div>
                    
                    <div>
                        <label for="join_date" class="block text-sm font-medium text-gray-700 mb-1">Join Date</label>
                        <input type="date" id="join_date" name="join_date"
                               class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo htmlspecialchars($student['join_date']); ?>">
                    </div>
                    
                    <div>
                        <label for="membership_type_id" class="block text-sm font-medium text-gray-700 mb-1">Membership Type</label>
                        <select id="membership_type_id" name="membership_type_id"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Select Membership --</option>
                            <?php foreach ($membership_types as $type): ?>
                                <option value="<?php echo $type['type_id']; ?>" <?php echo $student['membership_type_id'] == $type['type_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['type_name']); ?> (<?php echo '$' . $type['price']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                               <?php echo $student['is_active'] ? 'checked' : ''; ?>>
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">Active Student</label>
                    </div>
                </div>
                
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea id="address" name="address" rows="2"
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($student['address']); ?></textarea>
                </div>
                
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea id="notes" name="notes" rows="3"
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($student['notes']); ?></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg flex items-center space-x-2">
                        <i class="fas fa-save"></i>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/sidebar.js"></script>
</body>
</html>