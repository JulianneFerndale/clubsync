<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public pages
Route::get('/', fn () => view('start'))->name('start');
Route::get('/welcome', fn () => view('welcome'))->name('welcome');
Route::get('/terms', fn () => view('terms'))->name('terms');

// Guest-only auth routes
Route::middleware('guest.firebase')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate']);

    Route::get('/register', [RegisterController::class, 'showStep1'])->name('register');
    Route::post('/register', [RegisterController::class, 'storeStep1']);

    Route::get('/register/department', [RegisterController::class, 'showDepartment'])->name('register.department');
    Route::post('/register/department', [RegisterController::class, 'storeDepartment']);

    Route::get('/register/course', [RegisterController::class, 'showCourse'])->name('register.course');
    Route::post('/register/course', [RegisterController::class, 'storeCourse']);

    Route::get('/forgot-password',  [ForgotPasswordController::class, 'show'])->name('forgot-password');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('firebase.token');

// ─── Profile (all authenticated roles) ───────────────────────────────────────
Route::middleware('firebase.token')->group(function () {
    Route::get('/profile',          [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/photo',   [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');
});

// ─── DSA ────────────────────────────────────────────────────────────────────
Route::middleware(['firebase.token', 'role:dsa'])->prefix('dsa')->name('dsa.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DSA\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/clubs',             [\App\Http\Controllers\DSA\ClubController::class, 'index'])->name('clubs.index');
    Route::get('/clubs/create',      [\App\Http\Controllers\DSA\ClubController::class, 'create'])->name('clubs.create');
    Route::post('/clubs',            [\App\Http\Controllers\DSA\ClubController::class, 'store'])->name('clubs.store');
    Route::get('/clubs/{club}',      [\App\Http\Controllers\DSA\ClubController::class, 'show'])->name('clubs.show');
    Route::get('/clubs/{club}/edit', [\App\Http\Controllers\DSA\ClubController::class, 'edit'])->name('clubs.edit');
    Route::patch('/clubs/{club}',    [\App\Http\Controllers\DSA\ClubController::class, 'update'])->name('clubs.update');
});

// ─── Adviser ────────────────────────────────────────────────────────────────
Route::middleware(['firebase.token', 'role:adviser'])->prefix('adviser')->name('adviser.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Adviser\DashboardController::class, 'index'])->name('dashboard');

    // Announcement approval queue
    Route::get('/announcements',                                   [\App\Http\Controllers\Adviser\AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/{announcement}',                    [\App\Http\Controllers\Adviser\AnnouncementController::class, 'show'])->name('announcements.show');
    Route::post('/announcements/{announcement}/approve',           [\App\Http\Controllers\Adviser\AnnouncementController::class, 'approve'])->name('announcements.approve');
    Route::post('/announcements/{announcement}/request-revision',  [\App\Http\Controllers\Adviser\AnnouncementController::class, 'requestRevision'])->name('announcements.request-revision');
    Route::post('/announcements/{announcement}/reject',            [\App\Http\Controllers\Adviser\AnnouncementController::class, 'reject'])->name('announcements.reject');
});

// ─── Officer (president / treasurer / mmo) ───────────────────────────────────
Route::middleware(['firebase.token', 'role:officer'])->prefix('officer')->name('officer.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Officer\DashboardController::class, 'index'])->name('dashboard');

    // Events
    Route::get('/events',              [\App\Http\Controllers\Officer\EventController::class, 'index'])->name('events.index');
    Route::get('/events/create',       [\App\Http\Controllers\Officer\EventController::class, 'create'])->name('events.create');
    Route::post('/events',             [\App\Http\Controllers\Officer\EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}',      [\App\Http\Controllers\Officer\EventController::class, 'show'])->name('events.show');
    Route::post('/events/{event}/complete', [\App\Http\Controllers\Officer\EventController::class, 'complete'])->name('events.complete');

    // Attendance
    Route::get('/events/{event}/attendance',              [\App\Http\Controllers\Officer\AttendanceController::class, 'index'])->name('events.attendance');
    Route::post('/events/{event}/attendance/{user}',      [\App\Http\Controllers\Officer\AttendanceController::class, 'record'])->name('events.attendance.record');

    // Fees
    Route::get('/fees',                                   [\App\Http\Controllers\Officer\FeeController::class, 'index'])->name('fees.index');
    Route::get('/fees/create',                            [\App\Http\Controllers\Officer\FeeController::class, 'create'])->name('fees.create');
    Route::post('/fees',                                  [\App\Http\Controllers\Officer\FeeController::class, 'store'])->name('fees.store');
    Route::get('/fees/{fee}',                             [\App\Http\Controllers\Officer\FeeController::class, 'show'])->name('fees.show');
    Route::post('/fees/{fee}/members/{user}/paid',        [\App\Http\Controllers\Officer\FeeController::class, 'markPaid'])->name('fees.paid');
    Route::post('/fees/{fee}/members/{user}/unpaid',      [\App\Http\Controllers\Officer\FeeController::class, 'markUnpaid'])->name('fees.unpaid');
});

// ─── Member ──────────────────────────────────────────────────────────────────
Route::middleware(['firebase.token', 'role:member'])->prefix('member')->name('member.')->group(function () {
    Route::get('/dashboard',             [\App\Http\Controllers\Member\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/clubs',                 [\App\Http\Controllers\Member\ClubController::class, 'index'])->name('clubs.index');
    Route::get('/clubs/{club}',          [\App\Http\Controllers\Member\ClubController::class, 'show'])->name('clubs.show');
    Route::post('/clubs/{club}/join',    [\App\Http\Controllers\Member\ClubController::class, 'join'])->name('clubs.join');
    Route::post('/clubs/{club}/leave',   [\App\Http\Controllers\Member\ClubController::class, 'leave'])->name('clubs.leave');

    Route::get('/activity', [\App\Http\Controllers\Member\ActivityController::class, 'index'])->name('activity');

    // Bulletin board
    Route::get('/bulletin',                                  [\App\Http\Controllers\Member\BulletinController::class, 'index'])->name('bulletin.index');
    Route::get('/bulletin/{announcement}',                   [\App\Http\Controllers\Member\BulletinController::class, 'show'])->name('bulletin.show');
    Route::post('/bulletin/{announcement}/like',             [\App\Http\Controllers\Member\BulletinController::class, 'like'])->name('bulletin.like');
    Route::post('/bulletin/{announcement}/comment',          [\App\Http\Controllers\Member\BulletinController::class, 'comment'])->name('bulletin.comment');

    // Notifications
    Route::get('/notifications',                             [\App\Http\Controllers\Member\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read',        [\App\Http\Controllers\Member\NotificationController::class, 'markRead'])->name('notifications.read');
});

// ─── Officer announcements (added to officer group above, defined here for clarity) ─
// (These must be inside the officer middleware group — appended via separate registration)
Route::middleware(['firebase.token', 'role:officer'])->prefix('officer')->name('officer.')->group(function () {
    Route::get('/announcements',                    [\App\Http\Controllers\Officer\AnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/create',             [\App\Http\Controllers\Officer\AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements',                   [\App\Http\Controllers\Officer\AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{announcement}',     [\App\Http\Controllers\Officer\AnnouncementController::class, 'show'])->name('announcements.show');
    Route::get('/announcements/{announcement}/edit',[\App\Http\Controllers\Officer\AnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::patch('/announcements/{announcement}',   [\App\Http\Controllers\Officer\AnnouncementController::class, 'update'])->name('announcements.update');
    Route::post('/announcements/{announcement}/submit', [\App\Http\Controllers\Officer\AnnouncementController::class, 'submit'])->name('announcements.submit');
    Route::post('/announcements/ai-draft',          [\App\Http\Controllers\Officer\AnnouncementController::class, 'aiDraft'])->name('announcements.ai-draft');
});

// ─── Churn Risk Engine (DSA + officers, never members) ───────────────────────
Route::middleware(['firebase.token', 'role:dsa,president,treasurer,mmo'])->group(function () {
    Route::get('/churn-risk', fn () => abort(501))->name('churn-risk');
});
