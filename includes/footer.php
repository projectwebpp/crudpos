        </main>
    </div>

    <!-- Mobile Navigation -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200">
        <div class="flex justify-around items-center py-2">
            <a href="dashboard.php" class="flex flex-col items-center text-gray-600 hover:text-blue-600 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'text-blue-600' : ''; ?>">
                <i class="fas fa-tachometer-alt text-lg"></i>
                <span class="text-xs mt-1">แดชบอร์ด</span>
            </a>
            <a href="categories.php" class="flex flex-col items-center text-gray-600 hover:text-blue-600 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'text-blue-600' : ''; ?>">
                <i class="fas fa-folder text-lg"></i>
                <span class="text-xs mt-1">หมวดหมู่</span>
            </a>
            <a href="products.php" class="flex flex-col items-center text-gray-600 hover:text-blue-600 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'text-blue-600' : ''; ?>">
                <i class="fas fa-box text-lg"></i>
                <span class="text-xs mt-1">สินค้า</span>
            </a>
            <a href="profile.php" class="flex flex-col items-center text-gray-600 hover:text-blue-600 transition duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'text-blue-600' : ''; ?>">
                <i class="fas fa-cog text-lg"></i>
                <span class="text-xs mt-1">ตั้งค่า</span>
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <i class="fas fa-laptop-code mr-2 text-blue-400"></i>
                        ครบเครื่องเรื่องไอที
                    </h3>
                    <p class="text-gray-400 mb-4 text-sm leading-relaxed">
                        ให้เราดูแลทุกความต้องการด้านไอทีของคุณ ด้วยบริการที่ครบวงจรและทีมงานมืออาชีพ
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition duration-200">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-200">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-200">
                            <i class="fab fa-line text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-200">
                            <i class="fab fa-youtube text-xl"></i>
                        </a>
                    </div>
                </div>

                <!-- Services -->
                <div>
                    <h4 class="font-semibold mb-4 flex items-center">
                        <i class="fas fa-concierge-bell mr-2 text-green-400"></i>
                        บริการของเรา
                    </h4>
                    <ul class="space-y-2 text-gray-400">
                        <li>
                            <a href="#" class="hover:text-white transition duration-200 flex items-center">
                                <i class="fas fa-code mr-2 text-xs"></i>พัฒนาเว็บไซต์
                            </a>
                        </li>
                        <li>
                            <a href="#" class="hover:text-white transition duration-200 flex items-center">
                                <i class="fas fa-shopping-cart mr-2 text-xs"></i>ระบบจัดการสินค้า
                            </a>
                        </li>
                        <li>
                            <a href="#" class="hover:text-white transition duration-200 flex items-center">
                                <i class="fas fa-headset mr-2 text-xs"></i>ให้คำปรึกษาด้านไอที
                            </a>
                        </li>
                        <li>
                            <a href="#" class="hover:text-white transition duration-200 flex items-center">
                                <i class="fas fa-graduation-cap mr-2 text-xs"></i>ฝึกอบรม
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="font-semibold mb-4 flex items-center">
                        <i class="fas fa-address-book mr-2 text-yellow-400"></i>
                        ติดต่อเรา
                    </h4>
                    <ul class="space-y-3 text-gray-400 text-sm">
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-map-marker-alt text-yellow-400"></i>
                            <span>กรุงเทพมหานคร, ประเทศไทย</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-phone text-yellow-400"></i>
                            <span>02-123-4567</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-envelope text-yellow-400"></i>
                            <span>contact@ittech.com</span>
                        </li>
                        <li class="flex items-center space-x-3">
                            <i class="fas fa-clock text-yellow-400"></i>
                            <span>จันทร์-ศุกร์ 9:00-18:00 น.</span>
                        </li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div>
                    <h4 class="font-semibold mb-4 flex items-center">
                        <i class="fas fa-newspaper mr-2 text-purple-400"></i>
                        รับข่าวสาร
                    </h4>
                    <p class="text-gray-400 mb-4 text-sm">สมัครรับข่าวสารและโปรโมชั่นล่าสุด</p>
                    <form class="flex flex-col space-y-2">
                        <input type="email" placeholder="อีเมลของคุณ" 
                               class="px-3 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm placeholder-gray-400">
                        <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded-lg transition duration-200 flex items-center justify-center">
                            <i class="fas fa-paper-plane mr-2"></i>
                            <span>สมัครรับข่าวสาร</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Copyright -->
            <div class="border-t border-gray-700 mt-8 pt-6 text-center text-gray-400">
                <p class="flex items-center justify-center">
                    <i class="fas fa-copyright mr-2 text-sm"></i>
                    <?php echo date('Y'); ?> ครบเครื่องเรื่องไอที. สงวนลิขสิทธิ์ทุกประการ.
                </p>
                <p class="text-xs mt-2">พัฒนาโดยทีมงานครบเครื่องเรื่องไอที</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // แสดงข้อความแจ้งเตือนแบบ Toast
        <?php if (isset($_SESSION['message'])): ?>
            setTimeout(() => {
                const message = '<?php echo $_SESSION['message']; ?>';
                const type = '<?php echo $_SESSION['message_type'] ?? 'success'; ?>';
                showToast(message, type);
                <?php 
                unset($_SESSION['message']); 
                unset($_SESSION['message_type']);
                ?>
            }, 100);
        <?php endif; ?>

        // Toast function
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg text-white z-50 transform transition-transform duration-300 ${
                type === 'error' ? 'bg-red-500' : 'bg-green-500'
            }`;
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'error' ? 'fa-exclamation-triangle' : 'fa-check-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        // Confirm before delete
        function confirmDelete(message = 'คุณแน่ใจที่จะลบรายการนี้?') {
            return confirm(message);
        }

        // Mobile menu toggle
        function toggleMobileMenu() {
            const sidebar = document.querySelector('aside');
            sidebar.classList.toggle('hidden');
        }
    </script>
</body>
</html>