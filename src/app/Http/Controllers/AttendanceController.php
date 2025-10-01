<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\CorrectionRequest;

class AttendanceController extends Controller
{
    // 打刻画面
    public function create()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', today())
            ->latest()
            ->first();

        $onBreak = null;
        if ($attendance && !$attendance->end_time) {
            $onBreak = $attendance->breaks()
                ->whereNull('break_end')
                ->latest()
                ->first();
        }

        return view('user.attendance.create', compact('attendance', 'onBreak'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
    $created = false;
        switch ($request->input('action')) {
            case 'start': // 出勤
                Attendance::create([
                    'user_id'   => $user->id,
                    'work_date' => today(),
                    'start_time' => now(),
                    'status'    => 1,
                ]);
                break;

            case 'end': // 退勤
                $attendance = Attendance::where('user_id', $user->id)
                    ->whereDate('work_date', today())
                    ->whereNull('end_time')
                    ->latest()
                    ->first();
                if ($attendance) {
                    $attendance->update([
                        'end_time' => now(),
                        'status'   => 'finished', // 退勤済み
                    ]);
                }
                break;

            case 'break_in': // 休憩入り
                $attendance = Attendance::where('user_id', $user->id)
                    ->whereDate('work_date', today())
                    ->whereNull('end_time')
                    ->latest()
                    ->first();
                if ($attendance) {
                    $attendance->breaks()->create([
                        'break_start' => now(),
                    ]);
                }
                break;

            case 'break_out': // 休憩戻り
                $attendance = Attendance::where('user_id', $user->id)
                    ->whereDate('work_date', today())
                    ->whereNull('end_time')
                    ->latest()
                    ->first();
                if ($attendance) {
                    $break = $attendance->breaks()
                        ->whereNull('break_end')
                        ->latest()
                        ->first();
                    if ($break) {
                        $break->update([
                            'break_end' => now(),
                        ]);
                    }
                }
                break;
        }
        if ($created) {
            $attendance->status = 'corrected';
            $attendance->save();
        }
        return redirect()->route('attendance.create');
    }

    public function index(Request $request)
    {
        $user   = Auth::user();
        $month  = $request->input('month', now()->format('Y-m'));
        $start  = Carbon::parse($month . '-01')->startOfMonth();
        $end    = (clone $start)->endOfMonth();

        // 今月の出勤レコードをまとめて取得（休憩も一括で）
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->with('breaks')
            ->get()
            ->keyBy(fn($a) => Carbon::parse($a->work_date)->toDateString());

        // その月の全日リストを作成
        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            $days[] = [
                'date'       => $d->copy(),
                'attendance' => $attendances[$key] ?? null,
            ];
        }

        // 前後月（ナビ用）
        $prevMonth = $start->copy()->subMonth()->format('Y-m');
        $nextMonth = $start->copy()->addMonth()->format('Y-m');

        return view('user.attendance.index', compact('days', 'month', 'prevMonth', 'nextMonth'));
    }

    public function show($id)
    {
        $attendance = Attendance::with(['breaks'])->findOrFail($id);

        $pending = \App\Models\CorrectionRequest::where('attendance_id', $attendance->id)
            ->where('user_id', \Illuminate\Support\Facades\Auth::id())
            ->where('status', 'pending')
            ->get();

        // ===== 出退勤 =====
        $display = [
            'start_time' => $attendance->start_time,
            'end_time'   => $attendance->end_time,
        ];
        if ($shiftReq = $pending->firstWhere('break_time_id', null)) {
            if ($shiftReq->requested_start_time) {
                $display['start_time'] = $shiftReq->requested_start_time;
            }
            if ($shiftReq->requested_end_time) {
                $display['end_time'] = $shiftReq->requested_end_time;
            }
        }

        // ===== 休憩 =====
        $pendingBreaks = $pending->filter(fn($req) => !is_null($req->break_time_id))
            ->keyBy('break_time_id');

        $breaksForView = $attendance->breaks->map(function ($br) use ($pendingBreaks) {
            $row = $br->replicate();

            if ($req = $pendingBreaks->get($br->id)) {
                if ($req->requested_start_time) $row->break_start = $req->requested_start_time;
                if ($req->requested_end_time)   $row->break_end   = $req->requested_end_time;
            }
            return $row;
        });

        $newBreaks = $pending->filter(function ($req) use ($shiftReq) {
            return is_null($req->break_time_id)
                && $req->id !== optional($shiftReq)->id
                && ($req->requested_start_time || $req->requested_end_time);
        })->map(function ($req) {
            return (object)[
                'break_start' => $req->requested_start_time,
                'break_end'   => $req->requested_end_time,
            ];
        });

        $breaksForView = $breaksForView->concat($newBreaks)->sortBy('break_start')->values();

        return view('user.attendance.show', [
            'attendance' => $attendance,
            'display'    => $display,
            'breaks'     => $breaksForView,
            'pending'    => $pending,
        ]);
    }
}