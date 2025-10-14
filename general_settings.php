<?php
/**
 * General Settings Page (Admin Only)
 * Allows admins to configure system-wide settings
 */

require_once 'header.php';

// Check if user is admin
requireAdmin();

// Get current user data
$current_user_id = getCurrentUserId();
$current_username = getCurrentUsername();

// Connect to database
$conn = getConnection();

// Handle messages from URL parameters
$error_message = '';
$success_message = '';

if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}

// Get current settings
$settings = [];
if ($conn) {
    try {
        $query = "SELECT setting_key, setting_value FROM settings";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        $error_message = "خطا در بارگذاری تنظیمات: " . $e->getMessage();
    }
}

// Set default values if not found
$system_name = $settings['system_name'] ?? 'سیستم مدیریت شکایات';
$footer_text = $settings['footer_text'] ?? 'تمامی حقوق محفوظ است';
$ssl_enabled = $settings['ssl_enabled'] ?? '0';
$font_size = $settings['font_size'] ?? '14';
$allowed_extensions = $settings['allowed_extensions'] ?? 'pdf,doc,docx,jpg,jpeg,png,gif,txt';
$max_file_size = $settings['max_file_size'] ?? '10';
$deadline_warning_days = $settings['deadline_warning_days'] ?? '5';
$deadline_urgent_days = $settings['deadline_urgent_days'] ?? '2';

require_once 'includes/AdminLayout.php';
AdminLayout::renderHeader('تنظیمات عمومی');
?>

<?php if ($error_message): ?>
    <?php AdminLayout::renderErrorAlert($error_message); ?>
<?php endif; ?>

<?php if ($success_message): ?>
    <?php AdminLayout::renderSuccessAlert($success_message); ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    تنظیمات عمومی سیستم
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="update_settings.php">
                    <div class="mb-4">
                        <label for="system_name" class="form-label">نام سیستم</label>
                        <input type="text" class="form-control" id="system_name" name="system_name" 
                               value="<?php echo htmlspecialchars($system_name); ?>" required>
                        <div class="form-text">نام سیستم که در هدر صفحات نمایش داده می‌شود</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="footer_text" class="form-label">متن فوتر</label>
                        <textarea class="form-control" id="footer_text" name="footer_text" rows="3" required><?php echo htmlspecialchars($footer_text); ?></textarea>
                        <div class="form-text">متن فوتر که در پایین صفحات نمایش داده می‌شود</div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ssl_enabled" name="ssl_enabled" 
                                   value="1" <?php echo $ssl_enabled == '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="ssl_enabled">
                                <strong>فعال‌سازی SSL</strong>
                            </label>
                        </div>
                        <div class="form-text">
                            با فعال‌سازی این گزینه، تمام آدرس‌های سیستم به HTTPS تبدیل می‌شوند. 
                            <span class="text-warning">توجه: این گزینه فقط در صورت داشتن گواهی SSL معتبر فعال کنید.</span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="font_size" class="form-label">اندازه فونت سیستم</label>
                        <select class="form-select" id="font_size" name="font_size" onchange="updateFontSize(this.value)">
                            <option value="12" <?php echo $font_size == '12' ? 'selected' : ''; ?>>خیلی کوچک (12px)</option>
                            <option value="13" <?php echo $font_size == '13' ? 'selected' : ''; ?>>کوچک (13px)</option>
                            <option value="14" <?php echo $font_size == '14' ? 'selected' : ''; ?>>متوسط (14px)</option>
                            <option value="15" <?php echo $font_size == '15' ? 'selected' : ''; ?>>بزرگ (15px)</option>
                            <option value="16" <?php echo $font_size == '16' ? 'selected' : ''; ?>>خیلی بزرگ (16px)</option>
                        </select>
                        <div class="form-text">
                            اندازه فونت کلی سیستم را انتخاب کنید. این تنظیم بر تمام صفحات تأثیر می‌گذارد.
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    <h6 class="mb-3">
                        <i class="fas fa-clock me-2"></i>
                        تنظیمات مهلت پاسخ پرونده‌ها
                    </h6>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>
                            این تنظیمات مشخص می‌کند که چه زمانی وضعیت مهلت پرونده‌ها تغییر کند.
                        </small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="deadline_warning_days" class="form-label">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            روزهای هشدار
                        </label>
                        <input type="number" class="form-control" id="deadline_warning_days" name="deadline_warning_days" 
                               value="<?php echo htmlspecialchars($deadline_warning_days); ?>" min="1" max="30" required>
                        <div class="form-text">
                            وقتی مهلت پرونده به این تعداد روز یا کمتر برسد، Badge زرد (هشدار) نمایش داده می‌شود.
                            <br>
                            <strong>مثال:</strong> اگر 5 روز تنظیم کنید، پرونده‌هایی که 5 روز یا کمتر مهلت دارند، وضعیت "هشدار" خواهند داشت.
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="deadline_urgent_days" class="form-label">
                            <i class="fas fa-exclamation-circle text-danger me-2"></i>
                            روزهای فوری
                        </label>
                        <input type="number" class="form-control" id="deadline_urgent_days" name="deadline_urgent_days" 
                               value="<?php echo htmlspecialchars($deadline_urgent_days); ?>" min="1" max="10" required>
                        <div class="form-text">
                            وقتی مهلت پرونده به این تعداد روز یا کمتر برسد، Badge قرمز (فوری) نمایش داده می‌شود.
                            <br>
                            <strong>مثال:</strong> اگر 2 روز تنظیم کنید، پرونده‌هایی که 2 روز یا کمتر مهلت دارند، وضعیت "فوری" خواهند داشت.
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-lightbulb me-2"></i>
                        <small>
                            <strong>توجه:</strong> روزهای فوری باید کمتر از روزهای هشدار باشد.
                            <br>
                            <strong>پیشنهاد:</strong> هشدار = 5 روز، فوری = 2 روز
                        </small>
                    </div>
                    
                    <hr class="my-4">
                    <h6 class="mb-3">
                        <i class="fas fa-paperclip me-2"></i>
                        تنظیمات فایل‌های پیوست
                    </h6>
                    
                    <div class="mb-4">
                        <label for="allowed_extensions" class="form-label">پسوندهای مجاز فایل‌ها</label>
                        <input type="text" class="form-control" id="allowed_extensions" name="allowed_extensions" 
                               value="<?php echo htmlspecialchars($allowed_extensions); ?>" required>
                        <div class="form-text">
                            پسوندهای مجاز را با کاما جدا کنید (مثال: pdf,doc,docx,jpg,jpeg,png,gif,txt)
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="max_file_size" class="form-label">حداکثر حجم فایل (مگابایت)</label>
                        <select class="form-select" id="max_file_size" name="max_file_size">
                            <option value="1" <?php echo $max_file_size == '1' ? 'selected' : ''; ?>>1 مگابایت</option>
                            <option value="2" <?php echo $max_file_size == '2' ? 'selected' : ''; ?>>2 مگابایت</option>
                            <option value="5" <?php echo $max_file_size == '5' ? 'selected' : ''; ?>>5 مگابایت</option>
                            <option value="10" <?php echo $max_file_size == '10' ? 'selected' : ''; ?>>10 مگابایت</option>
                            <option value="20" <?php echo $max_file_size == '20' ? 'selected' : ''; ?>>20 مگابایت</option>
                            <option value="50" <?php echo $max_file_size == '50' ? 'selected' : ''; ?>>50 مگابایت</option>
                            <option value="100" <?php echo $max_file_size == '100' ? 'selected' : ''; ?>>100 مگابایت</option>
                        </select>
                        <div class="form-text">
                            حداکثر حجم مجاز برای هر فایل پیوست
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            ذخیره تنظیمات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    راهنما
                </h5>
            </div>
            <div class="card-body">
                <h6>تنظیمات موجود:</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-tag text-primary me-2"></i>
                        <strong>نام سیستم:</strong> تغییر نام نمایشی سیستم
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-align-center text-info me-2"></i>
                        <strong>متن فوتر:</strong> تغییر متن پایین صفحات
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-shield-alt text-success me-2"></i>
                        <strong>SSL:</strong> فعال‌سازی اتصال امن
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-font text-info me-2"></i>
                        <strong>اندازه فونت:</strong> تنظیم اندازه متن سیستم
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        <strong>روزهای هشدار:</strong> تعیین زمان نمایش Badge زرد
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-exclamation-circle text-danger me-2"></i>
                        <strong>روزهای فوری:</strong> تعیین زمان نمایش Badge قرمز
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-paperclip text-warning me-2"></i>
                        <strong>پسوندهای مجاز:</strong> تعیین نوع فایل‌های قابل آپلود
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-weight text-secondary me-2"></i>
                        <strong>حداکثر حجم:</strong> تعیین سقف حجم فایل‌ها
                    </li>
                </ul>
                
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <small>
                        تغییرات تنظیمات بلافاصله اعمال می‌شوند. 
                        در صورت فعال‌سازی SSL، مطمئن شوید که گواهی SSL معتبر دارید.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateFontSize(size) {
    // Save to localStorage
    localStorage.setItem('systemFontSize', size);
    
    // Apply immediately
    document.documentElement.style.setProperty('--system-font-size', size + 'px');
    
    // Show preview message
    const preview = document.createElement('div');
    preview.className = 'alert alert-info position-fixed';
    preview.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    preview.innerHTML = '<i class="fas fa-font me-2"></i>اندازه فونت به ' + size + 'px تغییر کرد';
    document.body.appendChild(preview);
    
    // Remove preview after 3 seconds
    setTimeout(() => {
        preview.remove();
    }, 3000);
}

// Apply current font size on page load
document.addEventListener('DOMContentLoaded', function() {
    const currentSize = '<?php echo $font_size; ?>';
    localStorage.setItem('systemFontSize', currentSize);
    document.documentElement.style.setProperty('--system-font-size', currentSize + 'px');
});
</script>

<?php AdminLayout::renderFooter(); ?>
