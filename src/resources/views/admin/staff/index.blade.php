@extends('layouts.admin')

@section('title', 'スタッフ一覧')
@push('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff/index.css') }}" />
@endpush
@section('content')
<div class="staff__container">
    <h2 class="staff__title">スタッフ一覧</h2>
    <div class="staff__card">
        <table class="staff__table">
            <thead class="staff__thead">
                <tr class="staff__row">
                    <th class="staff__th staff__th-name">名前</th>
                    <th class="staff__th staff__th-email">メールアドレス</th>
                    <th class="staff__th staff__th-action">月次勤怠</th>
                </tr>
            </thead>
            <tbody class="staff__tbody">
                @forelse($users as $user)
                <tr class="staff__row">
                    <td class="staff__td">{{ $user->name }}</td>
                    <td class="staff__td">{{ $user->email }}</td>
                    <td class="staff__td staff__td-action">
                        {{-- 当月へ遷移（コントローラ側で month が無ければ now() を使う想定） --}}
                        <a class="staff__detail-link"
                            href="{{ route('admin.staff.attendance', ['user' => $user->id]) }}">
                            詳細
                        </a>
                    </td>
                </tr>
                @empty
                <tr class="staff__row">
                    <td class="staff__td" colspan="3">スタッフが登録されていません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{-- ページネーションがある場合 --}}
        @if(method_exists($users, 'links'))
        <div class="staff__pagination">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection