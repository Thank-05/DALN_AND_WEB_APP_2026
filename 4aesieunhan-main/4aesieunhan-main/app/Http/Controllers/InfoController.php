<?php

namespace App\Http\Controllers;

use App\Models\Info;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InfoController extends Controller
{
    /**
     * Thư mục lưu logo (tính từ thư mục public).
     */
    private const LOGO_DIR = 'images/favicon';

    public function info()
    {
        // Tránh lỗi null khi bảng chưa có bản ghi id = 1
        $info = Info::firstOrCreate(['id' => 1]);

        return view('admin.info', [
            'info' => $info,
        ]);
    }

    public function postInfo(Request $request)
    {
        // Kiểm tra dữ liệu đầu vào (mimes kiểm tra nội dung file thật, không chỉ đuôi file)
        $validated = $request->validate([
            'Image'     => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'name'      => 'nullable|string|max:255',
            'phone'     => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:255',
            'facebook'  => 'nullable|string|max:255',
            'twitter'   => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'youtube'   => 'nullable|string|max:255',
            'worktime'  => 'nullable|string|max:255',
            'copyright' => 'nullable|string|max:255',
        ], [
            'Image.image' => 'File tải lên phải là hình ảnh.',
            'Image.mimes' => 'Chỉ hỗ trợ ảnh jpg, jpeg, png.',
            'Image.max'   => 'Ảnh không được vượt quá 2MB.',
        ]);

        $info = Info::firstOrCreate(['id' => 1]);

        // Chỉ lấy các trường văn bản, bỏ file Image và _token
        $data = collect($validated)->except('Image')->toArray();

        if ($request->hasFile('Image')) {
            $file = $request->file('Image');
            $dir  = public_path(self::LOGO_DIR);

            File::ensureDirectoryExists($dir);

            // Tên file duy nhất, giữ đúng đuôi -> tránh cache trình duyệt và sai định dạng
            $fileName = 'logo_' . time() . '_' . Str::random(6) . '.' . strtolower($file->getClientOriginalExtension());
            $file->move($dir, $fileName);

            // Chỉ xoá logo cũ sau khi upload ảnh mới thành công, và chỉ khi file tồn tại
            if (!empty($info->logo)) {
                $oldPath = $dir . DIRECTORY_SEPARATOR . $info->logo;
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
            }

            $data['logo'] = $fileName;
        }

        $info->update($data);

        return redirect('admin/info')->with('success', 'Cập nhật thành công');
    }
}