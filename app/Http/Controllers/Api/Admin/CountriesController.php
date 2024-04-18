<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Country;
use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $country = Country::all();
        return response()->json([
            'success'   => true,
            'message'   => "success",
            "data" => $country,
            'status_code' => 200
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user()->id;
        $data = [
            'users_id' =>$user,
            'name' => $request->input('name'),
        ];


        $validator = Validator::make($data, [
            'users_id' => 'required',
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 400);
        }

        $data = $validator->validated();
        $country = Country::create($data);

        return response()->json([
            'success'   => true,
            'message'   => "success",
            "data" => $country,
            'status_code' => 200
        ]);


    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        return response()->json([
            'success' => true,
            'message' => 'success',
            'data' => [
                'id' => $country->id,
                'name' => $country->name,
            ],
            'status_code' => 200
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Country $country)
    {
        $user = auth()->user()->id;
        $data = $request->all();
        $country->update($data);

        return response()->json([
            'success'   => true,
            'message'   => "success",
            'data' => $country,
            'status_code' => 200
        ]);



    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        $country->delete();
        return response()->json([
            'success' => true,
            'message' => 'Country deleted successfully',
            'status_code' => 200
        ]);
    }
}
