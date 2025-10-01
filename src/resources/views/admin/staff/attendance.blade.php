@extends('layouts.admin')

@section('title', $user->name . 'さんの勤怠')
<link rel="stylesheet" href="{{ asset('css/admin/staff/attendance.css') }}" />
@section('content')
<div class="staff-att__container">
    <h2 class="staff-att__title">{{ $user->name }}さんの勤怠</h2>
    {{-- 月切り替え --}}
    <div class="staff-att__month-nav">
        <a href="{{ route('admin.staff.attendance', ['user' => $user->id, 'month' => $currentMonth->copy()->subMonth()->format('Y-m')]) }}"
            class="staff-att__month-btn">← 前月</a>
        <span class="staff-att__month-display">
            🗓️{{ $currentMonth->format('Y年m月') }}
        </span>
        <a href="{{ route('admin.staff.attendance', ['user' => $user->id, 'month' => $currentMonth->copy()->addMonth()->format('Y-m')]) }}"
            class="staff-att__month-btn">翌月 →</a>
    </div>
    {{-- 勤怠一覧テーブル --}}
    <div class="staff-att__card">
        <table class="staff-att__table">
            <thead class="staff-att__thead">
                <tr class="staff-att__row">
                    <th class="staff-att__th">日付</th>
                    <th class="staff-att__th">出勤</th>
                    <th class="staff-att__th">退勤</th>
                    <th class="staff-att__th">休憩</th>
                    <th class="staff-att__th">合計</th>
                    <th class="staff-att__th staff-att__th-action">詳細</th>
                </tr>
            </thead>
            <tbody class="staff-att__tbody">
                @foreach($daysInMonth as $day)
                @php
                $attendance = $day['attendance'];
                $dateLabel = \Carbon\Carbon::parse($day['date'])->locale('ja')->isoFormat('MM/DD(ddd)');
                @endphp
                <tr class="staff-att__row">
                    <td class="staff-att__td">{{ $dateLabel }}</td>
                    <td class="staff-att__td">
                        {{ $attendance?->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '' }}
                    </td>
                    <td class="staff-att__td">
                        {{ $attendance?->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}
                    </td>
                    <td class="staff-att__td">
                        @if($attendance)
                        @php
                        // 打刻が存在するかどうか
                        $hasBreakRecords = $attendance->breaks->filter(function($break){
                        return $break->break_start && $break->break_end;
                        })->isNotEmpty();

                        $totalBreak = $attendance->breaks->reduce(function ($carry, $break) {
                        if ($break->break_start && $break->break_end) {
                        $seconds = \Carbon\Carbon::parse($break->break_start)
                        ->diffInSeconds(\Carbon\Carbon::parse($break->break_end));
                        return $carry + ceil($seconds / 60);
                        }
                        return $carry;
                        }, 0);
                        @endphp

                        {{-- 打刻があれば必ず表示。0分なら 0:00 --}}
                        @if($hasBreakRecords)
                        {{ sprintf('%d:%02d', floor($totalBreak/60), $totalBreak%60) }}
                        @endif
                        @endif
                    </td>
                    <td class="staff-att__td">
                        @if($attendance?->start_time && $attendance?->end_time)
                        @php
                        $workSeconds = \Carbon\Carbon::parse($attendance->start_time)
                        ->diffInSeconds(\Carbon\Carbon::parse($attendance->end_time));
                        $workMinutes = ceil($workSeconds / 60);

                        // 休憩は上の計算で $totalBreak に入っている想定
                        $realWork = max($workMinutes - ($totalBreak ?? 0), 0);
                        @endphp

                        {{ sprintf('%d:%02d', floor($realWork/60), $realWork%60) }}
                        @endif
                    </td>
                    <td class="staff-att__td staff-att__td-action">
                        @if($attendance)
                        <a href="{{ route('admin.attendance.show', $attendance->id) }}" class="staff-att__detail-link">詳細</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- CSV 出力 --}}
    <div class="staff-att__actions">
        <form method="GET" action="{{ route('admin.staff.attendance.csv', ['user' => $user->id, 'month' => $currentMonth->format('Y-m')]) }}">
            <button type="submit" class="staff-att__csv-btn">CSV出力</button>
        </form>
    </div>
</div>
@endsection