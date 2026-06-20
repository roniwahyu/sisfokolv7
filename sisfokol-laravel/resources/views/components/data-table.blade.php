@props(['headers' => [], 'rows' => [], 'actions' => false])

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
                @if ($actions)
                    <th>Aksi</th>
                @endif
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
