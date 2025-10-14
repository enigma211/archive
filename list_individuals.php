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

// Handle messages from URL parameters
$error_message = '';
$success_message = '';

if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}

// Get search functionality
$search = $_GET['search'] ?? '';

// Pagination settings
$items_per_page = 50;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total count
$total_items = 0;
$total_pages = 0;
$individuals = [];

if ($conn) {
    try {
        // Get total count with search
        $count_query = "SELECT COUNT(*) as total FROM individuals";
        $count_params = [];
        
        if (!empty($search)) {
            $count_query .= " WHERE national_id LIKE :search OR last_name LIKE :search OR first_name LIKE :search";
            $count_params[':search'] = '%' . $search . '%';
        }
        
        $count_stmt = $conn->prepare($count_query);
        foreach ($count_params as $key => $value) {
            $count_stmt->bindValue($key, $value);
        }
        $count_stmt->execute();
        $total_items = $count_stmt->fetch()['total'];
        $total_pages = ceil($total_items / $items_per_page);
        
        // Get individuals for current page with search
        $query = "SELECT * FROM individuals";
        $params = [];
        
        if (!empty($search)) {
            $query .= " WHERE national_id LIKE :search OR last_name LIKE :search OR first_name LIKE :search";
            $params[':search'] = '%' . $search . '%';
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $items_per_page;
        $params[':offset'] = $offset;
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        $individuals = $stmt->fetchAll();
    } catch (Exception $e) {
        $error_message = "خطا در بارگذاری اطلاعات: " . $e->getMessage();
    }
}

AdminLayout::renderHeader('لیست افراد');
?>

<?php if ($error_message): ?>
    <?php AdminLayout::renderErrorAlert($error_message); ?>
<?php endif; ?>

<?php if ($success_message): ?>
    <?php AdminLayout::renderSuccessAlert($success_message); ?>
<?php endif; ?>

<!-- Search Section -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-search me-2"></i>
                    جستجوی افراد
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <label for="search" class="form-label">جستجو بر اساس کد ملی، نام یا نام خانوادگی</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="کد ملی، نام یا نام خانوادگی را وارد کنید">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>
                                جستجو
                            </button>
                        </div>
                    </div>
                </form>
                <?php if (!empty($search)): ?>
                    <div class="mt-3">
                        <a href="list_individuals.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-2"></i>
                            حذف فیلتر جستجو
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        <?php if (!empty($search)): ?>
                            نتایج جستجو برای "<?php echo htmlspecialchars($search); ?>" (<?php echo $total_items; ?> نفر)
                        <?php else: ?>
                            لیست افراد (<?php echo $total_items; ?> نفر)
                        <?php endif; ?>
                    </h5>
                    <a href="add_individual_page.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>
                        افزودن فرد جدید
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($individuals)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-users fa-4x mb-4"></i>
                        <?php if (!empty($search)): ?>
                            <h4>هیچ فردی با این مشخصات یافت نشد</h4>
                            <p>لطفاً عبارت جستجو را تغییر دهید یا فیلتر را حذف کنید</p>
                            <a href="list_individuals.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>
                                حذف فیلتر جستجو
                            </a>
                        <?php else: ?>
                            <h4>هیچ فردی ثبت نشده است</h4>
                            <p>برای شروع، فرد جدیدی اضافه کنید</p>
                            <a href="add_individual_page.php" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>
                                افزودن اولین فرد
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>نام و نام خانوادگی</th>
                                    <th>کد ملی</th>
                                    <th>نام آزمون</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($individuals as $individual): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-user-circle fa-2x text-primary me-3"></i>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($individual['first_name'] . ' ' . $individual['last_name']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($individual['national_id']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($individual['exam_name'] ?: 'بدون آزمون'); ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view_individual.php?id=<?php echo $individual['id']; ?>" 
                                                   class="btn btn-outline-primary" title="مشاهده جزئیات">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($auth->canEditIndividuals()): ?>
                                                    <a href="edit_individual.php?id=<?php echo $individual['id']; ?>" 
                                                       class="btn btn-outline-warning" title="ویرایش سریع">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
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
                
                <!-- Pagination - Always show -->
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        نمایش <?php echo $offset + 1; ?> تا <?php echo min($offset + $items_per_page, $total_items); ?> از <?php echo $total_items; ?> نفر
                        <span class="badge bg-light text-dark ms-2">صفحه <?php echo $current_page; ?> از <?php echo max(1, $total_pages); ?></span>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="صفحه‌بندی">
                        <ul class="pagination mb-0">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <i class="fas fa-chevron-right"></i> قبلی
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        بعدی <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php AdminLayout::renderFooter(); ?>
