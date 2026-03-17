<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

// อนุญาตเฉพาะ admin
checkRole(['admin']);

$page_title = 'จัดการผู้ใช้งาน';

// ตัวแปรสำหรับการกรองและค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// สร้างเงื่อนไข WHERE
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter)) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
}

$where_sql = '';
if (!empty($where_conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
}

// สร้างเงื่อนไข ORDER BY
$order_sql = '';
switch ($sort) {
    case 'name_asc':
        $order_sql = 'ORDER BY u.full_name ASC';
        break;
    case 'name_desc':
        $order_sql = 'ORDER BY u.full_name DESC';
        break;
    case 'username_asc':
        $order_sql = 'ORDER BY u.username ASC';
        break;
    case 'username_desc':
        $order_sql = 'ORDER BY u.username DESC';
        break;
    case 'oldest':
        $order_sql = 'ORDER BY u.created_at ASC';
        break;
    default:
        $order_sql = 'ORDER BY u.created_at DESC';
}

// ดึงข้อมูลผู้ใช้งาน
$stmt = $pdo->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM products WHERE created_by = u.id) as product_count,
           (SELECT COUNT(*) FROM categories WHERE created_by = u.id) as category_count
    FROM users u 
    $where_sql 
    $order_sql
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// นับจำนวนผู้ใช้งานทั้งหมด
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users u $where_sql");
$stmt->execute($params);
$total_users = $stmt->fetchColumn();

// นับผู้ใช้งานตามบทบาท
$admin_count = 0;
$user_count = 0;
$active_users = 0;

foreach ($users as $user) {
    if ($user['role'] == 'admin') {
        $admin_count++;
    } else {
        $user_count++;
    }
    $active_users++; // ในระบบจริงอาจมีฟิลด์สถานะ
}
?>

<?php include 'includes/header.php'; ?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
        <i class="fas fa-users mr-3 text-blue-500"></i>
        จัดการผู้ใช้งาน
    </h2>
    <button onclick="openUserModal()" 
            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200 flex items-center">
        <i class="fas fa-user-plus mr-2"></i>เพิ่มผู้ใช้งาน
    </button>
</div>

<!-- Search and Filter Section -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-6">
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search -->
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="search">
                <i class="fas fa-search mr-2 text-blue-500"></i>ค้นหาผู้ใช้งาน
            </label>
            <input type="text" id="search" name="search" 
                   value="<?php echo escape($search); ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="ค้นหาชื่อ, ชื่อผู้ใช้, หรืออีเมล...">
        </div>

        <!-- Role Filter -->
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                <i class="fas fa-user-tag mr-2 text-green-500"></i>บทบาท
            </label>
            <select id="role" name="role" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">ทั้งหมด</option>
                <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>ผู้ดูแลระบบ</option>
                <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>ผู้ใช้ทั่วไป</option>
            </select>
        </div>

        <!-- Sort -->
        <div>
            <label class="block text-gray-700 text-sm font-bold mb-2" for="sort">
                <i class="fas fa-sort mr-2 text-purple-500"></i>เรียงตาม
            </label>
            <select id="sort" name="sort" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>ใหม่ล่าสุด</option>
                <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>เก่าที่สุด</option>
                <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>ชื่อ A-Z</option>
                <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>ชื่อ Z-A</option>
                <option value="username_asc" <?php echo $sort == 'username_asc' ? 'selected' : ''; ?>>ชื่อผู้ใช้ A-Z</option>
                <option value="username_desc" <?php echo $sort == 'username_desc' ? 'selected' : ''; ?>>ชื่อผู้ใช้ Z-A</option>
            </select>
        </div>

        <!-- Buttons -->
        <div class="flex items-end space-x-2">
            <button type="submit" 
                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200 flex items-center flex-1 justify-center">
                <i class="fas fa-filter mr-2"></i>กรอง
            </button>
            <a href="users.php" 
               class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200 flex items-center">
                <i class="fas fa-refresh mr-2"></i>
            </a>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">ผู้ใช้งานทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $total_users; ?></p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-users text-blue-500"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">ผู้ดูแลระบบ</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $admin_count; ?></p>
            </div>
            <div class="bg-purple-100 p-3 rounded-full">
                <i class="fas fa-crown text-purple-500"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">ผู้ใช้ทั่วไป</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $user_count; ?></p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <i class="fas fa-user text-green-500"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">ใช้งานล่าสุด</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $active_users; ?></p>
            </div>
            <div class="bg-orange-100 p-3 rounded-full">
                <i class="fas fa-clock text-orange-500"></i>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้ใช้งาน</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ข้อมูลติดต่อ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">บทบาท</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถิติ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่สมัคร</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-user-slash text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg mb-2">ไม่พบผู้ใช้งาน</p>
                            <p class="text-sm text-gray-400">ลองเปลี่ยนเงื่อนไขการค้นหาหรือเพิ่มผู้ใช้งานใหม่</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-100 to-purple-100 rounded-xl flex items-center justify-center mr-3 shadow-sm">
                                    <i class="fas fa-user text-blue-500 text-lg"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo escape($user['full_name']); ?></div>
                                    <div class="text-sm text-gray-500">@<?php echo escape($user['username']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <div class="flex items-center mb-1">
                                    <i class="fas fa-envelope text-gray-400 mr-2 w-4"></i>
                                    <span class="truncate max-w-xs"><?php echo escape($user['email']); ?></span>
                                </div>
                                <?php if (!empty($user['phone'])): ?>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-phone text-gray-400 mr-2 w-4"></i>
                                    <span><?php echo escape($user['phone']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $role_class = $user['role'] == 'admin' ? 
                                'bg-purple-100 text-purple-800 border-purple-200' : 
                                'bg-blue-100 text-blue-800 border-blue-200';
                            $role_icon = $user['role'] == 'admin' ? 'fa-crown' : 'fa-user';
                            ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border <?php echo $role_class; ?>">
                                <i class="fas <?php echo $role_icon; ?> mr-1"></i>
                                <?php echo $user['role'] == 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้ใช้ทั่วไป'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex space-x-2 text-xs">
                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-green-100 text-green-800">
                                    <i class="fas fa-box mr-1"></i>
                                    <?php echo $user['product_count']; ?>
                                </span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-blue-100 text-blue-800">
                                    <i class="fas fa-folder mr-1"></i>
                                    <?php echo $user['category_count']; ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex flex-col">
                                <span><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                                <span class="text-xs text-gray-400"><?php echo date('H:i', strtotime($user['created_at'])); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="editUser(<?php echo $user['id']; ?>)" 
                                    class="text-blue-600 hover:text-blue-900 mr-3 transition duration-200">
                                <i class="fas fa-edit mr-1"></i>แก้ไข
                            </button>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <button onclick="deleteUser(<?php echo $user['id']; ?>)" 
                                    class="text-red-600 hover:text-red-900 transition duration-200">
                                <i class="fas fa-trash mr-1"></i>ลบ
                            </button>
                            <?php else: ?>
                            <span class="text-gray-400 text-xs">บัญชีปัจจุบัน</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div id="userModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-xl bg-white">
        <div class="mt-3">
            <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">เพิ่มผู้ใช้งานใหม่</h3>
            
            <form id="userForm" method="POST" action="actions/user_action.php">
                <input type="hidden" id="userId" name="id">
                <input type="hidden" name="action" id="formAction" value="create">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="full_name">
                            <i class="fas fa-user mr-2 text-blue-500"></i>ชื่อ-นามสกุล
                        </label>
                        <input type="text" id="full_name" name="full_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="กรอกชื่อ-นามสกุล">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="username">
                            <i class="fas fa-user-tag mr-2 text-green-500"></i>ชื่อผู้ใช้
                        </label>
                        <input type="text" id="username" name="username" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="กรอกชื่อผู้ใช้">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            <i class="fas fa-envelope mr-2 text-purple-500"></i>อีเมล
                        </label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="กรอกอีเมล">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="phone">
                            <i class="fas fa-phone mr-2 text-orange-500"></i>เบอร์โทรศัพท์
                        </label>
                        <input type="tel" id="phone" name="phone"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="กรอกเบอร์โทรศัพท์">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div id="passwordSection">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                            <i class="fas fa-lock mr-2 text-red-500"></i>รหัสผ่าน
                        </label>
                        <input type="password" id="password" name="password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="กรอกรหัสผ่าน">
                        <p class="text-xs text-gray-500 mt-1" id="passwordHelp">เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยนรหัสผ่าน</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                            <i class="fas fa-user-shield mr-2 text-yellow-500"></i>บทบาท
                        </label>
                        <select id="role" name="role" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                            <option value="user">ผู้ใช้ทั่วไป</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeUserModal()"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                        ยกเลิก
                    </button>
                    <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                        บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openUserModal() {
    document.getElementById('userModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'เพิ่มผู้ใช้งานใหม่';
    document.getElementById('formAction').value = 'create';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('password').required = true;
    document.getElementById('passwordHelp').textContent = 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
}

function closeUserModal() {
    document.getElementById('userModal').classList.add('hidden');
}

function editUser(id) {
    // โหลดข้อมูลผู้ใช้งานผ่าน AJAX
    loadUserData(id);
}

function loadUserData(userId) {
    // แสดง loading
    const modalTitle = document.getElementById('modalTitle');
    modalTitle.innerHTML = '<div class="flex items-center justify-center"><i class="fas fa-spinner fa-spin mr-2"></i>กำลังโหลด...</div>';
    
    // ส่ง request ไปยัง API
    fetch(`actions/get_user.php?id=${userId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const user = data.user;
                
                // เติมข้อมูลลงใน form
                document.getElementById('userId').value = user.id;
                document.getElementById('full_name').value = user.full_name;
                document.getElementById('username').value = user.username;
                document.getElementById('email').value = user.email;
                document.getElementById('phone').value = user.phone || '';
                document.getElementById('role').value = user.role;
                
                // ปรับเปลี่ยนฟิลด์รหัสผ่านสำหรับการแก้ไข
                document.getElementById('password').required = false;
                document.getElementById('passwordHelp').textContent = 'เว้นว่างไว้ถ้าไม่ต้องการเปลี่ยนรหัสผ่าน';
                
                // เปลี่ยน title
                modalTitle.textContent = 'แก้ไขผู้ใช้งาน: ' + user.full_name;
                document.getElementById('formAction').value = 'update';
                
                // แสดง modal
                document.getElementById('userModal').classList.remove('hidden');
            } else {
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด!',
                    text: data.message || 'ไม่สามารถโหลดข้อมูลผู้ใช้งานได้',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: 'ไม่สามารถโหลดข้อมูลผู้ใช้งานได้ กรุณาลองอีกครั้ง',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
        });
}

function deleteUser(id) {
    // หาชื่อผู้ใช้งานจากแถวในตาราง
    const userRow = event.target.closest('tr');
    const userName = userRow.querySelector('.text-sm.font-medium').textContent;
    const userRole = userRow.querySelector('.inline-flex').textContent;
    
    Swal.fire({
        title: 'ยืนยันการลบผู้ใช้งาน',
        html: `
            <div class="text-center py-2">
                <div class="w-16 h-16 mx-auto mb-3 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-slash text-xl text-red-600"></i>
                </div>
                <p class="text-lg font-semibold text-gray-800 mb-1">คุณต้องการลบผู้ใช้งาน</p>
                <p class="text-red-600 font-bold text-lg">${userName}</p>
                <p class="text-gray-600 mb-2">${userRole}</p>
                <p class="text-gray-500 text-sm mt-2">การลบผู้ใช้งานนี้จะไม่สามารถย้อนกลับได้</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบผู้ใช้งาน',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        reverseButtons: true,
        customClass: {
            confirmButton: 'px-4 py-2 rounded-lg font-semibold',
            cancelButton: 'px-4 py-2 rounded-lg font-semibold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // แสดง loading
            Swal.fire({
                title: 'กำลังลบ...',
                text: 'กรุณารอสักครู่',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // ส่งคำขอลบ
            setTimeout(() => {
                window.location.href = 'actions/user_action.php?action=delete&id=' + id;
            }, 1000);
        }
    });
}

// ปิด modal เมื่อคลิกนอกพื้นที่
window.onclick = function(event) {
    const modal = document.getElementById('userModal');
    if (event.target === modal) {
        closeUserModal();
    }
}

// Form validation
document.getElementById('userForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const formAction = document.getElementById('formAction').value;
    
    // ตรวจสอบรหัสผ่านสำหรับการสร้างใหม่
    if (formAction === 'create' && password.length < 6) {
        e.preventDefault();
        Swal.fire({
            title: 'ข้อผิดพลาด!',
            text: 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
        return false;
    }
    
    // ตรวจสอบรหัสผ่านสำหรับการแก้ไข (ถ้ามีการกรอก)
    if (formAction === 'update' && password.length > 0 && password.length < 6) {
        e.preventDefault();
        Swal.fire({
            title: 'ข้อผิดพลาด!',
            text: 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
        return false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>