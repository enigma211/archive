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

// Get statistics for reports
$stats = [];

// Total counts
$queries = [
    'total_individuals' => "SELECT COUNT(*) as count FROM individuals",
    'total_cases' => "SELECT COUNT(*) as count FROM cases",
    'total_entries' => "SELECT COUNT(*) as count FROM case_entries",
    'open_cases' => "SELECT COUNT(*) as count FROM cases WHERE status = 'open'",
    'in_progress_cases' => "SELECT COUNT(*) as count FROM cases WHERE status = 'in_progress'",
    'closed_cases' => "SELECT COUNT(*) as count FROM cases WHERE status = 'closed'"
];

foreach ($queries as $key => $query) {
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats[$key] = $stmt->fetch()['count'];
}

// Cases by type
$query = "SELECT case_type, COUNT(*) as count FROM cases WHERE case_type IS NOT NULL GROUP BY case_type";
$stmt = $conn->prepare($query);
$stmt->execute();
$cases_by_type = $stmt->fetchAll();

// Cases by stage
$query = "SELECT case_stage, COUNT(*) as count FROM cases WHERE case_stage IS NOT NULL GROUP BY case_stage";
$stmt = $conn->prepare($query);
$stmt->execute();
$cases_by_stage = $stmt->fetchAll();

// Recent activity
$query = "SELECT ce.*, c.case_title, i.first_name, i.last_name, u.username 
          FROM case_entries ce 
          JOIN cases c ON ce.case_id = c.id 
          JOIN individuals i ON c.individual_id = i.id 
          JOIN users u ON ce.user_id = u.id 
          ORDER BY ce.created_at DESC 
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$recent_activity = $stmt->fetchAll();

// Cases with most entries
$query = "SELECT c.id, c.case_title, i.first_name, i.last_name, COUNT(ce.id) as entry_count 
          FROM cases c 
          JOIN individuals i ON c.individual_id = i.id 
          LEFT JOIN case_entries ce ON c.id = ce.case_id 
          GROUP BY c.id 
          ORDER BY entry_count DESC 
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute();
$most_active_cases = $stmt->fetchAll();

AdminLayout::renderHeader('گزارشات');
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h4 class="card-title"><?php echo $stats['total_individuals']; ?></h4>
                <p class="card-text text-muted">کل افراد</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-folder-open fa-3x text-info mb-3"></i>
                <h4 class="card-title"><?php echo $stats['total_cases']; ?></h4>
                <p class="card-text text-muted">کل پرونده‌ها</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-clipboard-list fa-3x text-success mb-3"></i>
                <h4 class="card-title"><?php echo $stats['total_entries']; ?></h4>
                <p class="card-text text-muted">کل ورودی‌ها</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <i class="fas fa-chart-pie fa-3x text-warning mb-3"></i>
                <h4 class="card-title"><?php echo round(($stats['total_entries'] / max($stats['total_cases'], 1)) * 100, 1); ?>%</h4>
                <p class="card-text text-muted">میانگین ورودی</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>توزیع پرونده‌ها بر اساس نوع</h5>
            </div>
            <div class="card-body">
                <?php if (empty($cases_by_type)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-chart-pie fa-2x mb-2"></i>
                        <p>هیچ پرونده‌ای با نوع مشخص ثبت نشده است</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($cases_by_type as $type): ?>
                        <?php
                        $type_colors = [
                            'اظهارنامه' => 'bg-primary',
                            'دادخواست بدوی' => 'bg-info',
                            'اعاده دادرسی' => 'bg-warning'
                        ];
                        $type_class = $type_colors[$type['case_type']] ?? 'bg-secondary';
                        $percentage = $stats['total_cases'] > 0 ? ($type['count'] / $stats['total_cases']) * 100 : 0;
                        ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span><?php echo htmlspecialchars($type['case_type']); ?></span>
                                <span class="fw-bold"><?php echo $type['count']; ?> (<?php echo round($percentage, 1); ?>%)</span>
                            </div>
                            <div class="progress mt-1" style="height: 8px;">
                                <div class="progress-bar <?php echo $type_class; ?>" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-star me-2"></i>پرونده‌های با بیشترین فعالیت</h5>
            </div>
            <div class="card-body">
                <?php if (empty($most_active_cases)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>هیچ پرونده‌ای ثبت نشده است</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($most_active_cases as $case): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($case['case_title']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($case['first_name'] . ' ' . $case['last_name']); ?></small>
                            </div>
                            <div class="text-center">
                                <div class="h5 mb-0 text-primary"><?php echo $case['entry_count']; ?></div>
                                <small class="text-muted">ورودی</small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>توزیع پرونده‌ها بر اساس مرحله</h5>
            </div>
            <div class="card-body">
                <?php if (empty($cases_by_stage)): ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-tasks fa-2x mb-2"></i>
                        <p>هیچ پرونده‌ای با مرحله مشخص ثبت نشده است</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($cases_by_stage as $stage): ?>
                            <?php
                            $stage_colors = [
                                'جاری' => 'bg-info',
                                'پاسخ تهیه شد' => 'bg-success',
                                'لایحه تهیه شده' => 'bg-primary',
                                'دادنامه صادر شده' => 'bg-warning',
                                'مختومه' => 'bg-secondary'
                            ];
                            $stage_class = $stage_colors[$stage['case_stage']] ?? 'bg-secondary';
                            $percentage = $stats['total_cases'] > 0 ? ($stage['count'] / $stats['total_cases']) * 100 : 0;
                            ?>
                            <div class="col-md-4 mb-3">
                                <div class="card text-center">
                                    <div class="card-body">
                                        <div class="h2 mb-2"><?php echo $stage['count']; ?></div>
                                        <span class="badge <?php echo $stage_class; ?> mb-2"><?php echo htmlspecialchars($stage['case_stage']); ?></span>
                                        <div class="text-muted small"><?php echo round($percentage, 1); ?>% از کل پرونده‌ها</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>فعالیت‌های اخیر</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_activity)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-clipboard-list fa-3x mb-3"></i>
                        <p>هیچ فعالیتی ثبت نشده است</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>عنوان ورودی</th>
                                    <th>پرونده</th>
                                    <th>شاکی</th>
                                    <th>ثبت کننده</th>
                                    <th>تاریخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activity as $activity): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($activity['entry_title']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['case_title']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                        <td><?php echo JalaliDate::formatJalaliDate($activity['created_at']); ?></td>
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
