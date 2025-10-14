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

AdminLayout::renderHeader('افزودن فرد جدید');
?>

<?php if ($error_message): ?>
    <?php AdminLayout::renderErrorAlert($error_message); ?>
<?php endif; ?>

<?php if ($success_message): ?>
    <?php AdminLayout::renderSuccessAlert($success_message); ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    افزودن فرد جدید
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="add_individual.php">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">نام *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">نام خانوادگی *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="national_id" class="form-label">کد ملی *</label>
                                <input type="text" class="form-control" id="national_id" name="national_id" 
                                       pattern="[0-9]{10}" maxlength="10" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="mobile_number" class="form-label">شماره موبایل</label>
                                <input type="text" class="form-control" id="mobile_number" name="mobile_number" 
                                       pattern="09[0-9]{9}" maxlength="11">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="father_name" class="form-label">نام پدر</label>
                                <input type="text" class="form-control" id="father_name" name="father_name">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="university_major" class="form-label">رشته دانشگاهی</label>
                                <input type="text" class="form-control" id="university_major" name="university_major">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="exam_name" class="form-label">نام آزمون</label>
                                <input type="text" class="form-control" id="exam_name" name="exam_name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            ذخیره فرد جدید
                        </button>
                        <a href="list_individuals.php" class="btn btn-secondary">
                            <i class="fas fa-list me-2"></i>
                            مشاهده لیست افراد
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php AdminLayout::renderFooter(); ?>
