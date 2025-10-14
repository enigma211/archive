<?php
require_once 'includes/Auth.php';
require_once 'includes/AdminLayout.php';
require_once 'includes/JalaliDate.php';
require_once 'config.php';
require_once 'functions.php';

$auth = new Auth();
$auth->requireLogin();

$database = new Database();
$conn = $database->getConnection();

$message = '';
$message_type = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $national_id = trim($_POST['national_id'] ?? '');
        $mobile_number = trim($_POST['mobile_number'] ?? '');
        $father_name = trim($_POST['father_name'] ?? '');
        $university_major = trim($_POST['university_major'] ?? '');
        $exam_name = trim($_POST['exam_name'] ?? '');
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($national_id) || empty($mobile_number)) {
            $message = 'لطفاً تمام فیلدهای اجباری را پر کنید';
            $message_type = 'error';
        } else {
            try {
                if ($action === 'add') {
                    $query = "INSERT INTO individuals (first_name, last_name, national_id, mobile_number, father_name, university_major, exam_name) 
                              VALUES (:first_name, :last_name, :national_id, :mobile_number, :father_name, :university_major, :exam_name)";
                } else {
                    $id = $_POST['id'];
                    $query = "UPDATE individuals SET first_name=:first_name, last_name=:last_name, national_id=:national_id, 
                              mobile_number=:mobile_number, father_name=:father_name, university_major=:university_major, 
                              exam_name=:exam_name WHERE id=:id";
                }
                
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':national_id', $national_id);
                $stmt->bindParam(':mobile_number', $mobile_number);
                $stmt->bindParam(':father_name', $father_name);
                $stmt->bindParam(':university_major', $university_major);
                $stmt->bindParam(':exam_name', $exam_name);
                
                if ($action === 'edit') {
                    $stmt->bindParam(':id', $id);
                }
                
                if ($stmt->execute()) {
                    $message = $action === 'add' ? 'فرد جدید با موفقیت اضافه شد' : 'اطلاعات فرد با موفقیت به‌روزرسانی شد';
                    $message_type = 'success';
                } else {
                    $message = 'خطا در ذخیره اطلاعات';
                    $message_type = 'error';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = 'کد ملی تکراری است';
                } else {
                    $message = 'خطا در ذخیره اطلاعات: ' . $e->getMessage();
                }
                $message_type = 'error';
            }
        }
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $query = "DELETE FROM individuals WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $message = 'فرد با موفقیت حذف شد';
            $message_type = 'success';
        } else {
            $message = 'خطا در حذف فرد';
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = 'خطا در حذف فرد: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get individuals list
$query = "SELECT * FROM individuals ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$individuals = $stmt->fetchAll();

// Get individual for editing
$edit_individual = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM individuals WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $edit_individual = $stmt->fetch();
}

AdminLayout::renderHeader('مدیریت افراد');
?>

<?php if ($message): ?>
    <?php if ($message_type === 'success'): ?>
        <?php AdminLayout::renderSuccessAlert($message); ?>
    <?php else: ?>
        <?php AdminLayout::renderErrorAlert($message); ?>
    <?php endif; ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    <?php echo $edit_individual ? 'ویرایش فرد' : 'افزودن فرد جدید'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_individual ? 'edit' : 'add'; ?>">
                    <?php if ($edit_individual): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_individual['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="first_name" class="form-label">نام *</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($edit_individual['first_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="last_name" class="form-label">نام خانوادگی *</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($edit_individual['last_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="national_id" class="form-label">کد ملی *</label>
                        <input type="text" class="form-control" id="national_id" name="national_id" 
                               value="<?php echo htmlspecialchars($edit_individual['national_id'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mobile_number" class="form-label">شماره موبایل *</label>
                        <input type="text" class="form-control" id="mobile_number" name="mobile_number" 
                               value="<?php echo htmlspecialchars($edit_individual['mobile_number'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="father_name" class="form-label">نام پدر</label>
                        <input type="text" class="form-control" id="father_name" name="father_name" 
                               value="<?php echo htmlspecialchars($edit_individual['father_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="university_major" class="form-label">رشته دانشگاهی</label>
                        <input type="text" class="form-control" id="university_major" name="university_major" 
                               value="<?php echo htmlspecialchars($edit_individual['university_major'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="exam_name" class="form-label">نام آزمون</label>
                        <input type="text" class="form-control" id="exam_name" name="exam_name" 
                               value="<?php echo htmlspecialchars($edit_individual['exam_name'] ?? ''); ?>">
                    </div>
                    
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            <?php echo $edit_individual ? 'به‌روزرسانی' : 'ذخیره'; ?>
                        </button>
                        <?php if ($edit_individual): ?>
                            <a href="individuals.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>انصراف
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>لیست افراد</h5>
            </div>
            <div class="card-body">
                <?php if (empty($individuals)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <p>هیچ فردی ثبت نشده است</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>نام و نام خانوادگی</th>
                                    <th>کد ملی</th>
                                    <th>موبایل</th>
                                    <th>رشته دانشگاهی</th>
                                    <th>تاریخ ثبت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($individuals as $individual): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($individual['first_name'] . ' ' . $individual['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($individual['national_id']); ?></td>
                                        <td><?php echo htmlspecialchars($individual['mobile_number']); ?></td>
                                        <td><?php echo htmlspecialchars($individual['university_major'] ?: '-'); ?></td>
                                        <td><?php echo JalaliDate::formatJalaliDate($individual['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="individuals.php?action=edit&id=<?php echo $individual['id']; ?>" 
                                                   class="btn btn-outline-primary" title="ویرایش">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="individuals.php?action=delete&id=<?php echo $individual['id']; ?>" 
                                                   class="btn btn-outline-danger" title="حذف"
                                                   onclick="return confirm('آیا از حذف این فرد اطمینان دارید؟')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php AdminLayout::renderFooter(); ?>
