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

$message = '';
$message_type = '';

// Check for messages from URL
if (isset($_GET['success'])) {
    $message = htmlspecialchars($_GET['success']);
    $message_type = 'success';
} elseif (isset($_GET['error'])) {
    $message = htmlspecialchars($_GET['error']);
    $message_type = 'error';
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $query = "DELETE FROM cases WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $message = 'پرونده با موفقیت حذف شد';
            $message_type = 'success';
        } else {
            $message = 'خطا در حذف پرونده';
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = 'خطا در حذف پرونده: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Removed old inline edit form functionality

// Pagination settings
$items_per_page = 50;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get cases list with individual names (with search and sort functionality)
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'desc';

// Validate sort and order parameters
$allowed_sorts = ['created_at', 'deadline_date'];
$allowed_orders = ['asc', 'desc'];

if (!in_array($sort, $allowed_sorts)) {
    $sort = 'created_at';
}

if (!in_array($order, $allowed_orders)) {
    $order = 'desc';
}

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

// Get cases for current page
$query = "SELECT c.id, c.case_title, c.status, c.created_at, c.deadline_date, c.deadline_days, 
          c.case_type, c.case_stage, c.individual_id, i.first_name, i.last_name, i.national_id 
          FROM cases c 
          JOIN individuals i ON c.individual_id = i.id";
$params = [];

if (!empty($search)) {
    $query .= " WHERE i.national_id LIKE :search OR i.last_name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

// Add ORDER BY clause
if ($sort === 'deadline_date') {
    // For deadline sorting, exclude expired cases and put NULL values at the end
    if ($order === 'asc') {
        $query .= " ORDER BY 
                    CASE 
                        WHEN c.deadline_date IS NULL THEN 2
                        WHEN c.deadline_date < CURDATE() THEN 3
                        ELSE 1
                    END,
                    c.deadline_date ASC";
    } else {
        $query .= " ORDER BY 
                    CASE 
                        WHEN c.deadline_date IS NULL THEN 2
                        WHEN c.deadline_date < CURDATE() THEN 3
                        ELSE 1
                    END,
                    c.deadline_date DESC";
    }
} else {
    $query .= " ORDER BY c." . $sort . " " . strtoupper($order);
}

// Add LIMIT and OFFSET for pagination
$query .= " LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$cases = $stmt->fetchAll();

// Removed old edit case loading

AdminLayout::renderHeader('مدیریت پرونده‌ها');
?>

<?php if ($message): ?>
    <?php if ($message_type === 'success'): ?>
        <?php AdminLayout::renderSuccessAlert($message); ?>
    <?php else: ?>
        <?php AdminLayout::renderErrorAlert($message); ?>
    <?php endif; ?>
<?php endif; ?>

<!-- Search Form -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="search" class="form-label">جستجو بر اساس کد ملی یا نام خانوادگی</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="کد ملی یا نام خانوادگی را وارد کنید">
                    </div>
                    <div class="col-md-3">
                        <label for="sort" class="form-label">مرتب‌سازی بر اساس</label>
                        <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
                            <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>تاریخ ایجاد</option>
                            <option value="deadline_date" <?php echo $sort === 'deadline_date' ? 'selected' : ''; ?>>مهلت پاسخ</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="order" class="form-label">ترتیب</label>
                        <select class="form-select" id="order" name="order" onchange="this.form.submit()">
                            <option value="asc" <?php echo $order === 'asc' ? 'selected' : ''; ?>>صعودی ↑</option>
                            <option value="desc" <?php echo $order === 'desc' ? 'selected' : ''; ?>>نزولی ↓</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <a href="cases.php" class="btn btn-outline-secondary">
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
                    <i class="fas fa-list me-2"></i>لیست پرونده‌ها 
                    <span class="badge bg-secondary"><?php echo $total_items; ?> پرونده</span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($cases)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                        <p>هیچ پرونده‌ای ثبت نشده است</p>
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
                                    <th>
                                        <a href="?sort=deadline_date&order=<?php echo ($sort === 'deadline_date' && $order === 'asc') ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                           class="text-decoration-none text-dark" title="مرتب‌سازی بر اساس مهلت">
                                            مهلت پاسخ
                                            <?php if ($sort === 'deadline_date'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-sort ms-1 text-muted"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>
                                        <a href="?sort=created_at&order=<?php echo ($sort === 'created_at' && $order === 'asc') ? 'desc' : 'asc'; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                           class="text-decoration-none text-dark" title="مرتب‌سازی بر اساس تاریخ">
                                            تاریخ ایجاد
                                            <?php if ($sort === 'created_at'): ?>
                                                <i class="fas fa-sort-<?php echo $order === 'asc' ? 'up' : 'down'; ?> ms-1"></i>
                                            <?php else: ?>
                                                <i class="fas fa-sort ms-1 text-muted"></i>
                                            <?php endif; ?>
                                        </a>
                                    </th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cases as $case): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($case['case_title']); ?></td>
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
                                                <span class="badge <?php echo $deadline_class; ?>">
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
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="view_case.php?case_id=<?php echo $case['id']; ?>" 
                                                   class="btn btn-outline-info" title="مشاهده ورودی‌ها">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_case.php?id=<?php echo $case['id']; ?>" 
                                                   class="btn btn-outline-primary" title="ویرایش">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="cases.php?action=delete&id=<?php echo $case['id']; ?>" 
                                                   class="btn btn-outline-danger" title="حذف"
                                                   onclick="return confirm('آیا از حذف این پرونده اطمینان دارید؟')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
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
                                        <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo '&sort=' . $sort . '&order=' . $order; ?>">
                                            <i class="fas fa-chevron-right"></i> قبلی
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo '&sort=' . $sort . '&order=' . $order; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo '&sort=' . $sort . '&order=' . $order; ?>">
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
