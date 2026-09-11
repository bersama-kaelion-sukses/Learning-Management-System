<?php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LMS\AuthController;
use App\Http\Controllers\LMS\LoginSessionController;
use App\Http\Controllers\LMS\UserController;
use App\Http\Controllers\LMS\ProfileController;
use App\Http\Controllers\LMS\CourseController;
use App\Http\Controllers\LMS\CourseWeekModuleController;
use App\Http\Controllers\LMS\CourseExplorerController;
use App\Http\Controllers\LMS\ApprovalSystemController;
use App\Http\Controllers\LMS\CourseEnrollmentController;
use App\Http\Controllers\Dashboard\LearnerDashboardController;
use App\Http\Controllers\Dashboard\AdministratorDashboardController;
use App\Http\Controllers\Dashboard\InstructorDashboardController;
use App\Http\Controllers\Dashboard\LaporanHRController;
use App\Http\Controllers\LMS\LearnerSubmissionController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// First Login  + Login Auth
Route::get('/', function () {
    return redirect()->route('login');
});
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.process');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('password.change');
Route::post('/change-password', [AuthController::class, 'updatePassword'])->name('password.update');
Route::resource('login-sessions', LoginSessionController::class)
    ->middleware('auth');

// Dashboard-Related
Route::get('/dashboard', function () {
    $user = Auth::user();

    switch ($user->role_id) {
        case 1:
            return view('dashboard.instructor-dashboard');
        case 2:
            return app(AdministratorDashboardController::class)->index();
        case 3:
             return app(InstructorDashboardController::class)->index();
        case 4:
            return app(LearnerDashboardController::class)->index();
        default:
            abort(403, 'Role tidak dikenali.');
    }
})->name('dashboard')->middleware('auth');

// Navigation 


Route::get('/learner-task', function () {
    return view('learner.task');
})->name('learner.task');

Route::get('/submission-course/{course_id}/', [LearnerSubmissionController::class, 'index'])
    ->name('instructor.submission-course');

Route::get('/submission-data/{item_id}/{learner_id}', [LearnerSubmissionController::class, 'getSubmission']);

Route::post('/submission-feedback/{item_id}/{learner_id}', [LearnerSubmissionController::class, 'applyFeedback']);

Route::get('/track-course', function () {
    return view('instructor.track-course');
})->name('instructor.track-course');

Route::get('/report', function() {
    return app(LaporanHRController::class)->index();
})->name('administrator.report');

// Route::get('/slug-detail-course', function() ){
//     return view('learner.detail-course');
// }

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('general.profile'); // alias nama
    Route::put('/profile/{id}', [ProfileController::class, 'editUser'])->name('profile.editUser');

});

// User Management
Route::prefix('user-management')->middleware('auth')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('administrator.user-mgt'); 
    Route::post('/store', [UserController::class, 'store'])->name('user.store');
    Route::put('/{id}', [UserController::class, 'update'])->name('user.update');
    Route::put('/{id}/roles', [UserController::class, 'updateRoles'])->name('user.updateRoles');
    Route::put('/{id}/delete', [UserController::class, 'destroy'])->name('user.destroy');
    Route::put('/{id}/status', [UserController::class, 'changeStatus'])->name('user.changeStatus');
});

Route::prefix('instructor-course')
    ->middleware('auth')
    ->group(function () {
        Route::get('/', [CourseController::class, 'index'])
            ->name('instructor.course');
        Route::post('/store', [CourseController::class, 'store']) // atau 'store' kalau method-nya store()
            ->name('instructor.store');
        Route::post('/requested', [CourseController::class, 'requested'])
            ->name('instructor.requested');
        Route::post('/update/{id}', [CourseController::class, 'update'])
            ->name('instructor.update');
        Route::post('/delete/{id}', [CourseController::class, 'destroy'])
            ->name('instructor.delete');
        Route::post('/restore/{id}', [CourseController::class, 'restore'])
            ->name('instructor.restore');
});


// Route::prefix('modify-course')->middleware('auth')->group(function () {

// Route::middleware(['auth'])->group(function () {
//     Route::get('/modify-course/{id}', [ProfileController::class, 'index'])->name('instructor.modify-course'); // alias nama
// });


// Route:: middleware(['auh'])->group(function() {

// });

Route::middleware(['auth'])->group(function () {
    // (Opsional) melihat daftar sekat pada course tertentu
    Route::get('/courses/{course}/modules', [CourseWeekModuleController::class, 'index'])
        ->name('courses.modules.index');

    // Simpan sekat-sekat sebagai DRAFT (batch insert, belum approval)
    Route::post('/courses/{course}/modules/draft', [CourseWeekModuleController::class, 'storeDraft'])
        ->name('courses.modules.storeDraft');

    // Ajukan sekat-sekat utk pratinjau/approval
    Route::post('/courses/{course}/modules/submit', [CourseWeekModuleController::class, 'submitForPreview'])
        ->name('courses.modules.submit');
});

Route::middleware(['auth'])->group(function () {
    // List publik
    Route::get('/courses/explore', [CourseExplorerController::class, 'index'])
        ->name('learner.explore');

    // Detail kursus publik
    Route::get('/courses/{courseId}', [CourseExplorerController::class, 'show'])
        ->name('learner.detail-course');

    // Minta gabung kursus publik
    Route::post('/courses/{courseId}/join', [CourseExplorerController::class, 'requestJoinUser'])
        ->name('learner.request-join');

    Route::get('/learner-course', [CourseEnrollmentController::class, 'index'])
        ->name('learner.course');

    Route::get('/detail-course/course-enrolled/{id}', [CourseEnrollmentController::class, 'detailMyCourse'])
        ->name('learner.course-enrolled');

    Route::get('/course/{courseId}/locks', [CourseEnrollmentController::class, 'getCourseItemLocks'])
    ->middleware('auth');

    Route::post('/mirror-process-course/{id}', [CourseController::class, 'mirrorProcessCourse']);
   

});


Route::prefix('approval')->group(function () {
    Route::get('/', [ApprovalSystemController::class, 'index'])->name('approval.index');
    Route::post('/store', [ApprovalSystemController::class, 'store'])->name('approval.store');
    Route::get('/{id}', [ApprovalSystemController::class, 'detail'])->name('approval.detail');
    Route::post('/{id}/process', [ApprovalSystemController::class, 'process'])->name('approval.process');
    Route::post('/{id}/cancel', [ApprovalSystemController::class, 'cancel'])->name('approval.cancel');

    Route::get('/course/{id}/learners', [ApprovalSystemController::class, 'getCourseLearners'])
        ->name('approval.course.learners');
});

Route::get('/course-list', [CourseController::class, 'showListCourse'])
     ->name('administrator.course-list');

Route::post('/course-list/assign', [CourseController::class, 'assignUsertoCourse'])->name('administrator.course-list.assign');

Route::get('/detail-course/{id}', [CourseController::class, 'detailCourse'])
    ->name('instructor.detail-course');

Route::post('/course-list/rollback', [CourseController::class, 'rollbackAssignUsertoCourse'])
     ->name('administrator.course-list.rollback');
     
Route::post('/course-list/get-assign', [CourseController::class, 'getAssignUserinCourse'])
    ->name('administrator.course-list.getAssign');
    
// halaman utama modify course
Route::get('/modify-course/{id}', [CourseController::class, 'modifyCourse'])
    ->name('instructor.modify-course');
// simpan modul (draft)
Route::post('/modify-course/{id}/draft', [CourseController::class, 'courseModule'])
    ->name('courses.modules.courseModule');
    
// CREATE sub-item
Route::post('/modify-course/item/{id}', [CourseController::class, 'itemCourseModule'])
    ->name('courses.modules.itemCourseModule');

// UPDATE sub-item
Route::put('/modify-course/item/{id}', [CourseController::class, 'itemEditCourseModule'])
    ->name('modify-course.item.update');

Route::post('/modify-course/item/{id}/essay', [CourseController::class, 'EssayItemModule'])
    ->name('modify-course.item.essay');

Route::get('/course/{itemId}/essay-submissions', [CourseEnrollmentController::class, 'getEssaySubmissions'])
    ->name('essay.submission.get');

Route::post('/course/{itemId}/essay-submission', [CourseEnrollmentController::class, 'essaySubmission'])
    ->name('essay.submission.essaySubmission');

Route::post('/course/{itemId}/mc-submission', [CourseEnrollmentController::class, 'mcSubmission'])
    ->name('multipleChoice.submission.mcSubmission');

Route::post('/modify-course/item/{id}/choice', [CourseController::class, 'MultipleChoice'])
    ->name('modify-course.item.choice');

Route::get('/course/{itemId}/mc-submission/check', [CourseEnrollmentController::class, 'getMcSubmission'])
    ->name('multipleChoice.submission.get');

Route::post('/course/{forumId}/forum-reply', [CourseEnrollmentController::class, 'storeForumReply'])
    ->name('forum.reply.store');

Route::get('/forum/{forumId}/replies', [CourseEnrollmentController::class, 'getForumDiscussion']);

Route::post('/item/{itemId}/submission', [CourseEnrollmentController::class, 'uploadSubmission'])
    ->name('submissions.uploadSubmission');

Route::post('/course/save-progress', [CourseEnrollmentController::class, 'savedProgress'])
    ->middleware('auth')
    ->name('course.saveProgress');
    
Route::get('/course/{id}/progress', [CourseEnrollmentController::class, 'getCourseProgress'])
    ->middleware('auth');

Route::delete('/modify-course/item/question/{id}', [CourseController::class, 'DeleteMultiplyChoice'])
    ->name('modify-course.item.choice.delete');

Route::post('/modify-course/{id}/forum-discussion', [CourseController::class, 'forumDiscussion'])
    ->name('modify-course.forum.store');
    
// hapus modul (course_week)
Route::delete('/modify-course/module/{id}', [CourseController::class, 'deleteModule'])
    ->name('courses.modules.deleteModule');

// hapus sub-item (course_week_item)
Route::delete('/modify-course/item/{id}', [CourseController::class, 'deleteItem'])
    ->name('courses.modules.deleteItem');

Route::prefix('api')->group(function () {
    Route::get('/item/{itemId}/essay', [CourseController::class, 'getEssay']);
    Route::get('/item/{itemId}/forum', [CourseController::class, 'getForum']);
    Route::get('/item/{itemId}/quiz', [CourseController::class, 'getQuiz']);
});