<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;
use App\Models\Attendance;

class CorrectionRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_id'  => ['required', 'exists:attendances,id'],
            'break_time_id'  => ['nullable', 'exists:break_times,id'],

            'requested_start_time' => ['nullable', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],
            'requested_end_time'   => ['nullable', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],

            'breaks'                        => ['array'],
            'breaks.*.break_time_id'        => ['nullable', 'exists:break_times,id'],
            'breaks.*.requested_start_time' => ['nullable', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],
            'breaks.*.requested_end_time'   => ['nullable', 'regex:/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/'],

            'remarks' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            // 出退勤
            'requested_start_time.regex' => '出勤時間もしくは退勤時間が不適切な値です',
            'requested_end_time.regex'   => '出勤時間もしくは退勤時間が不適切な値です',
            // 休憩
            'breaks.*.requested_start_time.regex' => '休憩時間が不適切な値です',
            // 休憩
            'breaks.*.requested_end_time.regex'   => '休憩時間もしくは退勤時間が不適切な値です',
            // 備考
            'remarks.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $attendance = \App\Models\Attendance::find($this->input('attendance_id'));
            if (!$attendance) return;

            $date = \Carbon\Carbon::parse($attendance->work_date)->format('Y-m-d');

            $toDateTime = function (?string $hhmm) use ($date): ?\Carbon\Carbon {
                if (!$hhmm) return null;
                if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $hhmm)) {
                    return null;
                }
                return \Carbon\Carbon::createFromFormat('Y-m-d H:i', $date . ' ' . $hhmm);
            };

            $start = $toDateTime($this->input('requested_start_time'));
            $end   = $toDateTime($this->input('requested_end_time'));

            // 1) 出退勤の前後
            if ($start && $end && $start->gte($end)) {
                $v->errors()->add('requested_end_time', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // 2)・3) 休憩
            $breaks = $this->input('breaks', []);
            foreach ($breaks as $i => $b) {
                $bs = $toDateTime($b['requested_start_time'] ?? null);
                $be = $toDateTime($b['requested_end_time'] ?? null);

                // 2) 休憩開始が出勤より前 or 退勤より後
                if ($bs) {
                    if (($start && $bs->lt($start)) || ($end && $bs->gt($end))) {
                        $v->errors()->add("breaks.$i.requested_start_time", '休憩時間が不適切な値です');
                    }
                }

                // 3) 休憩終了が退勤より後
                if ($be && $end && $be->gt($end)) {
                    $v->errors()->add("breaks.$i.requested_end_time", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }
}
