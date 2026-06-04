<?php

// CRM
use App\Http\Controllers\crm\CrmDashboardController as CrmDashboardController;
use App\Http\Controllers\crm\customers\CustomersController as CrmCustomersController;
use App\Http\Controllers\crm\tenders\TenderController as CrmTenderController;
use App\Http\Controllers\crm\followUps\FollowUpsController as CrmFollowUpsController;
use App\Http\Controllers\crm\approvals\ApprovalController as CrmApprovalController;

// KPI
use App\Http\Controllers\kpi\KpiDashboardController as KpiDashboardController;
use App\Http\Controllers\kpi\employees\EmployeesController as KpiEmployeesController;
use App\Http\Controllers\kpi\job_desk_master\JobDeskMasterController as KpiJobDeskMasterController;
use App\Http\Controllers\kpi\job_desk_entry\JobDeskEntryController as KpiJobDeskEntryController;
use App\Http\Controllers\kpi\work_progress_update\WorkProgressUpdateController as KpiWorkProgressUpdateController;
use App\Http\Controllers\kpi\reporting\ReportKpiController as KpiReportingController;

// Operations
use App\Http\Controllers\operations\masters\drivers\DriversController as OperationsDriversController;
use App\Http\Controllers\operations\masters\units\UnitsController as OperationsUnitsController;
use App\Http\Controllers\operations\masters\investors\InvestorsController as OperationsInvestorsController;
use App\Http\Controllers\operations\masters\customers\CustomersController as OperationsCustomersController;
use App\Http\Controllers\operations\masters\garages\GaragesController as OperationsGaragesController;


// Accounting
use App\Http\Controllers\accounting\masters\account_category\AccountCategoryController as AccountingAccountCategoryController;
// use App\Http\Controllers\operations\masters\units\UnitsController as OperationsUnitsController;
// use App\Http\Controllers\operations\masters\investors\InvestorsController as OperationsInvestorsController;
// use App\Http\Controllers\operations\masters\customers\CustomersController as OperationsCustomersController;
// use App\Http\Controllers\operations\masters\garages\GaragesController as OperationsGaragesController;

// HRD Android
use App\Http\Controllers\hrd\android\login\AuthController as HrdAndroidAuthController;

use Illuminate\Support\Facades\Route;

// CRM API Routes
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/crm-dashboard/{id?}', [CrmDashboardController::class, 'show']);
});

Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/followUps/{id?}', [CrmFollowUpsController::class, 'show']);
    Route::post('/v1/followUps/logs', [CrmFollowUpsController::class, 'insert']);
});

Route::get('public/v1/followUps/dash_admin_crm_preview_penawaran/{id}', [CrmCustomersController::class, 'preview'])->name('customers.preview');
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/customers/{id?}', [CrmCustomersController::class, 'show']);
});

Route::get('public/v1/tenders/dash_admin_crm_preview_penawaran/{id}', [CrmTenderController::class, 'preview'])->name('tender.preview');
Route::get('public/v1/tenders/negotiation-form/{id}', [CrmTenderController::class, 'getWithDetailsById'])->name('tender.getWithDetailsById');
Route::post('public/v1/tenders/store/', [CrmTenderController::class, 'store'])->name('tender.store');
Route::post('public/v1/tenders/update-deal/', [CrmTenderController::class, 'updateNegotiationDeal'])->name('tender.updateNegotiationDeal');
Route::post('public/v1/tenders/store-documents/{id}/upload', [CrmTenderController::class, 'storeDocuments'])->name('tender.storeDocuments');
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/tenders/{id?}', [CrmTenderController::class, 'show']);
    Route::put('/v1/tenders/details/{id}', [CrmTenderController::class, 'updateDetail']);
    Route::post('/v1/tenders/tender-header/{id}/status', [CrmTenderController::class,'updateStatusHeader']);
    Route::post('/v1/tenders/tender-detail/{id}/status', [CrmTenderController::class,'updateStatus']);
    Route::get('/v1/tenders/tender-documents-customer/{id}/documents', [CrmTenderController::class, 'getDocuments']);
});

Route::get('public/v1/followUps/dash_admin_crm_preview_penawaran/{id}', [CrmFollowUpsController::class, 'preview'])->name('followUps.preview');
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/followUps/{id?}', [CrmFollowUpsController::class, 'show']);
    Route::post('/v1/followUps/logs', [CrmFollowUpsController::class, 'insert']);
});

Route::get('public/v1/approvals/dash_admin_crm_preview_penawaran/{id}', [CrmApprovalController::class, 'preview'])->name('approvals.preview');
Route::get('public/v1/approvals/approvals-form/{id}', [CrmApprovalController::class, 'showApprovalForm'])->name('approvals.showApprovalForm');
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/approvals/{id?}', [CrmApprovalController::class, 'show']);
    Route::post('/v1/approvals/store', [CrmApprovalController::class, 'store']);
});

// KPI API Routes
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/kpi-dashboard/{id?}', [KpiDashboardController::class, 'show']);
});

Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/employees/{id?}', [KpiEmployeesController::class, 'show']);
});

Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/job-desk-master/{id?}', [KpiJobDeskMasterController::class, 'show']);
    Route::post('/v1/job-desk-master/store', [KpiJobDeskMasterController::class, 'store']);
});

Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/job-desk-entry/{id?}', [KpiJobDeskEntryController::class, 'show']);
    Route::post('/v1/job-desk-entry/store', [KpiJobDeskEntryController::class, 'store']);
});

Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/kpi-work-progress-update/logs/{user_jobdesk_kpi_id}', [KpiWorkProgressUpdateController::class, 'getLogs']);
    Route::post('/v1/kpi-work-progress-update/store', [KpiWorkProgressUpdateController::class, 'store']);   
    Route::get('/v1/kpi-work-progress-update/{user_id}/{department_id}', [KpiWorkProgressUpdateController::class, 'show']);
});

Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/kpi-reporting/{user_id}/{department_id}', [KpiReportingController::class, 'show']);
});

// Operations API Routes
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/driver-operationals/{id?}', [OperationsDriversController::class, 'show']);
});

Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/units-operationals/{id?}', [OperationsUnitsController::class, 'show']);
});

Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/investors-operationals/{id?}', [OperationsInvestorsController::class, 'show']);
});

Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/customers-operationals/{id?}', [OperationsCustomersController::class, 'show']);
});

Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/garages-operationals/{id?}', [OperationsGaragesController::class, 'show']);
});

// Accounting API Routes
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/acc-account-categories/{id?}', [AccountCategoryController::class, 'show']);
});

// HRD Android API Routes
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::post('/v1/hrd-android/login', [HrdAndroidAuthController::class, 'login']);
});