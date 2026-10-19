<?php

namespace App\Http\Controllers;

use App\Models\Price;
use App\Models\RoomType;
use App\Models\SeatType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PriceController extends Controller
{
    // Nhóm ngày: key dùng trong tên biến => giá trị cột `day` trong DB
    private const DAYS = [
        '2345' => 'Monday, Tuesday, Wednesday, Thursday',
        '67cn' => 'Friday, Saturday, Sunday',
    ];

    // Đối tượng khách: hssv, nl, nctte, vtt
    private const GENERATIONS = ['hssv', 'nl', 'nctte', 'vtt'];

    // Khung giờ: hậu tố tên biến => giá trị cột `after`
    private const TIMES = [
        't17' => '08:00', // trước 17h
        's17' => '17:00', // sau 17h
    ];

    /**
     * Danh sách 16 key giá, ví dụ:
     * 'hssv2345t17' => ['day' => 'Monday, ...', 'generation' => 'hssv', 'after' => '08:00']
     */
    private function priceKeys(): array
    {
        $keys = [];
        foreach (self::DAYS as $dayKey => $day) {
            foreach (self::GENERATIONS as $generation) {
                foreach (self::TIMES as $timeKey => $after) {
                    $keys[$generation . $dayKey . $timeKey] = [
                        'day'        => $day,
                        'generation' => $generation,
                        'after'      => $after,
                    ];
                }
            }
        }
        return $keys;
    }

    /**
     * Lấy toàn bộ giá bằng 1 query, trả về mảng [key => Price|null]
     */
    private function loadPrices(): array
    {
        $rows = Price::whereIn('day', array_values(self::DAYS))
            ->whereIn('generation', self::GENERATIONS)
            ->get();

        // Map theo (day|generation|after). Cắt 'after' về HH:MM phòng khi DB lưu dạng 08:00:00
        $map = [];
        foreach ($rows as $row) {
            $id = $row->day . '|' . $row->generation . '|' . substr((string) $row->after, 0, 5);
            $map[$id] = $map[$id] ?? $row; // giữ dòng đầu tiên nếu bị trùng
        }

        $result = [];
        foreach ($this->priceKeys() as $key => $cond) {
            $id = $cond['day'] . '|' . $cond['generation'] . '|' . $cond['after'];
            $result[$key] = $map[$id] ?? null;
        }
        return $result;
    }

    /**
     * Lấy phụ phí từ request.
     * Ưu tiên dạng mới: name="room_surcharge[{id}]" / name="seat_surcharge[{id}]"
     * Fallback dạng cũ: name="{tên}" (PHP đổi dấu cách và dấu chấm thành "_")
     * Trả về null nếu form không gửi ô đó => KHÔNG cập nhật
     */
    private function surchargeInput(Request $request, string $group, $model)
    {
        $byId = $request->input($group . '.' . $model->id);
        if ($byId !== null && $byId !== '') {
            return $byId;
        }

        $all = $request->all();
        foreach ([$model->name, str_replace([' ', '.'], '_', $model->name)] as $key) {
            if (array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== '' && !is_array($all[$key])) {
                return $all[$key];
            }
        }

        return null;
    }

    /* ==================== HIỂN THỊ ==================== */

    public function price()
    {
        $roomTypes = RoomType::where('name', '!=', '2D')->get();
        $seatTypes = SeatType::where('name', '!=', 'standard')->get();

        $data = [
            'roomTypes' => $roomTypes,
            'seatTypes' => $seatTypes,
        ];

        // Thiếu bản ghi thì hiển thị 0 thay vì crash
        foreach ($this->loadPrices() as $key => $price) {
            $data[$key] = $price ? $price->price : 0;
        }

        return view('admin.prices.list', $data);
    }

    /* ==================== LƯU ==================== */

    public function edit(Request $request)
    {
        // Validate 16 ô giá
        $rules = [];
        $messages = [];
        foreach (array_keys($this->priceKeys()) as $key) {
            $rules[$key] = 'required|numeric|min:0';
            $messages[$key . '.required'] = 'Vui lòng nhập đầy đủ giá vé';
            $messages[$key . '.numeric']  = 'Giá vé phải là số';
            $messages[$key . '.min']      = 'Giá vé không được âm';
        }
        $rules['room_surcharge.*'] = 'nullable|numeric|min:0';
        $rules['seat_surcharge.*'] = 'nullable|numeric|min:0';
        $messages['room_surcharge.*.numeric'] = 'Phụ phí phòng phải là số';
        $messages['room_surcharge.*.min']     = 'Phụ phí phòng không được âm';
        $messages['seat_surcharge.*.numeric'] = 'Phụ phí ghế phải là số';
        $messages['seat_surcharge.*.min']     = 'Phụ phí ghế không được âm';

        $request->validate($rules, $messages);

        // Kiểm tra phụ phí gửi theo kiểu cũ (theo tên)
        $roomTypes = RoomType::all();
        $seatTypes = SeatType::all();

        $roomUpdates = [];
        foreach ($roomTypes as $roomType) {
            $value = $this->surchargeInput($request, 'room_surcharge', $roomType);
            if ($value === null) {
                continue; // không có trong form (vd: 2D) => giữ nguyên
            }
            if (!is_numeric($value) || $value < 0) {
                return redirect()->back()->withInput()
                    ->with('danger', 'Phụ phí phòng "' . $roomType->name . '" không hợp lệ');
            }
            $roomUpdates[] = [$roomType, $value];
        }

        $seatUpdates = [];
        foreach ($seatTypes as $seatType) {
            $value = $this->surchargeInput($request, 'seat_surcharge', $seatType);
            if ($value === null) {
                continue; // không có trong form (vd: standard) => giữ nguyên
            }
            if (!is_numeric($value) || $value < 0) {
                return redirect()->back()->withInput()
                    ->with('danger', 'Phụ phí ghế "' . $seatType->name . '" không hợp lệ');
            }
            $seatUpdates[] = [$seatType, $value];
        }

        $priceKeys = $this->priceKeys();
        $prices = $this->loadPrices();

        DB::transaction(function () use ($request, $priceKeys, $prices, $roomUpdates, $seatUpdates) {
            // Lưu giá vé, thiếu bản ghi thì tạo mới
            foreach ($priceKeys as $key => $cond) {
                $price = $prices[$key];
                if (!$price) {
                    $price = new Price();
                    $price->day = $cond['day'];
                    $price->generation = $cond['generation'];
                    $price->after = $cond['after'];
                }
                $price->price = $request->input($key);
                $price->save();
            }

            // Lưu phụ phí phòng
            foreach ($roomUpdates as [$roomType, $value]) {
                $roomType->surcharge = $value;
                $roomType->save();
            }

            // Lưu phụ phí ghế
            foreach ($seatUpdates as [$seatType, $value]) {
                $seatType->surcharge = $value;
                $seatType->save();
            }
        });

        return redirect('admin/prices')->with('success', 'Saved!');
    }
}