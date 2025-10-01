@extends('layouts.user')

@section('title', '出勤登録')
<link rel="stylesheet" href="{{ asset('css/user/attendance/create.css') }}" />
@section('content')
<div class="attendance">
    <section class="attendance__panel">
        <div class="attendance__panel-inner">
            {{-- 勤務状態のチップ --}}
            @if ($attendance)
            @if ($attendance->end_time)
            <span class="attendance__status-chip attendance__status-chip-out">退勤済</span>
            @elseif ($onBreak)
            <span class="attendance__status-chip attendance__status-chip-break">休憩中</span>
            @else
            <span class="attendance__status-chip">勤務中</span>
            @endif
            @else
            <span class="attendance__status-chip attendance__status-chip-out">勤務外</span>
            @endif

            <p class="attendance__date">{{ now()->locale('ja')->isoFormat('YYYY年M月D日(ddd)') }}</p>
            <p class="attendance__clock" id="attendanceClock">{{ now()->format('H:i') }}</p>

            {{-- ボタン表示を出勤/退勤で切り替え --}}
            <form method="POST" action="{{ route('attendance.store') }}" class="attendance__actions">
                @csrf

                @if (!$attendance)
                {{-- 出勤前 --}}
                <button type="submit" name="action" value="start"
                    class="attendance__actions-btn attendance__actions-btn-primary">
                    出勤
                </button>
                @elseif ($attendance->end_time)
                {{-- 退勤済 --}}
                <p class="attendance__message">お疲れさまでした。</p>
                @elseif ($onBreak)
                {{-- 休憩中 --}}
                <button type="submit" name="action" value="break_out"
                    class="attendance__actions-btn attendance__actions-btn-secondary">
                    休憩戻
                </button>
                @else
                {{-- 勤務中 --}}
                <button type="submit" name="action" value="end"
                    class="attendance__actions-btn attendance__actions-btn-primary">
                    退勤
                </button>
                <button type="submit" name="action" value="break_in"
                    class="attendance__actions-btn attendance__actions-btn-secondary">
                    休憩入り
                </button>
                @endif
            </form>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (function() {
        const el = document.getElementById('attendanceClock');
        if (!el) return;

        // 表示を更新する関数（HH:mm）
        const render = () => {
            const d = new Date();
            const hh = String(d.getHours()).padStart(2, '0');
            const mm = String(d.getMinutes()).padStart(2, '0');
            el.textContent = `${hh}:${mm}`;
        };

        // ① すぐ一回描画
        render();

        // ② 次の「分の切り替わり」まで待ってから、以降は毎分更新
        const now = new Date();
        const msToNextMinute = (60 - now.getSeconds()) * 1000 - now.getMilliseconds();

        setTimeout(() => {
            render(); // ちょうど分が変わったタイミングで描画
            setInterval(render, 60 * 1000); // 以降は毎分更新
        }, msToNextMinute);
    })();
</script>
@endpush