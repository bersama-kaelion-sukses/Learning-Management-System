<?php
use Carbon\Carbon;
use Illuminate\Http\Request;
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
use App\Http\Controllers\Dashboard\ITDashboardController;
use App\Http\Controllers\Dashboard\LaporanHRController;
use App\Http\Controllers\LMS\LearnerSubmissionController;
use App\Http\Controllers\LMS\TrackController;
use App\Http\Controllers\LMS\TakeOverController;
use App\Http\Controllers\LMS\LearnerSubmissionRealeaseController;
use App\Http\Controllers\LMS\ConsultationLearnerController;
use App\Models\UserNotification;
use App\Models\Course;

/*
--------------------------------------------------------------------------
 Web Routes
--------------------------------------------------------------------------
 All routes for LMS application are registered here.
--------------------------------------------------------------------------
*/
Route::get('/lang/{locale}', function ($locale) {
    if (!in_array($locale, ['en', 'id'])) abort(400);
    session(['locale' => $locale]);
    return back();
});
/*
--------------------------------------------------------------------------
 AUTHENTICATION & LOGIN
----------------------------------------------------------------------
*/

Route::get('/', fn() => redirect()->route('login'));
Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login'])->name('login.process');
Route::post('logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/change-password', [AuthController::class, 'showChangePassword'])->name('password.change');
Route::post('/change-password', [AuthController::class, 'updatePassword'])->name('password.update');
Route::post('/resetPassword', [AuthController::class, 'resetByUser'])->name('password.resetbyUser');
Route::resource('login-sessions', LoginSessionController::class)->middleware('auth');


// ====================
// ALL ROLES 
// ====================
Route::middleware(['auth', 'role:1,2,3,4'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD ROUTES (ALL Roles)
    |--------------------------------------------------------------------------
    */
     Route::get('/dashboard', function () {
        $user = Auth::user();
        if (!$user) abort(401, 'Silakan login dulu.');

        $roles = [(int)$user->role_id];
        $subRoles = is_array($user->sub_role)
            ? $user->sub_role
            : json_decode($user->sub_role, true);

        if (!empty($subRoles)) $roles = array_merge($roles, $subRoles);
        $roles = array_unique(array_filter($roles));

        $sections = [];

        if (in_array(1, $roles)) {
            $sections['it'] = app(\App\Http\Controllers\Dashboard\ITDashboardController::class)->index()->render();
        }
        if (in_array(2, $roles)) {
            $sections['hr'] = app(\App\Http\Controllers\Dashboard\AdministratorDashboardController::class)->index()->render();
        }
        if (in_array(3, $roles)) {
            $sections['instructor'] = app(\App\Http\Controllers\Dashboard\InstructorDashboardController::class)->index()->render();
        }
        if (in_array(4, $roles)) {
            $sections['learner'] = app(\App\Http\Controllers\Dashboard\LearnerDashboardController::class)->index()->render();
        }

        return view('dashboard.multi', compact('sections', 'roles', 'user'));
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'index'])->name('general.profile');
    Route::put('/profile', [ProfileController::class, 'editUser'])->name('profile.editUser');
    Route::post('/profile/reset-password/{emp_id}',[ProfileController::class, 'resetPassword']);

    /*
    |--------------------------------------------------------------------------
    | Streaming Video
    |--------------------------------------------------------------------------
    */
    Route::get('/stream/{filename}', [CourseEnrollmentController::class, 'stream']);
    /*
    |--------------------------------------------------------------------------
    | Additional
    |--------------------------------------------------------------------------
    */
    Route::get('/course/{courseId}/locks', [CourseEnrollmentController::class, 'getCourseItemLocks']);
    Route::post('/mirror-process-course/{id}', [CourseController::class, 'mirrorProcessCourse']);
    Route::get('/course/{itemId}/check-submission',[CourseEnrollmentController::class, 'checkSubmission'])->name('learner.check-submission');
    Route::get('/learner-task', fn() => view('learner.task'))->name('learner.task');
    /*
    |--------------------------------------------------------------------------
    | API ENDPOINTS
    |--------------------------------------------------------------------------
    */
    Route::prefix('api')->group(function () {
        Route::get('/item/{itemId}/essay', [CourseController::class, 'getEssay']);
        Route::get('/item/{itemId}/forum', [CourseController::class, 'getForum']);
        Route::get('/item/{itemId}/quiz', [CourseController::class, 'getQuiz']);
    });
    /*
    |--------------------------------------------------------------------------
    | COURSE ENROLLMENT (Submissions, Progress, Forum)
    |--------------------------------------------------------------------------
    */
    Route::get('/course/{itemId}/essay-submissions', [CourseEnrollmentController::class, 'getEssaySubmissions'])->name('essay.submission.get');
    Route::post('/course/{itemId}/essay-submission', [CourseEnrollmentController::class, 'essaySubmission'])->name('essay.submission.essaySubmission');
    Route::post('/course/{itemId}/mc-submission', [CourseEnrollmentController::class, 'mcSubmission'])->name('multipleChoice.submission.mcSubmission');
    Route::get('/course/{itemId}/mc-submission/check', [CourseEnrollmentController::class, 'getMcSubmission'])->name('multipleChoice.submission.get');
    Route::post('/course/{forumId}/forum-reply', [CourseEnrollmentController::class, 'storeForumReply'])->name('forum.reply.store');
    Route::get('/forum/{forumId}/replies', [CourseEnrollmentController::class, 'getForumDiscussion']);
    Route::post('/item/{itemId}/submission', [CourseEnrollmentController::class, 'uploadSubmission'])->name('submissions.uploadSubmission');
    Route::post('/course/save-progress', [CourseEnrollmentController::class, 'savedProgress'])->name('course.saveProgress');
    Route::get('/course/{id}/progress', [CourseEnrollmentController::class, 'getCourseProgress']);

    /*
    |--------------------------------------------------------------------------
    | Notification 
    |--------------------------------------------------------------------------
    */

    Route::match(['get', 'post'], '/notif/read/{id}', function ($id) {
        $user = Auth::user();
        if (!$user) {
            Log::warning('🚫 Notif Read Attempt - Unauthenticated access.');
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        // 🔍 Ambil notifikasi sebelum update
        $notifBefore = DB::table('user_notifications')
            ->where('notification_id', $id)
            ->where('user_id', $user->user_id)
            ->first();

        if (!$notifBefore) {
            Log::warning('⚠️ Notification not found', [
                'user_id' => $user->user_id,
                'notification_id' => $id,
            ]);
            return redirect()->back()->with('error', 'Notifikasi tidak ditemukan.');
        }

        // 🧾 Log nilai sebelum
        Log::info('📥 Before Update', [
            'user_id' => $user->user_id,
            'notification_id' => $id,
            'is_read_before' => $notifBefore->is_read,
        ]);

        // 🛠 Update nilai
        UserNotification::where('notification_id', $id)
            ->where('user_id', $user->user_id)
            ->update(['is_read' => true]);

        // 🔍 Ambil kembali dari DB (after)
        $notifAfter = DB::table('user_notifications')
            ->where('notification_id', $id)
            ->where('user_id', $user->user_id)
            ->first();

        // 🧾 Log nilai sesudah
        Log::info('📤 After Update', [
            'user_id' => $user->user_id,
            'notification_id' => $id,
            'is_read_after' => $notifAfter->is_read,
            'message' => $notifAfter->message ?? null,
            'time' => now()->toDateTimeString(),
        ]);

        return redirect()->back()->with('success', 'Notifikasi berhasil ditandai dibaca.');
        })->name('notif.read');

    });

    // ✅ Route: tandai semua notifikasi sebagai dibaca
    Route::match(['get', 'post'], '/notif/read-all', function () {
        $user = Auth::user();
        if (!$user) {
            Log::warning('🚫 Mark All Attempt - Unauthenticated access.');
            return redirect()->back()->with('error', 'Unauthorized.');
        }

        // 🔍 Log sebelum update
        $countBefore = UserNotification::where('user_id', $user->user_id)
            ->where('is_read', false)
            ->count();

        // 🔧 Update semua ke read
        $updated = UserNotification::where('user_id', $user->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        Log::info('📤 Mark all notifications as read', [
            'user_id' => $user->user_id,
            'count_before' => $countBefore,
            'count_updated' => $updated,
            'time' => now()->toDateTimeString(),
        ]);

        return redirect()->back()->with('success', 'Semua notifikasi telah ditandai sebagai dibaca.');
    })->name('notif.readAll');

    Route::get('/notif/check-latest', function () {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['hasNew' => false]);
        }

        $latest = UserNotification::where('user_id', $user->user_id)
            ->where('is_read', false)
            ->latest('created_at')
            ->first();

        return response()->json([
            'hasNew' => (bool) $latest,
            'latest' => $latest ? [
                'id' => $latest->notification_id,
                'message' => $latest->message,
                'time' => $latest->created_at->diffForHumans(),
            ] : null,
        ]);
    })->name('notif.checkLatest');

/*
|--------------------------------------------------------------------------
| ADMINISTRATOR & IT 
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:1,2'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 📘 Course List Management
    |--------------------------------------------------------------------------
    */
    Route::get('/course-list', [CourseController::class, 'showListCourse'])
        ->name('administrator.course-list');

    Route::post('/course-list/assign', [CourseController::class, 'assignUsertoCourse'])
        ->name('administrator.course-list.assign');

    Route::post('/course-list/rollback', [CourseController::class, 'rollbackAssignUsertoCourse'])
        ->name('administrator.course-list.rollback');

    Route::post('/course-list/get-assign', [CourseController::class, 'getAssignUserinCourse'])
        ->name('administrator.course-list.getAssign');

    /*
    |--------------------------------------------------------------------------
    | 👥 User Management
    |--------------------------------------------------------------------------
    */
    Route::prefix('user-management')->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->name('administrator.user-mgt');
        Route::post('/store', [UserController::class, 'store'])
            ->name('user.store');
        Route::put('/{id}', [UserController::class, 'update'])
            ->name('user.update');
        Route::put('/{id}/roles', [UserController::class, 'updateRoles'])
            ->name('user.updateRoles');
        Route::put('/{id}/delete', [UserController::class, 'destroy'])
            ->name('user.destroy');
        Route::put('/{id}/status', [UserController::class, 'changeStatus'])
            ->name('user.changeStatus');
        // Route::get('/search', [UserController::class, 'search'])->name('user.search');

    });

    /*
    |--------------------------------------------------------------------------
    | 🧾 Learner Submission Released
    |--------------------------------------------------------------------------
    */
    Route::get('/learner-submission-realeased', [LearnerSubmissionRealeaseController::class, 'index'])
        ->name('administrator.learner-submission-realeased');

    Route::get('/learner-submission-realeased/submissions/{courseId}/{itemId}', 
        [LearnerSubmissionRealeaseController::class, 'showSubmissions'])
        ->name('administrator.learner-submission-realeased.submissions');

    Route::delete('/learner-submission-realeased/delete/{courseId}/{itemId}/{userId}', 
        [LearnerSubmissionRealeaseController::class, 'deleteSubmission'])
        ->name('administrator.learner-submission-realeased.delete');


    /*
    |--------------------------------------------------------------------------
    | KONSULTASI LEARNER (On Dev)
    |--------------------------------------------------------------------------
    */
    Route::get('/learner-consultation', [ConsultationLearnerController::class, 'index'])->name('learner.consultation');
    Route::post('/learner-consultation/store', [ConsultationLearnerController::class, 'store'])->name('learner.consultation.store');
    Route::get('/learner-consultation/{id}', [ConsultationLearnerController::class, 'show'])->name('learner.consultation.show');
    Route::post('/learner-consultation/status/{id}', [ConsultationLearnerController::class, 'updateStatus'])->name('learner.consultation.status');
    
    /*
    |--------------------------------------------------------------------------
    | 📊 HR Reports
    |--------------------------------------------------------------------------
    */
    Route::get('/report', [LaporanHRController::class, 'index'])
        ->name('administrator.report');
    Route::get('/report/getCourse/excel',[LaporanHRController::class, 'courseExportExcel'])
        ->name('administrator.report.course.export');
    Route::get('/report/participants/excel', [LaporanHRController::class, 'AllParticipantCourse'])
        ->name('administrator.report.participant.export');
    Route::get('/report/activity/excel', [LaporanHRController::class, 'ActivityTaskReport'])
        ->name('administrator.activity.report.excel');
    Route::get('/report/learner-progress', [LaporanHRController::class, 'LearnerProgressReport'])
        ->name('administrator.report.learner.progress');
    Route::get('/report/instructor-feedback',[LaporanHRController::class, 'InstructorFeedback'])
        ->name('administrator.instructor.feedback');

});

Route::middleware(['auth', 'role:1,2,3'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | APPROVAL SYSTEM
    |--------------------------------------------------------------------------
    */

    Route::get('/detail-course/{id}', [CourseController::class, 'detailCourse'])
        ->name('instructor.detail-course');

    Route::prefix('approval')->group(function () {
        Route::get('/', [ApprovalSystemController::class, 'index'])->name('approval.index');
        Route::post('/store', [ApprovalSystemController::class, 'store'])->name('approval.store');
        Route::get('/{id}', [ApprovalSystemController::class, 'detail'])->name('approval.detail');
        Route::post('/{id}/process', [ApprovalSystemController::class, 'process'])->name('approval.process');
        Route::post('/{id}/cancel', [ApprovalSystemController::class, 'cancel'])->name('approval.cancel');
        Route::get('/course/{id}/learners', [ApprovalSystemController::class, 'getCourseLearners'])->name('approval.course.learners');
    });
    Route::get('/submission-course/{course_id}/', [LearnerSubmissionController::class, 'index'])
        ->name('instructor.submission-course');
    Route::get('/submission-data/{item_id}/{learner_id}', [LearnerSubmissionController::class, 'getAllSubmissions']);
        Route::get('/track-course/{courseId}', [TrackController::class, 'show'])
        ->name('instructor.track-course');
    Route::get('/submission-course/{course_id}/thread', [LearnerSubmissionController::class, 'getThreadDiscussion']);
    Route::get('/track-course/{courseId}/item/{itemId}/learners', [TrackController::class, 'getLearnerCourse'])
        ->name('track.course.learners');
    Route::get('/track-course/{courseId}/remedial', [TrackController::class, 'getLearnerRemedial'])->name('track.course.remedial');
    Route::get('/track-course/{courseId}/progress', [TrackController::class, 'getLearnerProgress'])->name('track.course.progress');
});
/*
|--------------------------------------------------------------------------
| Instructor & IT 
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:1,3'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | INSTRUCTOR COURSE MANAGEMENT
    |--------------------------------------------------------------------------
    */
    Route::prefix('instructor-course')->group(function () {
        Route::get('/', [CourseController::class, 'index'])->name('instructor.course');
        Route::post('/store', [CourseController::class, 'store'])->name('instructor.store');
        Route::put('/editCourse/{id}', [CourseController::class, 'editDepan'])->name('instructor.edit');
        Route::post('/requested', [CourseController::class, 'requested'])->name('instructor.requested');
        Route::post('/update/{id}', [CourseController::class, 'update'])->name('instructor.update');
        Route::post('/delete/{id}', [CourseController::class, 'destroy'])->name('instructor.delete');
        Route::post('/restore/{id}', [CourseController::class, 'restore'])->name('instructor.restore');
        Route::post('/takeover-instructor', [CourseController::class, 'takeoverByInstructor'])->name('instructor.takeoverbyInstructor');
        Route::post('/takedown/{id}', [CourseController::class, 'takedown'])->name('instructor.takedown');
        Route::post('/duplicate/{id}',[CourseController::class, 'duplicate'])->name('course.duplicate');


        // Route::delete('/takeover-instructor', [CourseController::class, 'resetTakeoverByInstructor'])->name('instructor.takeoverbyInstructorReset');

    });

    /*
    |--------------------------------------------------------------------------
    | MODIFY COURSE (Instructor editing)
    |--------------------------------------------------------------------------
    */

    // Route::get('/modify-course/{id}', [CourseController::class, 'modifyCourse'])->name('instructor.modify-course');
    Route::get('/modify-course/{id}', function (Request $request, $id) {
        $course = Course::findOrFail($id);

        if ($course->is_approved == 1) {
            abort(403, 'This course has already been approved and cannot be modified.');
        }

        return app(CourseController::class)->modifyCourse($request,$id);
    })->name('instructor.modify-course');

    Route::post('/modify-course/autoSave/{id}', [CourseController::class, 'courseModuleWeek'])->name('instructor.courseModuleWeek');
    Route::post('/modify-course/autoSaveItem/{courseWeekId}', [CourseController::class, 'courseWeekItem'])->name('instructor.courseWeekItem');
    Route::post('/modify-course/{id}/draft', [CourseController::class, 'courseModule'])->name('courses.modules.courseModule');
    Route::post('/modify-course/item/{id}', [CourseController::class, 'itemCourseModule'])
    ->name('courses.modules.itemCourseModule');
    Route::put('/modify-course/item/{id}', [CourseController::class, 'itemEditCourseModule'])->name('modify-course.item.update');
    Route::post('/modify-course/item/{id}/essay', [CourseController::class, 'EssayItemModule'])->name('modify-course.item.essay');
    // Route::put('/modify-course/item/{id}/essay', [CourseController::class, 'updateEssayItemModule'])->name('modify-course.item.essay.update');
    Route::post('/modify-course/item/{id}/choice', [CourseController::class, 'MultipleChoice'])->name('modify-course.item.choice');
    Route::delete('/modify-course/item/question/{id}', [CourseController::class, 'DeleteMultiplyChoice'])->name('modify-course.item.choice.delete');
    Route::post('/modify-course/{id}/forum-discussion', [CourseController::class, 'forumDiscussion'])->name('modify-course.forum.store');
    Route::delete('/modify-course/module/{id}', [CourseController::class, 'deleteModule'])->name('courses.modules.deleteModule');
    Route::delete('/modify-course/item/{id}', [CourseController::class, 'deleteItem'])->name('courses.modules.deleteItem');
    Route::get('/modify-course/forum/get-threads/{course_id}', [CourseController::class, 'getThreads']);
    Route::post('/modify-course/forum/discussion-thread', [CourseController::class, 'discussionThread'])->name('forum.discussionThread');
    Route::delete('/modify-course/forum/thread/{id}', [CourseController::class, 'deleteThread'])->name('forum.thread.delete');
    
    Route::post('/modify-course/course/update-week-order', [CourseController::class, 'updateWeekOrder'])->name('update.week.order');
    Route::post('/modify-course/course/update-item-order', [CourseController::class, 'updateItemOrder'])->name('course.updateItemOrder');
    
    // MODULE MOVE
    // Route::post('/modify-course/module/move', [CourseController::class, 'moveModule'])
    //     ->name('course.module.move');

    // // ITEM MOVE
    // Route::post('/modify-course/item/move', [CourseController::class, 'moveItem'])
    //     ->name('course.item.move');


    /*
    |--------------------------------------------------------------------------
    | SUBMISSION MANAGEMENT
    |--------------------------------------------------------------------------
    */
    // Route::get('/submission-course/{course_id}/', [LearnerSubmissionController::class, 'index'])
    //     ->name('instructor.submission-course');
    // Route::get('/submission-data/{item_id}/{learner_id}', [LearnerSubmissionController::class, 'getAllSubmissions']);
    // Route::get('/submission-course/{course_id}/thread', [LearnerSubmissionController::class, 'getThreadDiscussion']);
    Route::post('/submission-feedback/{item_id}/{learner_id}', [LearnerSubmissionController::class, 'applyFeedback']);

    /*
    // -----------------------
    // Course Takeover
    // -----------------------
    */
    Route::get('/takeover', [TakeoverController::class, 'takeoverPage'])
            ->name('instructor.takeover');
    Route::post('/takeover/finish/{id}', [TakeoverController::class, 'finish'])->name('takeover.finish');
    Route::post('/takeover/editCourseDepan/{id}',[TakeoverController::class, 'editDepanTakeover'])->name('takeover.courseMasterEdit');
    Route::post('/takeover/duplicate/{id}', [TakeoverController::class, 'duplicate'])->name('takeover.courseDuplicate');
    Route::post('/takeover/revert/{id}', [TakeoverController::class, 'revert'])->name('takeover.revert');

    /*
    // -----------------------
    // Track Learner
    // -----------------------
    */
    // Route::get('/track-course/{courseId}', [TrackController::class, 'show'])
    //     ->name('instructor.track-course');
    // Route::get('/track-course/{courseId}/item/{itemId}/learners', [TrackController::class, 'getLearnerCourse'])
    //     ->name('track.course.learners');
    // Route::get('/track-course/{courseId}/remedial', [TrackController::class, 'getLearnerRemedial'])->name('track.course.remedial');
    // Route::get('/track-course/{courseId}/progress', [TrackController::class, 'getLearnerProgress'])->name('track.course.progress');




      /*
    |--------------------------------------------------------------------------
    | COURSE MODULES
    |--------------------------------------------------------------------------
    */
    Route::get('/courses/{course}/modules', [CourseWeekModuleController::class, 'index'])->name('courses.modules.index');
    Route::post('/courses/{course}/modules/draft', [CourseWeekModuleController::class, 'storeDraft'])->name('courses.modules.storeDraft');
    Route::post('/courses/{course}/modules/submit', [CourseWeekModuleController::class, 'submitForPreview'])->name('courses.modules.submit');


});

Route::middleware(['auth', 'role:1,4'])->group(function () {

    /*
    // -----------------------
    // Kursus Saya (Learner)
    // -----------------------
    */
    Route::get('/courses/explore', [CourseExplorerController::class, 'index'])->name('learner.explore');
    Route::get('/courses/{courseId}', [CourseExplorerController::class, 'show'])->name('learner.detail-course');
    Route::post('/courses/{courseId}/join', [CourseExplorerController::class, 'requestJoinUser'])->name('learner.request-join');
    Route::get('/learner-course', [CourseEnrollmentController::class, 'index'])->name('learner.course');
    // Route::get('/detail-course/course-enrolled/{id}', [CourseEnrollmentController::class, 'detailMyCourse'])->name('learner.course-enrolled');

    Route::get('/detail-course/course-enrolled/{id}', function ($id) {
        $course = Course::findOrFail($id);
        $user = Auth::user();
        $now    = Carbon::now(); // ⏱️ waktu sekarang

        
        // ============================
        // 1️⃣ CEK KEIKUTSERTAAN USER
        // ============================
        if ($user) {
            $isEnrolled = \DB::table('course_enrollment')
                ->where('course_id', $course->course_id)
                ->where('user_id', $user->user_id)
                ->exists();

            if (!$isEnrolled) {
                abort(403, '⚠️ Anda tidak tergabung di dalam course ini.');
            }
        }
        
        // ============================
        // ⏳ CEK STATUS WAKTU COURSE
        // ============================
        if ($course->start_course && $now->lt($course->start_course)) {
            abort(403, '⏳ Course belum dibuka.');
        }

        // if ($course->end_course && $now->gt($course->end_course)) {
        //     abort(403, '⛔ Course sudah berakhir.');
        // }
        
        if ($user) {
            // Ambil semua role (utama + sub_role)
            $roles = [(int)$user->role_id];

            if (!empty($user->sub_role)) {
                $subRoles = is_array($user->sub_role)
                    ? $user->sub_role
                    : json_decode($user->sub_role, true);
                $roles = array_merge($roles, $subRoles);
            }

            // 🔒 Jika salah satu role bernilai 4 (learner) dan course belum disetujui
            if (in_array(4, $roles) && !$course->is_approved) {
                abort(403, '🚫 Course ini belum disetujui dan tidak dapat diakses.');
            }
        }

        // ✅ Jika approved atau user tidak punya role learner
        return app(\App\Http\Controllers\LMS\CourseEnrollmentController::class)->detailMyCourse($id);
    })->name('learner.course-enrolled');

    Route::get('/detail-course/course-enrolled/{course_id}/threads', [CourseEnrollmentController::class, 'GetThread'])
        ->name('learner.forum.threads');
    Route::get('/detail-course/course-enrolled/thread/{thread_id}/replies', [CourseEnrollmentController::class, 'ReplyThread'])
        ->name('learner.forum.replies');
    Route::post('/detail-course/course-enrolled/thread/{thread_id}/reply', [CourseEnrollmentController::class, 'StoreReply']);
    // 🔹 Simpan feedback dari form (dalam modal atau halaman terpisah)
    Route::post('/feedback/store', [CourseEnrollmentController::class, 'storeFeedback'])
        ->name('feedback.store');

});

Route::middleware(['auth', 'role:1'])->group(function () {
    /*
    // -----------------------
    // Menu IT
    // -----------------------
    */
    Route::get('/dashboard/it/activity', [ITDashboardController::class, 'ActivityInquiry'])
    ->name('dashboard.it.activity');
    Route::post('/report/export', [LaporanHRController::class, 'export'])
    ->name('dashboard.it.activity');
});