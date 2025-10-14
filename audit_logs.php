<?php
require_once 'includes/Auth.php';
require_once 'includes/AdminLayout.php';
require_once 'includes/JalaliDate.php';
require_once 'config.php';

$auth = new Auth();
$auth->requireAdmin();

$conn = $pdo;

// Filters
$username = $_GET['username'] ?? '';
$action = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

$where = [];
$params = [];
if ($username !== '') {
    $where[] = 'username LIKE :username';
    $params[':username'] = "%$username%";
}
if ($action !== '') {
    $where[] = 'action LIKE :action';
    $params[':action'] = "%$action%";
}
if ($date_from !== '') {
    $where[] = 'DATE(created_at) >= :from';
    $params[':from'] = $date_from;
}
if ($date_to !== '') {
    $where[] = 'DATE(created_at) <= :to';
    $params[':to'] = $date_to;
}

$where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count
$count_sql = "SELECT COUNT(*) as total FROM audit_logs $where_sql";
$stmt = $conn->prepare($count_sql);
foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
$stmt->execute();
$total = (int)$stmt->fetch()['total'];
$total_pages = max(1, (int)ceil($total / $per_page));

// Fetch
$sql = "SELECT * FROM audit_logs $where_sql ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

AdminLayout::renderHeader('گزارش رفتار کاربران');

// Localization maps
$actionMap = [
    'login_success'    => 'ورود موفق',
    'case_create'      => 'ایجاد پرونده',
    'case_update'      => 'ویرایش پرونده',
    'deadline_update'  => 'بروزرسانی مهلت',
    'case_entry_create'=> 'افزودن ورودی',
    'case_entry_delete'=> 'حذف ورودی',
    'attachment_add'   => 'افزودن پیوست',
    'attachment_delete'=> 'حذف پیوست',
];

$entityMap = [
    'user'       => 'کاربر',
    'case'       => 'پرونده',
    'case_entry' => 'ورودی پرونده',
    'attachment' => 'پیوست',
];

function faLabel($key) {
    $map = [
        'individual_id' => 'شناسه فرد',
        'case_id' => 'شناسه پرونده',
        'entry_id' => 'شناسه ورودی',
        'entry_title' => 'عنوان ورودی',
        'case_title' => 'عنوان پرونده',
        'case_type' => 'نوع پرونده',
        'case_stage' => 'مرحله پرونده',
        'deadline_days' => 'مهلت (روز)',
        'deadline_date' => 'تاریخ مهلت',
        'old_deadline_date' => 'مهلت قدیم',
        'new_deadline_date' => 'مهلت جدید',
        'deadline_jalali_submitted' => 'مهلت (جلالی) ارسالی',
        'original_filename' => 'نام فایل',
        'file_path' => 'مسیر فایل',
        'attachments_deleted' => 'تعداد پیوست‌های حذف‌شده',
        'username' => 'نام کاربری',
        'role' => 'نقش',
        'changes' => 'تغییرات',
    ];
    return $map[$key] ?? $key;
}

function renderDetailsFa($json) {
    if (empty($json)) return '<span class="text-muted">-</span>';
    $data = json_decode($json, true);
    if ($data === null) {
        return '<code style="white-space: pre-wrap; font-size: 12px;">' . htmlspecialchars($json) . '</code>';
    }
    $out = '<div style="max-width:360px">';
    foreach ($data as $k => $v) {
        if ($k === 'changes' && is_array($v)) {
            $out .= '<div><strong>' . faLabel('changes') . ':</strong></div>';
            $out .= '<ul class="mb-1">';
            foreach ($v as $field => $diff) {
                $old = isset($diff['old']) ? htmlspecialchars((string)$diff['old']) : '-';
                $new = isset($diff['new']) ? htmlspecialchars((string)$diff['new']) : '-';
                $out .= '<li><small>' . faLabel($field) . ': <span class="text-muted">قدیم:</span> ' . $old . ' <span class="text-muted">| جدید:</span> ' . $new . '</small></li>';
            }
            $out .= '</ul>';
            continue;
        }
        if (is_array($v)) {
            $vText = htmlspecialchars(json_encode($v, JSON_UNESCAPED_UNICODE));
        } else {
            $vText = htmlspecialchars((string)$v);
        }
        $out .= '<div><small><strong>' . faLabel($k) . ':</strong> ' . $vText . '</small></div>';
    }
    $out .= '</div>';
    return $out;
}
?>
<div class="card mb-3">
  <div class="card-header">
    <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>فیلترها</h5>
  </div>
  <div class="card-body">
    <form method="GET" class="row g-3">
      <div class="col-md-3">
        <label class="form-label">نام کاربری</label>
        <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($username); ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">اکشن</label>
        <input type="text" class="form-control" name="action" placeholder="مثال: case_create, deadline_update" value="<?php echo htmlspecialchars($action); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">از تاریخ (میلادی)</label>
        <input type="date" class="form-control" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">تا تاریخ (میلادی)</label>
        <input type="date" class="form-control" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100" type="submit"><i class="fas fa-search me-1"></i>جستجو</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0"><i class="fas fa-list me-2"></i>لاگ‌ها</h5>
    <small class="text-muted">نمایش <?php echo $offset + 1; ?> تا <?php echo min($offset + $per_page, $total); ?> از <?php echo $total; ?></small>
  </div>
  <div class="card-body table-responsive">
    <table class="table table-striped table-hover">
      <thead>
        <tr>
          <th>#</th>
          <th>تاریخ/زمان (شمسی)</th>
          <th>کاربر</th>
          <th>نقش</th>
          <th>اقدام</th>
          <th>نوع موجودیت</th>
          <th>شناسه</th>
          <th>جزئیات</th>
          <th>IP</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="9" class="text-center text-muted">لاگی یافت نشد</td></tr>
        <?php else: ?>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td><?php echo (int)$log['id']; ?></td>
              <?php 
                $time = '';
                try { $time = (new DateTime($log['created_at']))->format('H:i'); } catch (Exception $e) { $time = ''; }
                $jalali = JalaliDate::formatJalaliDate($log['created_at']);
              ?>
              <td><small><?php echo htmlspecialchars(trim($jalali . ' ' . $time)); ?></small></td>
              <td><?php echo htmlspecialchars($log['username'] ?? '-'); ?></td>
              <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['role'] ?? '-'); ?></span></td>
              <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($actionMap[$log['action']] ?? $log['action']); ?></span></td>
              <td><?php echo htmlspecialchars($entityMap[$log['entity_type']] ?? ($log['entity_type'] ?? '-')); ?></td>
              <td><?php echo htmlspecialchars($log['entity_id'] ?? '-'); ?></td>
              <td><?php echo renderDetailsFa($log['details']); ?></td>
              <td><small><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></small></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($total_pages > 1): ?>
  <div class="card-footer">
    <nav>
      <ul class="pagination mb-0">
        <?php if ($page > 1): ?>
        <li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>&username=<?php echo urlencode($username); ?>&action=<?php echo urlencode($action); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">قبلی</a></li>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
        <li class="page-item <?php echo $i==$page?'active':''; ?>">
          <a class="page-link" href="?page=<?php echo $i; ?>&username=<?php echo urlencode($username); ?>&action=<?php echo urlencode($action); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>"><?php echo $i; ?></a>
        </li>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
        <li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>&username=<?php echo urlencode($username); ?>&action=<?php echo urlencode($action); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">بعدی</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
  <?php endif; ?>
</div>

<?php AdminLayout::renderFooter(); ?>
