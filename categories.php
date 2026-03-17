<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    redirect('auth/login.php');
}

$page_title = 'จัดการหมวดหมู่';

// ดึงข้อมูลหมวดหมู่
$stmt = $pdo->prepare("SELECT c.*, u.full_name as creator, COUNT(p.id) as product_count 
                       FROM categories c 
                       LEFT JOIN users u ON c.created_by = u.id 
                       LEFT JOIN products p ON c.id = p.category_id 
                       GROUP BY c.id 
                       ORDER BY c.created_at DESC");
$stmt->execute();
$categories = $stmt->fetchAll();
?>

<?php include 'includes/header.php'; ?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
    <h2 class="text-2xl font-bold text-gray-800 flex items-center">
        <i class="fas fa-folder mr-3 text-blue-500"></i>
        จัดการหมวดหมู่
    </h2>
    <button onclick="openModal()" 
            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200 flex items-center">
        <i class="fas fa-plus mr-2"></i>เพิ่มหมวดหมู่
    </button>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">หมวดหมู่ทั้งหมด</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo count($categories); ?></p>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
                <i class="fas fa-folder text-blue-500"></i>
            </div>
        </div>
    </div>

    <?php
    // นับหมวดหมู่ตามจำนวนสินค้า
    $empty_categories = 0;
    $has_products_categories = 0;
    $popular_categories = 0;
    
    foreach ($categories as $category) {
        if ($category['product_count'] == 0) {
            $empty_categories++;
        } elseif ($category['product_count'] <= 5) {
            $has_products_categories++;
        } else {
            $popular_categories++;
        }
    }
    ?>

    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">หมวดหมู่ยอดนิยม</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $popular_categories; ?></p>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
                <i class="fas fa-star text-green-500"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">มีสินค้า</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $has_products_categories; ?></p>
            </div>
            <div class="bg-orange-100 p-3 rounded-full">
                <i class="fas fa-boxes text-orange-500"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-4 border-l-4 border-gray-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">หมวดหมู่ว่าง</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $empty_categories; ?></p>
            </div>
            <div class="bg-gray-100 p-3 rounded-full">
                <i class="fas fa-folder-open text-gray-500"></i>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หมวดหมู่</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">คำอธิบาย</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนสินค้า</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ผู้สร้าง</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่สร้าง</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">การดำเนินการ</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-folder-open text-4xl mb-3 text-gray-300"></i>
                            <p class="text-lg mb-2">ยังไม่มีหมวดหมู่</p>
                            <p class="text-sm text-gray-400">เริ่มต้นโดยการเพิ่มหมวดหมู่ใหม่</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                    <tr class="hover:bg-gray-50 transition duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center mr-3 shadow-sm" 
                                     style="background-color: <?php echo $category['color']; ?>20;">
                                    <i class="<?php echo $category['icon']; ?> text-lg" 
                                       style="color: <?php echo $category['color']; ?>"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo escape($category['name']); ?></div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-palette mr-1" style="color: <?php echo $category['color']; ?>"></i>
                                        <?php echo $category['color']; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs"><?php echo escape($category['description']); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                            $count_class = 'bg-blue-100 text-blue-800';
                            $count_icon = 'fa-boxes';
                            
                            if ($category['product_count'] == 0) {
                                $count_class = 'bg-gray-100 text-gray-800';
                                $count_icon = 'fa-box-open';
                            } elseif ($category['product_count'] > 10) {
                                $count_class = 'bg-green-100 text-green-800';
                                $count_icon = 'fa-star';
                            }
                            ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold <?php echo $count_class; ?>">
                                <i class="fas <?php echo $count_icon; ?> mr-1"></i>
                                <?php echo $category['product_count']; ?> สินค้า
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo escape($category['creator']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y', strtotime($category['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="editCategory(<?php echo $category['id']; ?>)" 
                                    class="text-blue-600 hover:text-blue-900 mr-3 transition duration-200">
                                <i class="fas fa-edit mr-1"></i>แก้ไข
                            </button>
                            <button onclick="deleteCategory(<?php echo $category['id']; ?>)" 
                                    class="text-red-600 hover:text-red-900 transition duration-200">
                                <i class="fas fa-trash mr-1"></i>ลบ
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div id="categoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-xl bg-white">
        <div class="mt-3">
            <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4">เพิ่มหมวดหมู่ใหม่</h3>
            
            <form id="categoryForm" method="POST" action="actions/category_action.php">
                <input type="hidden" id="categoryId" name="id">
                <input type="hidden" name="action" id="formAction" value="create">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                        <i class="fas fa-tag mr-2 text-blue-500"></i>ชื่อหมวดหมู่
                    </label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                           placeholder="กรอกชื่อหมวดหมู่">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                        <i class="fas fa-align-left mr-2 text-purple-500"></i>คำอธิบาย
                    </label>
                    <textarea id="description" name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                              placeholder="รายละเอียดเกี่ยวกับหมวดหมู่..."></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="color">
                            <i class="fas fa-palette mr-2 text-green-500"></i>สี
                        </label>
                        <input type="color" id="color" name="color" value="#3B82F6"
                               class="w-full h-10 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="icon">
                            <i class="fas fa-icons mr-2 text-yellow-500"></i>ไอคอน
                        </label>
                        <input type="text" id="icon" name="icon" value="fas fa-folder"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="fas fa-folder">
                    </div>
                </div>

                <!-- Icon Preview -->
                <div class="mb-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        <i class="fas fa-eye mr-2 text-orange-500"></i>ตัวอย่างไอคอน
                    </label>
                    <div class="flex items-center justify-center p-4">
                        <div id="iconPreview" class="w-16 h-16 rounded-xl flex items-center justify-center shadow-md" 
                             style="background-color: #3B82F620;">
                            <i id="iconPreviewIcon" class="fas fa-folder text-2xl" style="color: #3B82F6"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 text-center mt-2">นี่คือตัวอย่างการแสดงผลไอคอน</p>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal()"
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
function openModal() {
    document.getElementById('categoryModal').classList.remove('hidden');
    document.getElementById('modalTitle').textContent = 'เพิ่มหมวดหมู่ใหม่';
    document.getElementById('formAction').value = 'create';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('color').value = '#3B82F6';
    document.getElementById('icon').value = 'fas fa-folder';
    updateIconPreview();
}

function closeModal() {
    document.getElementById('categoryModal').classList.add('hidden');
}

function editCategory(id) {
    // โหลดข้อมูลหมวดหมู่ผ่าน AJAX
    loadCategoryData(id);
}

function loadCategoryData(categoryId) {
    // แสดง loading
    document.getElementById('modalTitle').textContent = 'กำลังโหลด...';
    
    // ส่ง request ไปยัง API
    fetch(`actions/get_category.php?id=${categoryId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const category = data.category;
                
                // เติมข้อมูลลงใน form
                document.getElementById('categoryId').value = category.id;
                document.getElementById('name').value = category.name;
                document.getElementById('description').value = category.description;
                document.getElementById('color').value = category.color;
                document.getElementById('icon').value = category.icon;
                
                // อัพเดทตัวอย่างไอคอน
                updateIconPreview();
                
                // เปลี่ยน title
                document.getElementById('modalTitle').textContent = 'แก้ไขหมวดหมู่: ' + category.name;
                document.getElementById('formAction').value = 'update';
                
                // แสดง modal
                document.getElementById('categoryModal').classList.remove('hidden');
            } else {
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด!',
                    text: data.message || 'ไม่สามารถโหลดข้อมูลหมวดหมู่ได้',
                    icon: 'error',
                    confirmButtonText: 'ตกลง'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'เกิดข้อผิดพลาด!',
                text: 'ไม่สามารถโหลดข้อมูลหมวดหมู่ได้ กรุณาลองอีกครั้ง',
                icon: 'error',
                confirmButtonText: 'ตกลง'
            });
        });
}

function deleteCategory(id) {
    // หาชื่อหมวดหมู่จากแถวในตาราง
    const categoryRow = event.target.closest('tr');
    const categoryName = categoryRow.querySelector('.text-sm.font-medium').textContent;
    const productCount = categoryRow.querySelector('.inline-flex').textContent.split(' ')[0];
    
    let warningText = 'การลบหมวดหมู่นี้ไม่สามารถย้อนกลับได้';
    if (parseInt(productCount) > 0) {
        warningText = `หมวดหมู่นี้มี ${productCount} สินค้า การลบจะทำให้สินค้าเหล่านี้ไม่มีหมวดหมู่!`;
    }
    
    Swal.fire({
        title: 'ยืนยันการลบหมวดหมู่',
        html: `
            <div class="text-center py-2">
                <div class="w-16 h-16 mx-auto mb-3 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-xl text-red-600"></i>
                </div>
                <p class="text-lg font-semibold text-gray-800 mb-1">คุณต้องการลบหมวดหมู่</p>
                <p class="text-red-600 font-bold text-lg">${categoryName}</p>
                <p class="text-gray-500 text-sm mt-2">${warningText}</p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบหมวดหมู่',
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
                window.location.href = 'actions/category_action.php?action=delete&id=' + id;
            }, 1000);
        }
    });
}

// อัพเดทตัวอย่างไอคอนเมื่อเปลี่ยนสีหรือไอคอน
function updateIconPreview() {
    const color = document.getElementById('color').value;
    const icon = document.getElementById('icon').value;
    
    const preview = document.getElementById('iconPreview');
    const previewIcon = document.getElementById('iconPreviewIcon');
    
    preview.style.backgroundColor = color + '20';
    previewIcon.className = icon + ' text-2xl';
    previewIcon.style.color = color;
}

// Event listeners สำหรับอัพเดทตัวอย่างไอคอน
document.getElementById('color').addEventListener('input', updateIconPreview);
document.getElementById('icon').addEventListener('input', updateIconPreview);

// ปิด modal เมื่อคลิกนอกพื้นที่
window.onclick = function(event) {
    const modal = document.getElementById('categoryModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Form validation
document.getElementById('categoryForm').addEventListener('submit', function(e) {
    const name = document.getElementById('name').value.trim();
    const icon = document.getElementById('icon').value.trim();
    
    if (name.length < 2) {
        e.preventDefault();
        Swal.fire({
            title: 'ข้อผิดพลาด!',
            text: 'ชื่อหมวดหมู่ต้องมีความยาวอย่างน้อย 2 ตัวอักษร',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
        return false;
    }
    
    if (!icon.startsWith('fas fa-')) {
        e.preventDefault();
        Swal.fire({
            title: 'ข้อผิดพลาด!',
            text: 'รูปแบบไอคอนไม่ถูกต้อง ต้องขึ้นต้นด้วย "fas fa-"',
            icon: 'error',
            confirmButtonText: 'ตกลง'
        });
        return false;
    }
});
</script>

<?php include 'includes/footer.php'; ?>