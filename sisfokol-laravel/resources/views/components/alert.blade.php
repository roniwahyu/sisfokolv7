@props(['type' => 'info', 'message' => ''])

<div class="alert alert-{{ $type }} alert-dismissible fade show">
    {!! $message !!}
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
</div>
