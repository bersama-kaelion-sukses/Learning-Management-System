<?php

namespace App\Http\Controllers\LMS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CourseWeekModuleController extends Controller
{

    public function index(Request $request, $courseId)
    {

        // 1) Get course_id form CourseController.php
        // 2) Fetch course module that have course_id = {id} 

        $modules = DB::table('course_week_module')
                  ->where('course_id', $courseId)
                  ->orderBy('created_at', 'asc')
                  ->get();

        return view('instructor.modules.index', [
            'course_id' => $courseId,
            'modules'   => $modules,
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function storeDraft(Request $request, $courseId)
    {
        $validated = $request->validate([
            'modules'               => 'required|array|min:1',
            'modules.*.title'       => 'required|string|max:255',
            'modules.*.visibility'  => 'required|in:public,private,unlisted',
            'modules.*.start_date'  => 'nullable|date',
            'modules.*.end_date'    => 'nullable|date|after_or_equal:modules.*.start_date',
        ]);

        DB::transaction(function () use ($validated, $courseId) {
            foreach ($validated['modules'] as $mod) {
                DB::table('course_week_module')->insert([
                    'course_id'             => $courseId,
                    'course_week_title'     => $mod['title'],
                    'course_week_visibility'=> $mod['visibility'],
                    'course_start'          => $mod['start_date'] ?? null,
                    'course_end'            => $mod['end_date'] ?? null,
                    'is_checked'            => 0,
                    'is_approve'            => 0,          // 0 = draft / belum approve
                    'last_process'          => 'draft',
                    'person_process'        => auth()->user()->emp_id ?? auth()->id(),
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }
        });

        return response()->json(['message' => 'Sekat-sekat (modules) disimpan sebagai draft.']);
    }

    /**
     * Ajukan sekat-sekat utk pratinjau/approval.
     * Opsinya 2:
     *  A) Kalau sekat BELUM ada di DB → kirim modules[] juga (seperti draft) lalu set status ajukan.
     *  B) Kalau sekat SUDAH di DB (hasil draft) → cukup ubah status course atau flag sekat ke waiting_approval.
     * Di sini kupakai opsi B: ubah flag sekat draft -> waiting_approval.
     */

    /**
     * Display the specified resource.
     */
    public function update(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function destroy(string $id)
    {
        //
    }
}
