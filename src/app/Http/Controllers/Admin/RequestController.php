<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->query('tab') === 'approved' ? 'approved' : 'pending';

        // 全申請を取得（ユーザー・勤怠付き）
        $all = CorrectionRequest::with(['attendance', 'user'])
            ->orderByDesc('created_at')
            ->get();

        $grouped = $all->groupBy('submission_id')->map(function ($rows) {
            $status = collect(['pending', 'rejected', 'approved'])
                ->first(fn($s) => $rows->contains('status', $s)) ?? 'pending';

            return (object)[
                'submission_id'  => $rows->first()->submission_id,
                'user'           => $rows->first()->user,
                'attendance'     => $rows->first()->attendance,
                'requests'       => $rows,
                'request_count'  => $rows->count(),
                'display_status' => $status,
                'latest_at'      => $rows->max('updated_at'),
                'remarks'        => $rows->first()->remarks,
                'reason'         => $rows->first()->reason,
            ];
        });

        $pending = $grouped->filter(fn($g) => $g->display_status === 'pending')
            ->sortByDesc('latest_at')->values();
        $approved = $grouped->filter(fn($g) => $g->display_status === 'approved')
            ->sortByDesc('latest_at')->values();

        return view('admin.requests.index', [
            'activeTab'        => $activeTab,
            'pendingRequests'  => $pending,
            'approvedRequests' => $approved,
        ]);
    }

    public function show(string $submissionId)
    {
        $requests = CorrectionRequest::with(['attendance.breaks', 'user'])
            ->where('submission_id', $submissionId)
            ->get();

        if ($requests->isEmpty()) {
            abort(404);
        }

        return view('admin.requests.approve', [
            'requests' => $requests,
            'group'    => (object)[
                'submission_id' => $submissionId,
                'user'          => $requests->first()->user,
                'attendance'    => $requests->first()->attendance,
                'status'        => $requests->first()->status,
            ],
        ]);
    }

    // 承認ボタン
    public function approve(string $submissionId)
    {
        $requests = CorrectionRequest::with(['attendance', 'breakTime'])
            ->where('submission_id', $submissionId)
            ->get();

        if ($requests->isEmpty()) {
            return back()->with('status', '申請が見つかりません。');
        }

        return DB::transaction(function () use ($requests, $submissionId) {
            foreach ($requests as $correctionRequest) {
                // --- 修正申請を承認 ---
                $correctionRequest->status   = 'approved';
                $correctionRequest->admin_id = Auth::guard('admin')->id() ?? 1;
                $correctionRequest->save();

                $attendance = $correctionRequest->attendance;

                // --- 出退勤修正 ---
                if ($correctionRequest->break_time_id === null) {
                    if ($correctionRequest->requested_start_time) {
                        $attendance->start_time = $correctionRequest->requested_start_time;
                    }
                    if ($correctionRequest->requested_end_time) {
                        $attendance->end_time = $correctionRequest->requested_end_time;
                    }
                    if ($correctionRequest->remarks) {
                        $attendance->remarks = $correctionRequest->remarks;
                    }
                    $attendance->status = 'corrected';
                    $attendance->save();
                }

                // --- 休憩修正 ---
                if ($correctionRequest->break_time_id) {
                    $break = $correctionRequest->breakTime;
                    if ($break) {
                        if ($correctionRequest->requested_start_time) {
                            $break->break_start = $correctionRequest->requested_start_time;
                        }
                        if ($correctionRequest->requested_end_time) {
                            $break->break_end = $correctionRequest->requested_end_time;
                        }
                        $break->save();
                    } else {
                        $attendance->breaks()->create([
                            'break_start' => $correctionRequest->requested_start_time,
                            'break_end'   => $correctionRequest->requested_end_time,
                        ]);
                    }
                }
            }

            return redirect()
                ->route('admin.requests.index')
                ->with('status', '承認しました。勤怠に反映しました。');
        });
    }
}