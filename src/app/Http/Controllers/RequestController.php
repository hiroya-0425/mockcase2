<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CorrectionRequest;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        // 全部まとめて取得
        $all = \App\Models\CorrectionRequest::with('attendance')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        // submission_id ごとにグループ化
        $grouped = $all->groupBy('submission_id')->map(function ($rows) {
            $status = collect(['pending', 'rejected', 'approved'])
                ->first(fn($s) => $rows->contains('status', $s)) ?? 'pending';

            return (object) [
                'submission_id'  => $rows->first()->submission_id,
                'attendance'     => $rows->first()->attendance,
                'requests'       => $rows,
                'request_count'  => $rows->count(),
                'display_status' => $status,
                'latest_at'      => $rows->max('updated_at'),
                'remarks'        => $rows->first()->remarks,
                'reason'         => $rows->first()->reason,
            ];
        });

        // タブごとにフィルタ
        $pendingRequests  = $grouped->filter(fn($g) => $g->display_status === 'pending')
            ->sortByDesc('latest_at')->values();
        $approvedRequests = $grouped->filter(fn($g) => $g->display_status === 'approved')
            ->sortByDesc('latest_at')->values();

        $activeTab = $request->get('tab', 'pending');
        return view('user.requests.index', compact('pendingRequests', 'approvedRequests', 'activeTab'));
    }
}
