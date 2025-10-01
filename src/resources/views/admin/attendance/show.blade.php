@extends('layouts.admin')

@section('title', '勤怠詳細（管理者）')
<link rel="stylesheet" href="{{ asset('css/admin/attendance/show.css') }}" />
@section('content')
<div class="admin-detail__container">
    <h2 class="admin-detail__title">勤怠詳細</h2>

    @if (session('status'))
    <p class="admin-detail__flash">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('admin.attendance.update', $attendance->id) }}" class="admin-detail__form" novalidate>
        @csrf
        @method('PATCH')
        <div class="admin-detail__card">
            <table class="admin-detail__table">
                {{-- 氏名 --}}
                <tr>
                    <th class="admin-detail__th">名前</th>
                    <td class="admin-detail__td">{{ $attendance->user?->name ?? '-' }}</td>
                </tr>
                {{-- 日付 --}}
                <tr>
                    <th class="admin-detail__th">日付</th>
                    <td class="admin-detail__td">
                        {{ \Carbon\Carbon::parse($attendance->work_date)->locale('ja')->isoFormat('YYYY年M月D日') }}
                    </td>
                </tr>
                {{-- 出勤・退勤 --}}
                @php
                $origStart = $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '';
                $origEnd = $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '';
                @endphp
                <tr>
                    <th class="admin-detail__th">出勤・退勤</th>
                    <td class="admin-detail__td">
                        <input type="text"
                            name="start_time"
                            value="{{ old('start_time', $origStart) }}"
                            placeholder="HH:MM"
                            class="admin-detail__input">
                        〜
                        <input type="text"
                            name="end_time"
                            value="{{ old('end_time', $origEnd) }}"
                            placeholder="HH:MM"
                            class="admin-detail__input">
                        @error('start_time') <div class="admin-detail__error">{{ $message }}</div> @enderror
                        @error('end_time') <div class="admin-detail__error">{{ $message }}</div> @enderror
                    </td>
                </tr>

                {{-- 休憩（oldがあれば優先 → 空行を全部除去 → 最後に空行を1つだけ追加） --}}
                @php
                $oldBreaks = old('breaks');

                if (is_array($oldBreaks)) {
                // old のキーを 0..n に詰める
                $rows = array_values($oldBreaks);
                } else {
                // 既存をベースに配列化
                $rows = ($breaks ?? collect())->map(function ($b) {
                return [
                'break_time_id' => $b->id,
                'start' => $b->break_start ? \Carbon\Carbon::parse($b->break_start)->format('H:i') : '',
                'end' => $b->break_end ? \Carbon\Carbon::parse($b->break_end)->format('H:i') : '',
                ];
                })->values()->toArray();
                }

                // 完全な空行（startもendも空 かつ break_time_id空）を全て除去
                $rows = array_values(array_filter($rows, function ($r) {
                $start = trim($r['start'] ?? '');
                $end = trim($r['end'] ?? '');
                $id = $r['break_time_id'] ?? null;
                return !($start === '' && $end === '' && empty($id));
                }));

                // ★ バリデーションで戻ってきた時は空行を追加しない
                if (!$errors->any()) {
                // 通常時のみ、最後に空行を1行だけ追加
                $rows[] = ['break_time_id' => null, 'start' => '', 'end' => ''];
                }
                @endphp
                @foreach($rows as $i => $row)
                <tr>
                    <th class="admin-detail__th">休憩{{ $i+1 }}</th>
                    <td class="admin-detail__td">
                        <input type="text"
                            name="breaks[{{ $i }}][start]"
                            value="{{ $row['start'] ?? '' }}"
                            placeholder="HH:MM"
                            class="admin-detail__input">
                        〜
                        <input type="text"
                            name="breaks[{{ $i }}][end]"
                            value="{{ $row['end'] ?? '' }}"
                            placeholder="HH:MM"
                            class="admin-detail__input">

                        @if(!empty($row['break_time_id']))
                        <input type="hidden" name="breaks[{{ $i }}][break_time_id]" value="{{ $row['break_time_id'] }}">
                        @endif

                        @error("breaks.$i.start") <div class="admin-detail__error">{{ $message }}</div> @enderror
                        @error("breaks.$i.end") <div class="admin-detail__error">{{ $message }}</div> @enderror
                    </td>
                </tr>
                @endforeach
                {{-- 備考（必須） --}}
                <tr class="admin-detail__row--remarks">
                    <th class="admin-detail__th">備考</th>
                    <td class="admin-detail__td">
                        <textarea name="remarks" rows="2" class="admin-detail__textarea" required>{{ old('remarks', $attendance->remarks) }}</textarea>
                        @error('remarks') <div class="admin-detail__error">{{ $message }}</div> @enderror
                    </td>
                </tr>
            </table>
        </div>
        <div class="admin-detail__actions">
            <button type="submit" class="admin-detail__btn">修正</button>
        </div>
    </form>
</div>
@endsection