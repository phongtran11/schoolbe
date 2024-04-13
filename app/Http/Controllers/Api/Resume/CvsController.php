<?php

namespace App\Http\Controllers\Api\Resume;

use App\Http\Controllers\Controller;
use App\Models\Cv;
use Illuminate\Http\Request;

class CvsController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf', // Giả sử chỉ chấp nhận file PDF
        ]);

        if ($request->hasFile('cv')) {
            $file = $request->file('cv');
            $path = $file->store('public/cvs');

            $cv = new CV();
            $cv->users_id  = auth()->id();
            $cv->file_path = $path;
            $cv->save();

            return response()->json(['message' => 'CV đã được tải lên.'], 200);
        }

        return response()->json(['error' => 'Không tìm thấy tệp CV.'], 400);
    }


    public function setDefault(CV $cv)
    {
        if ($cv->users_id !== auth()->id()) {
            abort(403);
        }

        // Đặt tất cả CV khác của người dùng này thành không phải mặc định
        CV::where('users_id', auth()->id())->update(['is_default' => false]);

        // Đặt CV này là mặc định
        $cv->is_default = true;
        $cv->save();

        return back()->with('success', 'CV đã được đặt làm mặc định.');
    }

    public function getDefaultCv(Request $request)
    {
        $user = auth()->user();
        $defaultCv = $user->cvs()->where('is_default', true)->first();

        if ($defaultCv) {
            return response()->json([
                'success' => true,
                'defaultCv' => $defaultCv
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy CV mặc định.'
            ], 404);
        }
    }

    // Lấy danh sách tất cả CV của người dùng hiện tại
    public function index()
    {
        $cvs = auth()->user()->cvs;
        return response()->json($cvs, 200);
    }

// Lấy thông tin chi tiết của một CV
    public function show(CV $cv)
    {
        if ($cv->users_id !== auth()->id()) {
            abort(403);
        }

        return response()->json($cv, 200);
    }

    // Cập nhật thông tin của CV
    public function update(Request $request, CV $cv)
    {
        if ($cv->users_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            // Các quy tắc xác thực cho các trường cần cập nhật
        ]);

        $cv->update($request->all());

        return response()->json(['message' => 'CV đã được cập nhật.'], 200);
    }

    // Xóa CV
    public function destroy(CV $cv)
    {
        if ($cv->users_id !== auth()->id()) {
            abort(403);
        }

        $cv->delete();

        return response()->json(['message' => 'CV đã được xóa.'], 200);
    }

}
