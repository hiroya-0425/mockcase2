@extends('layouts.user')

@section('title', '勤怠詳細')
<link rel="stylesheet" href="{{ asset('css/user/attendance/show.css') }}" />

@section('content')
<div class="attendance__detail">
    <div class="attendance__detail-container">
        <h2 class="attendance__detail-title">勤怠詳細</h2>

        @if (session('status'))
        <p class="attendance__flash">{{ session('status') }}</p>
        @endif

        <div class="attendance__compare">
            {{-- 修正前 --}}
            <div class="attendance__compare-col">
                {{-- ★ form はカラム全体を囲む --}}
                <form method="POST" action="{{ route('attendance.requestCorrection', $attendance->id) }}" novalidate>
                    @csrf
                    <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">

                    @if ($attendance->status === 'corrected')
                    <h3 class="attendance__compare-title">修正前</h3>
                    @endif

                    <table class="attendance__detail-table">
                        <tr>
                            <th class="attendance__detail-th">名前</th>
                            <td class="attendance__detail-td">
                                <span class="attendance__name">{{ Auth::user()->name }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th class="attendance__detail-th">日付</th>
                            <td class="attendance__detail-td">
                                <span class="date-year">
                                    {{ \Carbon\Carbon::parse($attendance->work_date)->format('Y年') }}
                                </span>
                                {{ \Carbon\Carbon::parse($attendance->work_date)->format('n月j日') }}
                            </td>
                        </tr>
                        {{-- 出勤・退勤 --}}
                        <tr>
                            <th class="attendance__detail-th">出勤・退勤</th>
                            <td class="attendance__detail-td">
                                @if ($attendance->status === 'corrected')
                                {{ $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '-' }}
                                <span class="time-separator">〜</span>
                                {{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '-' }}
                                @else
                                <input type="text" name="requested_start_time"
                                    value="{{ old('requested_start_time', $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '') }}"
                                    placeholder="HH:MM" class="attendance__input">
                                <span class="time-separator">〜</span>
                                <input type="text" name="requested_end_time"
                                    value="{{ old('requested_end_time', $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '') }}"
                                    placeholder="HH:MM" class="attendance__input">

                                @error('requested_start_time')
                                <div class="attendance__error">{{ $message }}</div>
                                @enderror
                                @error('requested_end_time')
                                <div class="attendance__error">{{ $message }}</div>
                                @enderror
                                @endif
                            </td>
                        </tr>
                        {{-- 休憩（申請後は表示だけ / 申請前は入力フォーム） --}}
                        @php
                        if ($attendance->status !== 'corrected') {
                        $oldBreaks = old('breaks');
                        if (is_array($oldBreaks)) {
                        $rows = array_values($oldBreaks);
                        } else {
                        // ★ 申請前は DB の値をフォーム初期値に
                        $rows = $attendance->breaks->map(function ($b) {
                        return [
                        'break_time_id' => $b->id,
                        'requested_start_time' => $b->break_start ? \Carbon\Carbon::parse($b->break_start)->format('H:i') : '',
                        'requested_end_time' => $b->break_end ? \Carbon\Carbon::parse($b->break_end)->format('H:i') : '',
                        ];
                        })->values()->toArray();
                        }
                        $rows = array_values(array_filter($rows, function ($r) {
                        $s = trim($r['requested_start_time'] ?? '');
                        $e = trim($r['requested_end_time'] ?? '');
                        $id = $r['break_time_id'] ?? null;
                        return !($s === '' && $e === '' && empty($id));
                        }));
                        $rows[] = ['break_time_id' => null, 'requested_start_time' => '', 'requested_end_time' => ''];
                        }
                        @endphp

                        @if ($attendance->status === 'corrected')
                        {{-- ★ 修正前（DBの元値だけを表示） --}}
                        @forelse($attendance->breaks as $i => $break)
                        <tr>
                            <th class="attendance__detail-th">休憩{{ $i+1 }}</th>
                            <td class="attendance__detail-td">
                                {{ $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '-' }}
                                <span class="time-separator">〜</span>
                                {{ $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '-' }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <th class="attendance__detail-th">休憩1</th>
                            <td class="attendance__detail-td">-</td>
                        </tr>
                        @endforelse
                        @else
                        {{-- ★ 修正前（申請前はフォーム入力可能） --}}
                        @foreach($rows as $i => $row)
                        <tr>
                            <th class="attendance__detail-th">休憩{{ $i+1 }}</th>
                            <td class="attendance__detail-td">
                                <input type="text"
                                    name="breaks[{{ $i }}][requested_start_time]"
                                    value="{{ old("breaks.$i.requested_start_time", $row['requested_start_time'] ?? '') }}"
                                    placeholder="HH:MM"
                                    class="attendance__input">
                                <span class="time-separator">〜</span>
                                <input type="text"
                                    name="breaks[{{ $i }}][requested_end_time]"
                                    value="{{ old("breaks.$i.requested_end_time", $row['requested_end_time'] ?? '') }}"
                                    placeholder="HH:MM"
                                    class="attendance__input">

                                @if(!empty($row['break_time_id']))
                                <input type="hidden" name="breaks[{{ $i }}][break_time_id]" value="{{ $row['break_time_id'] }}">
                                @endif

                                @error("breaks.$i.requested_start_time")
                                <div class="attendance__error">{{ $message }}</div>
                                @enderror
                                @error("breaks.$i.requested_end_time")
                                <div class="attendance__error">{{ $message }}</div>
                                @enderror
                            </td>
                        </tr>
                        @endforeach
                        @endif

                        {{-- 備考 --}}
                        <tr class="attendance__detail-row--remarks">
                            <th class="attendance__detail-th">備考</th>
                            <td class="attendance__detail-td">
                                @if ($attendance->status === 'corrected')
                                {{ $attendance->remarks ?? '-' }}
                                @else
                                <textarea name="remarks" rows="2" class="attendance__textarea">{{ old('remarks', $attendance->remarks) }}</textarea>
                                @error('remarks')
                                <div class="attendance__error">{{ $message }}</div>
                                @enderror
                                @endif
                            </td>
                        </tr>
                    </table>

                    @if ($attendance->status !== 'corrected')
                    <div class="attendance__detail-actions">
                        <button type="submit" class="attendance__detail-btn">修正</button>
                    </div>
                    @endif
                </form>
            </div>
        </div>

        {{-- 修正後 --}}
        <div class="attendance__compare-col">
            @if ($attendance->status === 'corrected')
            <h3 class="attendance__compare-title">修正後（申請内容）</h3>
            <table class="attendance__detail-table">
                {{-- 出勤・退勤 --}}
                <tr>
                    <th class="attendance__detail-th">出勤・退勤</th>
                    <td class="attendance__detail-td">
                        {{ $display['start_time'] ? \Carbon\Carbon::parse($display['start_time'])->format('H:i') : '-' }}
                        <span class="time-separator">〜</span>
                        {{ $display['end_time'] ? \Carbon\Carbon::parse($display['end_time'])->format('H:i') : '-' }}
                    </td>
                </tr>
                {{-- 休憩 --}}
                @foreach($breaks as $i => $break)
                <tr>
                    <th class="attendance__detail-th">休憩{{ $i+1 }}</th>
                    <td class="attendance__detail-td">
                        {{ $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '-' }}
                        <span class="time-separator">〜</span>
                        {{ $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '-' }}
                    </td>
                </tr>
                @endforeach
                {{-- 備考 --}}
                <tr class="attendance__detail-row--remarks">
                    <th class="attendance__detail-th">備考</th>
                    <td class="attendance__detail-td">
                        {{ optional($pending->first())->remarks ?? '-' }}
                    </td>
                </tr>
            </table>
            <p class="attendance__detail-note">※承認待ちのため修正できません。</p>
            @endif
        </div>
    </div>
</div>
@endsection