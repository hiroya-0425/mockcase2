<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    /**
     * スタッフ月次勤怠一覧表示
     */
    public function index(User $user, Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $currentMonth = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        // その月の勤怠を取得（関連モデルでまとめて）
        $attendances = $user->attendances()
            ->with('breaks')
            ->whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->get()
            ->keyBy(function ($att) {
                return \Carbon\Carbon::parse($att->work_date)->format('Y-m-d');
            });

        // 月の日付リストを生成
        $daysInMonth = collect();
        for ($d = 0; $d < $currentMonth->daysInMonth; $d++) {
            $date = $currentMonth->copy()->addDays($d)->format('Y-m-d');
            $daysInMonth->push([
                'date' => $date,
                'attendance' => $attendances->get($date),
            ]);
        }

        return view('admin.staff.attendance', [
            'user' => $user,
            'currentMonth' => $currentMonth,
            'daysInMonth' => $daysInMonth,
        ]);
    }

    /**
     * CSV 出力
     */
    public function exportCsv(Request $request, User $user)
    {
        $monthParam = $request->query('month');
        $currentMonth = $monthParam
            ? Carbon::createFromFormat('Y-m', $monthParam)
            : Carbon::now()->startOfMonth();

        $start = $currentMonth->copy()->startOfMonth();
        $end   = $currentMonth->copy()->endOfMonth();

        $attendances = $user->attendances()
            ->with('breaks')
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('work_date')
            ->get();


        $filename = $user->name . '_' . $currentMonth->format('Y_m') . '_attendances.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($attendances) {
            $handle = fopen('php://output', 'w');

            // ✅ Excel文字化け防止
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // ヘッダー行
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩時間', '勤務時間']);

            foreach ($attendances as $attendance) {
                $breakMinutes = $attendance->breaks->reduce(function ($carry, $break) {
                    if ($break->break_start && $break->break_end) {
                        return $carry + \Carbon\Carbon::parse($break->break_start)
                            ->diffInMinutes(\Carbon\Carbon::parse($break->break_end));
                    }
                    return $carry;
                }, 0);

                $workMinutes = null;
                if ($attendance->start_time && $attendance->end_time) {
                    $workMinutes = \Carbon\Carbon::parse($attendance->start_time)
                        ->diffInMinutes(\Carbon\Carbon::parse($attendance->end_time)) - $breakMinutes;
                    if ($workMinutes < 0) $workMinutes = 0;
                }

                $breakFormatted = sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60);
                $workFormatted = $workMinutes !== null
                    ? sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60)
                    : '';

                fputcsv($handle, [
                    '="' . $attendance->work_date . '"',
                    $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '',
                    $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '',
                    $breakFormatted,
                    $workFormatted,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

