<?php

namespace App\Http\Controllers\Api\Job;

use App\Mail\ApplicationApproved;
use App\Mail\ApplicationRejected;
use App\Models\Job;
use App\Models\job_user;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class JobApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user(); // Get the currently authenticated user

        // Check if the user has a company associated with them
        if (!$user->companies) {
            return response()->json([
                'success' => false,
                'message' => 'Không có thông tin công ty.'
            ], 403);
        }

        // Get the company ID of the authenticated user
        $companyId = $user->companies->id;

        // Load only jobs that belong to the company of the authenticated user,

        $jobs = Job::with(['applicants' => function ($query) {
            $query->withPivot('status', 'cv'); // Include pivot table fields
        }])->where('company_id', $companyId) // Assuming the jobs table has a 'company_id' column
        ->get();
        $jobsData = $jobs->map(function ($job) {
            return [
                'id' => $job->id,
                'title' => $job->title,
                'salary' => $job->salary,
                'job_type' => $job->jobtype ? $job->jobtype->pluck('name')->toArray() : null,
                'job_city' => $job->jobcity ? $job->jobcity->pluck('name')->toArray() : null,
                'skills' => $job->skill->pluck('name')->toArray(),
                'address' => $job->company ? $job->company->address : null,
                'description' => $job->description,
                'status' => $job->status,
                'skill_experience' => $job->skill_experience,
                'benefits' => $job->benefits,
                'last_date' => $job->last_date,
                'created_at' => $job->created_at->diffForHumans(),
                'applicants' => $job->applicants->map(function ($applicant) {
                    return [
                        'id' => $applicant->id,
                        'name' => $applicant->name,
                        'email' => $applicant->email,
                        'status' => $applicant->pivot->status,
                        'cv' => $applicant->pivot->cv,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $jobsData,
            'status_code' => 200
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(job_user $job_user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(job_user $job_user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, job_user $job_user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(job_user $job_user)
    {
        //
    }

    public function processApplication(Request $request, $jobId, $userId)
    {
        $job = Job::find($jobId);
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Công việc không tồn tại.',
                'status_code' => 404,
            ], 404);
        }

        // Check if the authenticated user is the owner of the job
        if ($job->users_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền thực hiện chức năng này.',
                'status_code' => 403,
            ], 403);
        }
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Người dùng không tồn tại.',
                'status_code' => 404
            ], 404);
        }

        $status = $request->input('status');
        if (!in_array($status, ['approved', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'Trạng thái không hợp lệ.',
                'status_code' => 400
            ], 400);
        }

        // Update the status of the application
        $job->users()->updateExistingPivot($user->id, ['status' => $status]);

        // Send notification email to the applicant
        if ($status === 'approved') {
            Mail::to($user->email)->send(new ApplicationApproved($job, $user));
        } elseif ($status === 'rejected') {
            Mail::to($user->email)->send(new ApplicationRejected($job, $user));
        }

        return response()->json([
            'success' => true,
            'message' => 'Xử lí đơn ứng tuyển thành công.',
            'status_code' => 200
        ], 200);
    }

    public function toggle(Request $request, $id)
    {
        try {
            // Find the job by ID
            $job = Job::findOrFail($id);

            // Check if the authenticated user is the owner of the job
            if ($job->users_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bạn không có quyền thực hiện chức năng này.',
                    'status_code' => 403
                ], 403);
            }

            // Toggle the status
            $job->status = !$job->status;

            // Save the changes
            $job->save();

            // Return a JSON response indicating success
            return response()->json([
                'success' => true,
                'message' => 'Trạng thái đã được cập nhật thành công!',
                'status_code' => 200
            ], 200);
        } catch (\Exception $e) {
            // Handle the case where an exception occurred
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái.',
                'error' => $e->getMessage(),
                'status_code' => 500
            ], 500);
        }
    }

}


