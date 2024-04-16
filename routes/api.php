<?php

use App\Http\Controllers\Api\Admin\CompanysizesController;
use App\Http\Controllers\Api\Admin\CompanytypesController;
use App\Http\Controllers\Api\Admin\CountriesController;
use App\Http\Controllers\Api\Admin\Job_typesController;
use App\Http\Controllers\Api\Admin\LocationsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Companies\CompaniesController;
use App\Http\Controllers\Api\Companies\CompanyLocationsController;
use App\Http\Controllers\Api\Companies\JobsController;
use App\Http\Controllers\Api\Employer\EmployerRegisterController;
use App\Http\Controllers\Api\Job\JobApplicationController;
use App\Http\Controllers\Api\Recruitments\experience_levelController;
use App\Http\Controllers\Api\Resume\AboutmeController;
use App\Http\Controllers\Api\Resume\AwardsController;
use App\Http\Controllers\Api\Resume\CertificatesController;
use App\Http\Controllers\Api\Resume\CvsController;
use App\Http\Controllers\Api\Resume\EducationController;
use App\Http\Controllers\Api\Resume\ExperiencesController;
use App\Http\Controllers\Api\Resume\GetResumeController;
use App\Http\Controllers\Api\Resume\profilesController;
use App\Http\Controllers\Api\Resume\ProjectsController;
use App\Http\Controllers\Api\Resume\skillsController;
use App\Http\Controllers\Api\User\UserJobController;
use App\Http\Middleware\CheckUserRole;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//User Jobs
Route::get('/', [JobsController::class, 'indexShow']);
Route::get('/jobs/{job}', [JobsController::class, 'showJob']);
Route::get('/search', [JobsController::class, 'search']);
Route::get('/companies1', [CompaniesController::class, 'indexShow']);
Route::get('/companies1/{company}', [CompaniesController::class, 'show']);

//Auth
Route::post('employer/register', [EmployerRegisterController::class, 'employerRegister']);
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::resource('profile', profilesController::class);
    Route::resource('profiles/educations', EducationController::class);
    Route::resource('profiles/skills', skillsController::class);
    Route::resource('profiles/aboutMe', AboutmeController::class);
    Route::resource('profiles/certificates', CertificatesController::class);
    Route::resource('profiles/awards', AwardsController::class);
    Route::resource('profiles/projects', ProjectsController::class);
    Route::resource('profiles/getResume', GetResumeController::class);
    Route::resource('profiles/experiences', ExperiencesController::class);
    Route::post('/upload-cv', [\App\Http\Controllers\Api\Resume\CvsController::class, 'upload']);
    Route::apiResource('cvs', 'App\Http\Controllers\Api\Resume\CvsController');


    // Route cho việc đặt CV mặc định
    Route::get('/default-cv',  [\App\Http\Controllers\Api\Resume\CvsController::class, 'getDefaultCv']);
    Route::put('/cvs/{cv}/set-default', [CvsController::class, 'setDefault'])->name('cvs.set-default');
    //Company
    Route::resource('companies', CompaniesController::class);
    Route::resource('companies/location', CompanyLocationsController::class);

    //Apply

    Route::post('/jobs/{id}/apply', [JobsController::class, 'apply']);
    Route::get('/viewAppliedJobs', [JobsController::class, 'applicant']);

    Route::middleware(CheckUserRole::class)->group(function () {
        Route::resource('job', JobsController::class);
    });

    //favorites Job User
    Route::post('/favorites/{id}/save', [JobsController::class, 'saveJob']);
    Route::post('/favorites/{id}/unsave', [JobsController::class, 'unsaveJob']);
    Route::get('/favorites/saved-jobs', [JobsController::class, 'savedJobs']);
    Route::get('/appliedJobs', [JobsController::class, 'appliedJobs']);


    //JobAdmin
    Route::post('/{id}/toggle', [JobApplicationController::class, 'toggle']);
    Route::get('/applications', [JobApplicationController::class, 'index']);
    Route::post('/processApplication/{jobId}/{userId}', [JobApplicationController::class, 'processApplication']);
    Route::get('/getStatistics', [JobApplicationController::class, 'getStatistics']);


    //Admin
    Route::resource('Admin/jobTypes', Job_typesController::class);
    Route::resource('Admin/locations', LocationsController::class);
    Route::resource('Admin/country', CountriesController::class);
    Route::resource('Admin/companyType', CompanytypesController::class);
    Route::resource('Admin/companySize', CompanysizesController::class);
    Route::Delete('logout', [AuthController::class, 'logout']);

});
