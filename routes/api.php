<?php

// CRM
use App\Http\Controllers\crm\customers\CustomersController;
use App\Http\Controllers\crm\tenders\TenderController;
use App\Http\Controllers\crm\followUps\FollowUpsController;
use App\Http\Controllers\crm\approvals\ApprovalController;

// KPI
use App\Http\Controllers\kpi\employees\EmployeesController;
use App\Http\Controllers\kpi\job_desk_master\JobDeskMasterController;
use App\Http\Controllers\kpi\job_desk_entry\JobDeskEntryController;


use Illuminate\Support\Facades\Route;

// CRM API Routes
Route::get('public/v1/followUps/dash_admin_crm_preview_penawaran/{id}', [CustomersController::class, 'preview'])->name('customers.preview');
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/customers/{id?}', [CustomersController::class, 'show']);
});

Route::get('public/v1/tenders/dash_admin_crm_preview_penawaran/{id}', [TenderController::class, 'preview'])->name('tender.preview');
Route::get('public/v1/tenders/negotiation-form/{id}', [TenderController::class, 'getWithDetailsById'])->name('tender.getWithDetailsById');
Route::post('public/v1/tenders/store/', [TenderController::class, 'store'])->name('tender.store');
Route::post('public/v1/tenders/update-deal/', [TenderController::class, 'updateNegotiationDeal'])->name('tender.updateNegotiationDeal');
Route::post('public/v1/tenders/store-documents/{id}/upload', [TenderController::class, 'storeDocuments'])->name('tender.storeDocuments');
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/tenders/{id?}', [TenderController::class, 'show']);
    Route::put('/v1/tenders/details/{id}', [TenderController::class, 'updateDetail']);
    Route::post('/v1/tenders/tender-header/{id}/status', [TenderController::class,'updateStatusHeader']);
    Route::post('/v1/tenders/tender-detail/{id}/status', [TenderController::class,'updateStatus']);
    Route::get('/v1/tenders/tender-documents-customer/{id}/documents', [TenderController::class, 'getDocuments']);
});

Route::get('public/v1/followUps/dash_admin_crm_preview_penawaran/{id}', [FollowUpsController::class, 'preview'])->name('followUps.preview');
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/followUps/{id?}', [FollowUpsController::class, 'show']);
    Route::post('/v1/followUps/logs', [FollowUpsController::class, 'insert']);
});

Route::get('public/v1/approvals/dash_admin_crm_preview_penawaran/{id}', [ApprovalController::class, 'preview'])->name('approvals.preview');
Route::get('public/v1/approvals/approvals-form/{id}', [ApprovalController::class, 'showApprovalForm'])->name('approvals.showApprovalForm');
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/approvals/{id?}', [ApprovalController::class, 'show']);    
    Route::post('/v1/approvals/store', [ApprovalController::class, 'store']);
});

// KPI API Routes
Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/employees/{id?}', [EmployeesController::class, 'show']);
});

Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/job-desk-master/{id?}', [JobDeskMasterController::class, 'show']);
});

Route::prefix('public')->middleware('hmac.auth')->group(function () {
    Route::get('/v1/job-desk-entry/{id?}', [JobDeskEntryController::class, 'show']);
});
