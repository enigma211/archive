<?php
require_once 'config.php';
require_once 'includes/Auth.php';
require_once 'includes/AdminLayout.php';
require_once 'includes/JalaliDate.php';
require_once 'includes/DeadlineHelper.php';
require_once 'functions.php';

$auth = new Auth();
$auth->requireLogin();

$conn = $pdo;

// Handle messages from URL parameters
$error_message = '';
$success_message = '';

if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}

// Get dashboard statistics
$stats = [];

// Total individuals
$query = "SELECT COUNT(*) as count FROM individuals";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['individuals'] = $stmt->fetch()['count'];

// Total cases
$query = "SELECT COUNT(*) as count FROM cases";
$stmt = $conn->prepare($query);
$stmt->execute();
$stats['cases'] = $stmt->fetch()['count'];

// Removed: open_cases, in_progress_cases, closed_cases statistics

// Pagination settings
$items_per_page = 50;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get search functionality
$search = $_GET['search'] ?? '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM cases c 
                JOIN individuals i ON c.individual_id = i.id";
$count_params = [];

if (!empty($search)) {
    $count_query .= " WHERE i.national_id LIKE :search OR i.last_name LIKE :search";
    $count_params[':search'] = '%' . $search . '%';
}

$count_stmt = $conn->prepare($count_query);
foreach ($count_params as $key => $value) {
    $count_stmt->bindValue($key, $value);
}
$count_stmt->execute();
$total_items = $count_stmt->fetch()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Recent cases (with search and pagination functionality)
$query = "SELECT c.id, c.case_title, c.status, c.created_at, c.deadline_date, c.deadline_days, 
          c.case_type, c.case_stage, i.first_name, i.last_name, i.national_id 
          FROM cases c 
          JOIN individuals i ON c.individual_id = i.id";
$params = [];

if (!empty($search)) {
    $query .= " WHERE i.national_id LIKE :search OR i.last_name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$query .= " ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$recent_cases = $stmt->fetchAll();

AdminLayout::renderHeader('داشبورد');
?>

<?php if ($error_message): ?>
    <?php AdminLayout::renderErrorAlert($error_message); ?>
<?php endif; ?>

<?php if ($success_message): ?>
    <?php AdminLayout::renderSuccessAlert($success_message); ?>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h4 class="card-title"><?php echo $stats['individuals']; ?></h4>
                <p class="card-text text-muted">کل افراد</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-folder-open fa-3x text-info mb-3"></i>
                <h4 class="card-title"><?php echo $stats['cases']; ?></h4>
                <p class="card-text text-muted">کل پرونده‌ها</p>
            </div>
        </div>
    </div>
</div>

<!-- Search Section -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-search me-2"></i>
                    جستجوی سریع پرونده‌ها
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <label for="search" class="form-label">جستجو بر اساس کد ملی یا نام خانوادگی</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="کد ملی یا نام خانوادگی را وارد کنید">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>جستجو
                            </button>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>پاک کردن
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    <?php if (!empty($search)): ?>
                        نتایج جستجو برای "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                        لیست پرونده‌ها
                    <?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_cases)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <?php if (!empty($search)): ?>
                            <p>هیچ پرونده‌ای با این مشخصات یافت نشد</p>
                            <p class="small">لطفاً کد ملی یا نام خانوادگی را بررسی کنید</p>
                        <?php else: ?>
                            <p>هیچ پرونده‌ای ثبت نشده است</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>عنوان پرونده</th>
                                    <th>شاکی</th>
                                    <th>کد ملی</th>
                                    <th>نوع و مرحله</th>
                                    <th>مهلت پاسخ</th>
                                    <th>تاریخ ایجاد</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_cases as $case): ?>
                                    <tr>
                                        <td>
                                            <a href="view_case.php?case_id=<?php echo $case['id']; ?>" 
                                               class="text-decoration-none fw-bold">
                                                <?php echo htmlspecialchars($case['case_title']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($case['first_name'] . ' ' . $case['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($case['national_id']); ?></td>
                                        <td>
                                            <?php if (!empty($case['case_type'])): ?>
                                                <span class="badge bg-primary me-1"><?php echo htmlspecialchars($case['case_type']); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($case['case_stage'])): ?>
                                                <span class="badge bg-success"><?php echo htmlspecialchars($case['case_stage']); ?></span>
                                            <?php endif; ?>
                                            <?php if (empty($case['case_type']) && empty($case['case_stage'])): ?>
                                                <span class="text-muted">تعیین نشده</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($case['deadline_date'])): ?>
                                                <?php 
                                                $remaining_days = DeadlineHelper::getRemainingDays($case['deadline_date']);
                                                $deadline_class = DeadlineHelper::getDeadlineStatusClass($case['deadline_date'], $case['status']);
                                                $deadline_text = DeadlineHelper::getDeadlineStatusText($case['deadline_date'], $case['status']);
                                                ?>
                                                <span class="badge <?php echo $deadline_class; ?> me-1">
                                                    <?php echo $deadline_text; ?>
                                                </span>
                                                <?php if ($case['status'] !== 'closed' && $remaining_days > 0): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo $remaining_days . ' روز باقی'; ?>
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">بدون مهلت</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo JalaliDate::formatJalaliDate($case['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination - Always show -->
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <div class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            نمایش <?php echo $offset + 1; ?> تا <?php echo min($offset + $items_per_page, $total_items); ?> از <?php echo $total_items; ?> پرونده
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
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
    
</div>

<?php AdminLayout::renderFooter(); ?>
