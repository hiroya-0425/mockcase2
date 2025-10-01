<?php

namespace App\Http\Controllers;

use App\Http\Requests\CorrectionRequestStoreRequest;
use App\Models\CorrectionRequest;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CorrectionRequestController extends Controller
{
    public function store(CorrectionRequestStoreRequest $request, Attendance $attendance)
    {
        $submissionId = (string) Str::uuid();

        $toDateTime = function (?string $hhmm) use ($attendance): ?string {
            if (!$hhmm) return null;
            if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/', $hhmm)) return null;
            $date = \Carbon\Carbon::parse($attendance->work_date)->format('Y-m-d');
            return \Carbon\Carbon::createFromFormat('Y-m-d H:i', "$date $hhmm")->format('Y-m-d H:i:s');
        };

        $fmtHi = function ($dt) {
            return $dt ? \Carbon\Carbon::parse($dt)->format('H:i') : null;
        };

        $dbStartHi = $fmtHi($attendance->start_time);
        $dbEndHi   = $fmtHi($attendance->end_time);

        $inStartHi = trim($request->input('requested_start_time') ?? '');
        $inEndHi   = trim($request->input('requested_end_time') ?? '');

        $createdCount = 0;

        // --- 休憩（入力がある行だけ） ---
        $breakRows = collect($request->input('breaks', []))
            ->filter(function ($row) {
                $s = trim($row['requested_start_time'] ?? '');
                $e = trim($row['requested_end_time'] ?? '');
                return $s !== '' || $e !== '';
            });

        foreach ($breakRows as $row) {
            $id = $row['break_time_id'] ?? null;
            $newS = trim($row['requested_start_time'] ?? '');
            $newE = trim($row['requested_end_time'] ?? '');

            $sameAsDb = false;
            if ($id) {
                $orig = $attendance->breaks->firstWhere('id', $id);
                if ($orig) {
                    $dbBs = $fmtHi($orig->break_start);
                    $dbBe = $fmtHi($orig->break_end);
                    $sameAsDb = (($newS ?: null) === ($dbBs ?: null)) && (($newE ?: null) === ($dbBe ?: null));
                }
            }

            if ($sameAsDb) {
                // DBと同じなら申請を作らない
                continue;
            }

            CorrectionRequest::create([
                'submission_id'        => $submissionId,
                'user_id'              => auth()->id(),
                'attendance_id'        => $attendance->id,
                'break_time_id'        => $id ?: null,
                'requested_start_time' => $toDateTime($newS ?: null),
                'requested_end_time'   => $toDateTime($newE ?: null),
                'reason'               => '',
                'remarks'              => $request->input('remarks'),
                'status'               => 'pending',
                'admin_id'             => null,
            ]);
            $createdCount++;
        }

        // --- 出退勤（時刻が入力され、DBと違うときだけ） ---
        $shiftChanged =
            ($inStartHi !== '' && $inStartHi !== ($dbStartHi ?? '')) ||
            ($inEndHi   !== '' && $inEndHi   !== ($dbEndHi   ?? ''));

        if ($shiftChanged) {
            CorrectionRequest::create([
                'submission_id'        => $submissionId,
                'user_id'              => auth()->id(),
                'attendance_id'        => $attendance->id,
                'break_time_id'        => null,
                'requested_start_time' => $toDateTime($inStartHi ?: null),
                'requested_end_time'   => $toDateTime($inEndHi   ?: null),
                'reason'               => '',
                'remarks'              => $request->input('remarks'),
                'status'               => 'pending',
                'admin_id'             => null,
            ]);
            $createdCount++;
        } elseif ($createdCount === 0) {
            return back()
                ->withErrors(['remarks' => '修正内容がありません。いずれかの項目を入力してください。'])
                ->withInput();
        }

        // 1件でも作成したら勤怠を申請中に
        if ($createdCount > 0) {
            $attendance->status = 'corrected';
            $attendance->save();
        }

        return redirect()
            ->route('attendance.show', $attendance->id)
            ->with('status', '修正申請を送信しました。承認をお待ちください。');
    }
}
