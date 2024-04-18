<?php

namespace App\Http\Controllers\Api\Companies;

use App\Http\Controllers\Controller;
use App\Mail\JobApplied;
use App\Models\Job;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class JobsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Lấy người dùng hiện tại
        $user = auth()->user();

        // Kiểm tra xem người dùng có một công ty không
        if ($user->companies) {
            // Lấy danh sách công việc của công ty của người dùng hiện tại
            $jobs = $user->companies->jobs()->paginate(5);

            $jobsData = $jobs->map(function ($job) {
                return [
                    'id' => $job->id,
                    'title' => $job->title,
//                    'company' => $job->company ? $job->company->name : null,
                    'job_type' => $job->jobtype ? $job->jobtype->pluck('name')->toArray() : null,
                    'job_city' => $job->jobcity ? $job->jobcity->pluck('name')->toArray() : null,
                    'salary' => $job->salary,
                    'status' => $job->status,
                    'featured' => $job->featured,
                    'address' => $job->address,
                    'description' => $job->description,
                    'skills' => $job->skill->pluck('name')->toArray(),
                    'skill_experience' => $job->skill_experience,
                    'benefits' => $job->benefits,
                    'last_date' => $job->last_date,
                    'created_at' => $job->created_at->diffForHumans(),
                ];
            });

            // Trả về dữ liệu dưới dạng JSON
            return response()->json([
                'success' => true,
                'message' => 'success',
                'data' => $jobsData,
                'links' => [
                    'first' => $jobs->url(1),
                    'last' => $jobs->url($jobs->lastPage()),
                    'prev' => $jobs->previousPageUrl(),
                    'next' => $jobs->nextPageUrl(),
                ],
                'status_code' => 200
            ]);
        }

        // Nếu người dùng không có công ty, trả về thông báo lỗi hoặc thông tin tùy thuộc vào yêu cầu của bạn
        return response()->json([
            'success' => false,
            'message' => 'User does not have a company.',
            'status_code' => 404
        ], 404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $company = $user->companies->id;

        $validator = Validator::make($request->all(), [
            'jobtype_id' => 'required',
            'city_id' => 'required',
            'title' => 'required',
            'salary' => 'required|numeric',
            'status' => 'required|integer',
            'featured' => 'required|integer',
            'description' => 'required|string',
            'last_date' => 'required|date',
            'address' => 'required|string',
            'skill_experience' => 'required|string',
            'benefits' => 'required|string',
            'job_skills' => 'required|array',  // Validate that job_skills is an array.
            'job_skills.*.name' => 'required|string', // Validate that each skill has a name.
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        $validatedData = $validator->validated();

        // Add users_id and company_id to the validated data array.
        $validatedData['users_id'] = $user->id;
        $validatedData['company_id'] = $company ;

        // You may want to handle the case when a company is not found.
        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'No company found for the user.',
            ], 404);
        }

        // Extract job skills data from the request and remove it from the validated data
        $jobSkillsData = $validatedData['job_skills'];
        unset($validatedData['job_skills']);

        try {
            // Start a transaction
            \DB::beginTransaction();

            // Create the job
            $job = Job::create($validatedData);

            // Attach the job skills to the job
            foreach ($jobSkillsData as $skillData) {
                $job->jobSkills()->create($skillData);
            }

            // Commit the transaction
            \DB::commit();

            $jobData = [
                'id' => $job->id,
                'title' => $job->title,
                'job_type' => $job->jobtype ? $job->jobtype->pluck('name')->toArray() : null,
                'salary' => $job->salary,
                'status' => $job->status ? 'active' : 'inactive',
                'featured' => $job->featured ?  'active' : 'inactive',
                'address' => $job->address,
                'description' => $job->description,
                'skill_experience' => $job->skill_experience,
                'last_date' => $job->last_date,
                'benefits' => $job->benefits,
                'job_skills' => $job->jobSkills->pluck('name')->toArray(),
            ];
            return response()->json([
                'success' => true,
                'message' => 'Job and job skills created successfully.',
                'data' => $jobData,
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction
            \DB::rollBack();

            \Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Job creation failed.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
//    public function show(Job $job)
//    {
//        // Lấy các công việc đề xuất
//        $jobRecommendation = $this->jobRecommend($job);
//
//        // Tạo một mảng dữ liệu chứa thông tin về công việc và đề xuất công việc
//        $responseData = [
//            'job' => $job,
//            'job_recommendation' => $jobRecommendation,
//        ];
//
//        // Trả về dữ liệu dưới dạng JSON
//        return response()->json([
//            'success' => true,
//            'message' => 'success',
//            'data' => $responseData,
//        ]);
//    }

    public function show(Job $job)
    {
        // Load relationships in advance
        $job->load('jobtype', 'skill', 'jobcity');
        // Prepare data for the job
        $jobData = [
            'id' => $job->id,
            'title' => $job->title,
            'salary' => $job->salary,
            'status' => $job->status,
            'featured' => $job->featured,
            'description' => $job->description,
            'benefits' => $job->benefits,
            'last_date' => $job->last_date,
            'job_type' => $job->jobtype ? $job->jobtype->pluck('name')->toArray() : null,
            'job_city' => $job->jobcity ? $job->jobcity->pluck('name')->toArray() : null,
            'skills' => $job->skill->pluck('name')->toArray(),
            'address' => $job->address,
            'created_at' => $job->created_at->diffForHumans(),
        ];

        // Return data as JSON response
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $jobData,
            'status_code' => 200
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Job $job)
    {
        if ($request->user()->id !== $job->company->users_id ) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền chỉnh sửa job này.',
                'status_code' => 403
            ], 403); // 403 là mã lỗi "Forbidden" khi người dùng không có quyền truy cập
        }
        try {
            // Start a transaction
            \DB::beginTransaction();

            // Update the job with the request data
            $job->update([
                'jobtype_id' => $request->input('jobtype_id'),
                'city_id' => $request->input('city_id'),
                'title' => $request->input('title'),
                'salary' => $request->input('salary'),
                'status' => $request->input('status'),
                'featured' => $request->input('featured'),
                'description' => $request->input('description'),
                'last_date' => $request->input('last_date'),
                'address' => $request->input('address'),
                'skill_experience' => $request->input('skill_experience'),
                'benefits' => $request->input('benefits'),
            ]);

            // If 'job_skills' are provided in the request, update job skills accordingly
            // If 'job_skills' are provided in the request, update job skills accordingly
            if ($request->has('job_skills')) {
                $jobSkillsData = $request->input('job_skills');
                $existingSkills = $job->jobSkills()->pluck('name')->toArray();

                // Delete job skills that are not in the updated list
                foreach ($existingSkills as $existingSkill) {
                    if (! in_array($existingSkill, array_column($jobSkillsData, 'name'))) {
                        $job->jobSkills()->where('name', $existingSkill)->delete();
                    }
                }

                // Attach the updated job skills to the job
                foreach ($jobSkillsData as $skillData) {
                    $job->jobSkills()->updateOrCreate(['name' => $skillData['name']], $skillData);
                }
            }

            // Commit the transaction
            \DB::commit();

            $jobData = [
                'id' => $job->id,
                'title' => $job->title,
                'job_type' => $job->jobtype ? $job->jobtype->pluck('name')->toArray() : null,
                'salary' => $job->salary,
                'status' => $job->status ? 'active' : 'inactive',
                'featured' => $job->featured ?  'active' : 'inactive',
                'address' => $job->address,
                'description' => $job->description,
                'skill_experience' => $job->skill_experience,
                'last_date' => $job->last_date,
                'benefits' => $job->benefits,
                'job_skills' => $job->jobSkills->pluck('name')->toArray(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Job and job skills updated successfully.',
                'data' => $jobData,
                'status_code' => 200
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction
            \DB::rollBack();

            \Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Job update failed.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Job $job)
    {
        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully',
            'status_code' => 200
        ]);
    }

    public function jobRecommend(Job $job)
    {
        $currentJobSkills = $job->skill->pluck('name')->toArray();

        // Lấy tất cả các công việc khác từ cơ sở dữ liệu
        $otherJobs = Job::with('skill')->where('id', '!=', $job->id)->get();

        // Lọc các công việc khác để chỉ chọn những công việc có ít nhất một kỹ năng giống với công việc hiện tại
        $recommendedJobs = $otherJobs->filter(function ($otherJob) use ($currentJobSkills) {
            // Lấy kỹ năng của công việc khác

            $otherJobSkills = $otherJob->skill->pluck('name')->toArray();

            // Kiểm tra xem công việc khác có chứa ít nhất một kỹ năng của công việc hiện tại không
            $hasSkill = count(array_intersect($currentJobSkills, $otherJobSkills)) > 0;

            return $hasSkill;
        });

        return $recommendedJobs;
    }

    public function apply(Request $request, $id)
    {
        $job = Job::find($id);
        if (!$job) {
            return response()->json(['message' => 'Công việc không tồn tại.'], 404);
        }

        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($job->users()->where('users.id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Bạn đã ứng tuyển công việc này rồi.',
                'status_code' => 409
            ], 409);
        }

        // Check if a new CV file was uploaded
        if ($request->hasFile('cv')) {
            $cv = $request->file('cv');
            $cvFileName = time() . '_' . $cv->getClientOriginalName();
            $cv->storeAs('cv', $cvFileName); // Store the CV file in storage/cv directory
        } else {
            // No new CV file was uploaded, attempt to use the default CV
            $defaultCv = $user->cvs()->where('is_default', true)->first();
            $cvFileName = $defaultCv ? $defaultCv->file_path : null;
        }

        if (!$cvFileName) {
            return response()->json([
                'message' => 'Không có CV mặc định và không có CV mới được tải lên.',
                'status_code' => 400
            ], 400);
        }

        // Continue with the application process...
        Mail::to($user->email)->send(new JobApplied($job, $user, $cvFileName));
        $job->users()->attach($user->id, ['status' => 'pending', 'cv' => $cvFileName]);

        return response()->json(
            [
                'message' => 'Ứng tuyển công việc thành công.',
                'status_code' => 200,
            ], 200);
    }



    public function applicant()
    {
        $user = Auth::guard('sanctum')->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $appliedJobs = $user->jobs()->withPivot('status')->get();

        $formattedJobs = $appliedJobs->map(function ($job) {
            $company = $job->company()->first();
            $jobType = $job->jobType()->first();
            $city = $job->jobcity()->first();
            $lastDate = Carbon::createFromFormat('Y-m-d', $job->last_date);
            $daysRemaining = $lastDate->diffInDays(Carbon::now());
            return [
                'id' => $job->id,
                'company' => $company ? $company->name : null, // Kiểm tra xem công ty có tồn tại không trước khi truy cập trường name
                'job_type' => $jobType ? $jobType->name : null, // Kiểm tra xem loại công việc có tồn tại không trước khi truy cập trường name
                'city' => $city ? $city->name : null, // Kiểm tra xem thành phố có tồn tại không trước khi truy cập trường name
                'title' => $job->title,
                'salary' => $job->salary,
                'status' => $job->pivot->status,
//                'featured' => $job->featured,
                'address' => $job->address,
                'description' => $job->description,
                'skill_experience' => $job->skill_experience,
                'benefits' => $job->benefits,
                'last_date' => $job->last_date,
                'last_date' => $daysRemaining,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $formattedJobs,
            'status_code' => 200
        ], 200);
    }




    public function search(Request $request)
    {
        $searchQuery = $request->input('query', '');

        // If the search query is empty, return all jobs
        if (empty($searchQuery)) {
            return $this->index();
        }

        // Search for jobs where the title or description contains the search query
        // You can adjust the fields you wish to search in
        $jobs = Job::with('jobtype', 'skill', 'company')
            ->where('title', 'like', "%{$searchQuery}%")
            ->orWhere('description', 'like', "%{$searchQuery}%")
            ->orWhereHas('skill', function ($query) use ($searchQuery) {
                $query->where('name', 'like', "%{$searchQuery}%");
            })
            ->orWhereHas('jobtype', function ($query) use ($searchQuery) {
                $query->where('name', 'like', "%{$searchQuery}%");
            })
            ->orWhereHas('company', function ($query) use ($searchQuery) {
                $query->where('name', 'like', "%{$searchQuery}%");
            })
            ->paginate(5);

        // Transform the jobs as done in the index method or any other preferred format
        $jobsData = $jobs->map(function ($job) {
            return [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $job->company ? $job->company->name : null,
                'salary' => $job->salary,
                'job_type' => $job->jobtype ? $job->jobtype->pluck('name')->toArray() : null,
                'skills' => $job->skill->pluck('name')->toArray(),
                'address' => $job->address,
                'last_date' => $job->last_date,
                'created_at' => $job->created_at->diffForHumans(),
            ];
        });

        // Return the search results
        return response()->json([
            'success' => true,
            'message' => 'Search results',
            'data' => $jobsData,
            'links' => [
                'first' => $jobs->url(1),
                'last' => $jobs->url($jobs->lastPage()),
                'prev' => $jobs->previousPageUrl(),
                'next' => $jobs->nextPageUrl(),
            ],
            'status_code' => 200
        ]);
    }

    /**
     * Get list of saved jobs
     */
    public function savedJobs(Request $request)
    {
        $user = $request->user();
        $savedJobs = $user->favorites;

        $savedJobsData = $savedJobs->map(function ($job) {
            return [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $job->company ? $job->company->name : null,
                'salary' => $job->salary,
                'job_type' => $job->jobtype ? $job->jobtype->pluck('name')->toArray() : null,
                'skills' => $job->skill->pluck('name')->toArray(),
                'address' => $job->address,
                'last_date' => $job->last_date,
                'created_at' => $job->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Saved jobs',
            'data' => $savedJobsData,
            'status_code' => 200
        ], 200);
    }

    /**
     * Get list of applied jobs
     */
    public function appliedJobs(Request $request)
    {
        $user = $request->user();
        $appliedJobs = $user->jobs;
    }

    /**
     * Save Job in favorite table
     */
    public function saveJob(Request $request, $id)
    {
        $job = Job::findOrFail($id);
        $user = $request->user();

        // Kiểm tra xem công việc đã được thêm vào danh sách yêu thích của người dùng chưa
        if ($user->favorites()->where('job_id', $job->id)->exists()) {
            return response()->json(['message' => 'Job is already saved to favorites'], 200);
        } else {
            // Nếu công việc chưa được thêm vào danh sách yêu thích, thực hiện thêm mới
            $user->favorites()->syncWithoutDetaching([$job->id]);
            return response()->json([
                 'success' => 'true',
                 'message' => 'Job saved to favorites',
                 'status_code' => 200
            ], 200);
        }
    }


    /**
     * Unsave Job from favorite table
     */
    public function unsaveJob(Request $request, $id)
    {
        $job = Job::findOrFail($id);
        $user = $request->user();
        $user->favorites()->detach($job->id);

        return response()->json([
            'success' => 'true',
            'message' => 'Job removed from favorites',
            'status_code' => 200,

        ], 200);
    }

    public function indexShow()
    {
        $jobs = Job::where('status', 1)
            ->orderBy('created_at', 'desc') // Sắp xếp theo thời gian tạo mới nhất
            ->with('jobtype', 'skill', 'company','jobcity')
            ->paginate(5);

        $jobsData = $jobs->map(function ($job) {
            return [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $job->company ? $job->company->name : null,
                'salary' => $job->salary,
                'job_type' => $job->jobtype ? $job->jobtype->pluck('name')->toArray() : null,
                'job_city' => $job->jobcity ? $job->jobcity->pluck('name')->toArray() : null,
                'skills' => $job->skill->pluck('name')->toArray(),
                'address' => $job->company->address,
                'last_date' => $job->last_date,
                'created_at' => $job->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => $jobsData,
            'links' => [
                'first' => $jobs->url(1),
                'last' => $jobs->url($jobs->lastPage()),
                'prev' => $jobs->previousPageUrl(),
                'next' => $jobs->nextPageUrl(),
            ],
            'status_code' => 200
        ]);
    }


    public function showJob(Job $job)
    {
        // If the job does not exist, return a 404 response
        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found',
                'status_code' => 404
            ]);
        }

        try {
            $job->load('jobtype', 'skill', 'company', 'jobcity');

            // Prepare job data
            $jobData = [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $job->company ? $job->company->name : null,
                'salary' => $job->salary,
                'job_type' => $job->jobtype ? $job->jobtype->pluck('name')->toArray() : null,
                'jobcity' => $job->jobcity ? $job->jobcity->pluck('name')->toArray() : null,
                'skills' => $job->skill->pluck('name')->toArray(),
                'address' => $job->company->address,
                'description' => $job->description,
                'skill_experience' => $job->skill_experience,
                'benefits' => $job->benefits,
                'last_date' => $job->last_date,
                'created_at' => $job->created_at->diffForHumans(),
            ];

            // Get job recommendations and format them
            $jobRecommendations = $this->jobRecommend($job)->take(5)->map(function ($recommendedJob) {
                return [
                    'id' => $recommendedJob->id,
                    'title' => $recommendedJob->title,
                    'company' => $recommendedJob->company ? $recommendedJob->company->name : null,
                    'salary' => $recommendedJob->salary,
                    'job_type' => $recommendedJob->jobtype ? $recommendedJob->jobtype->pluck('name')->toArray() : null,
                    'job_city' => $recommendedJob->jobcity ? $recommendedJob->jobcity->pluck('name')->toArray() : null,
                    'skills' => $recommendedJob->skill->pluck('name')->toArray(),
                    'address' => $recommendedJob->company->address,
                    'last_date' => $recommendedJob->last_date,
                    'created_at' => $recommendedJob->created_at->diffForHumans(),
                ];
            })->toArray();

            // Return the successful response with job details and recommendations
            return response()->json([
                'success' => true,
                'message' => 'Job details retrieved successfully',
                'data' => [
                    'job' => $jobData,
                    'jobRecommendations' => $jobRecommendations,
                ],
                'status_code' => 200
            ]);

        } catch (\Exception $e) {
            // Return a response indicating there was an error
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving job details',
                'error' => $e->getMessage(),
                'status_code' => 500
            ]);
        }
    }

}
