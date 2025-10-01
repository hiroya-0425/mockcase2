<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use App\Models\Attendance;

class AdminAttendanceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 管理者で通過前提
    }

    public function rules(): array
    {
        return [
            // ← 出勤・退勤は regex を外す（ここでは形式チェックしない）
            'start_time' => ['nullable'],
            'end_time'   => ['nullable'],
            'remarks'    => ['required', 'string', 'max:2000'],

            // 休憩は従来どおり個別に regex を掛ける（行ごとに出したいはずなので）
            'breaks'                 => ['array'],
            'breaks.*.break_time_id' => ['nullable', 'exists:break_times,id'],
            'breaks.*.start'         => ['nullable', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],
            'breaks.*.end'           => ['nullable', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],
        ];
    }

    public function messages(): array
    {
        return [
            // 出勤退勤の形式エラー文言は after で1回だけ出すのでここは不要
            'breaks.*.start.regex' => '休憩時間が不適切な値です',
            'breaks.*.end.regex'   => '休憩時間が不適切な値です',
            'remarks.required'     => '備考を記入してください',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            /** @var Attendance $attendance */
            $attendance = $this->route('attendance');
            if (!$attendance) return;

            $date = Carbon::parse($attendance->work_date)->format('Y-m-d');

            $toCarbon = function (?string $hhmm) use ($date): ?Carbon {
                if (!$hhmm) return null;
                if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $hhmm)) return null;
                return Carbon::createFromFormat('Y-m-d H:i', "$date $hhmm");
            };

            // 入力値（あれば入力を優先して勤務範囲に採用、なければDB値）
            $inputStart = $toCarbon($this->input('start_time'));
            $inputEnd   = $toCarbon($this->input('end_time'));

            $dbStart = $attendance->start_time ? Carbon::parse($attendance->start_time) : null;
            $dbEnd   = $attendance->end_time   ? Carbon::parse($attendance->end_time)   : null;

            $workStart = $inputStart ?: $dbStart;
            $workEnd   = $inputEnd   ?: $dbEnd;

            // 1) 出退勤の前後関係（同時刻はOKにする => gt のみNG）
            if ($inputStart && $inputEnd && $inputStart->gt($inputEnd)) {
                $v->errors()->add('end_time', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // 2) 休憩のチェック
            $haveWorkRange = $workStart && $workEnd;

            foreach ($this->input('breaks', []) as $i => $row) {
                $bs = $toCarbon($row['start'] ?? null);
                $be = $toCarbon($row['end'] ?? null);

                // 勤務時間外（開始または終了のどちらかが外れていたら1件だけ出す）
                if ($haveWorkRange) {
                    $outOfRange =
                        ($bs && ($bs->lt($workStart) || $bs->gt($workEnd))) ||
                        ($be && ($be->lt($workStart) || $be->gt($workEnd)));

                    if ($outOfRange) {
                        $v->errors()->add("breaks.$i.start", '休憩時間が勤務時間外です。');
                        continue; // この行の他の判定はスキップ
                    }
                }

                // 休憩の内部整合（同時刻はOKにする => gt のみNG）
                if ($bs && $be && $bs->gt($be)) {
                    $v->errors()->add("breaks.$i.end", '休憩時間が不適切な値です');
                }
            }
        });
    }
}