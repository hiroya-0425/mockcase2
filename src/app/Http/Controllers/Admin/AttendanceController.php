<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\AdminAttendanceUpdateRequest;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        // 表示日（指定が無ければ今日）
        $date = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();

        // その日の出勤者のみの勤怠（休憩も含めて）
        $attendances = Attendance::with('breaks', 'user')
            ->whereDate('work_date', $date->toDateString())
            ->whereNotNull('start_time')
            ->get();

        // 一覧行を作る
        $rows = $attendances->map(function ($a) {
            $start = $a && $a->start_time ? Carbon::parse($a->start_time) : null;
            $end   = $a && $a->end_time   ? Carbon::parse($a->end_time)   : null;

            // 休憩合計（存在しない/終了が無い区間は0扱い）
            $breakMinutes = 0;
            foreach ($a->breaks as $br) {
                if ($br->break_start && $br->break_end) {
                    $seconds = Carbon::parse($br->break_start)->diffInSeconds(Carbon::parse($br->break_end));
                    $breakMinutes += ceil($seconds / 60);
                }
            }

            // 実働合計 = 退勤-出勤-休憩, どれか欠けていたら空欄
            $totalMinutes = null;
            if ($start && $end) {
                $totalMinutes = ceil($end->diffInSeconds($start) / 60) - $breakMinutes;
                if ($totalMinutes < 0) $totalMinutes = 0;
            }

            $fmt = fn($dt) => $dt ? $dt->format('H:i') : '';
            $fmtMin = function ($mins) {
                if ($mins === null) return '';
                $h = intdiv($mins, 60);
                $m = $mins % 60;
                return sprintf('%d:%02d', $h, $m);
            };

            return [
                'user'        => $a->user,
                'attendance'  => $a,
                'start'       => $fmt($start),
                'end'         => $fmt($end),
                'break_total' => $fmtMin($breakMinutes),
                'work_total'  => $fmtMin($totalMinutes),
            ];
        });

        // 前日/翌日
        $prevDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();

        return view('admin.attendance.index', [
            'date'     => $date,
            'rows'     => $rows,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
        ]);
    }

    public function show(Attendance $attendance)
    {
        $attendance->load('user', 'breaks');

        // ★ breaks を Blade に渡す
        $breaks = $attendance->breaks;

        return view('admin.attendance.show', compact('attendance', 'breaks'));
    }

    public function update(AdminAttendanceUpdateRequest $request, Attendance $attendance)
    {
        $date = Carbon::parse($attendance->work_date)->format('Y-m-d');

        $toDateTime = function (?string $hhmm) use ($date): ?string {
            if (!$hhmm) return null;
            return Carbon::createFromFormat('Y-m-d H:i', "$date $hhmm")->format('Y-m-d H:i:s');
        };

        DB::transaction(function () use ($request, $attendance, $toDateTime) {
            // 出退勤 & 備考
            $attendance->start_time = $toDateTime($request->input('start_time'));
            $attendance->end_time   = $toDateTime($request->input('end_time'));
            $attendance->remarks    = $request->input('remarks');
            $attendance->save();

            // 休憩（既存更新 + 追加1行）
            foreach ($request->input('breaks', []) as $row) {
                $bid   = $row['break_time_id'] ?? null;
                $bFrom = $toDateTime($row['start'] ?? null);
                $bTo   = $toDateTime($row['end'] ?? null);

                if ($bid) {
                    // 既存休憩を更新
                    $attendance->breaks()
                        ->where('id', $bid)
                        ->update([
                            'break_start' => $bFrom,
                            'break_end'   => $bTo,
                        ]);
                } else {
                    // 新規行（両方空ならスキップ）
                    if ($bFrom || $bTo) {
                        $attendance->breaks()->create([
                            'break_start' => $bFrom,
                            'break_end'   => $bTo,
                        ]);
                    }
                }
            }
        });

        return redirect()
            ->route('admin.attendance.show', $attendance->id)
            ->with('status', '勤怠を修正しました。');
    }
}
