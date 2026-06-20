@props(['title', 'value', 'icon' => 'fa-info-circle', 'color' => 'info'])

<div class="col-md-3 col-sm-6 col-12">
    <div class="info-box">
        <span class="info-box-icon bg-{{ $color }}"><i class="fas {{ $icon }}"></i></span>
        <div class="info-box-content">
            <span class="info-box-text">{{ $title }}</span>
            <span class="info-box-number">{{ $value }}</span>
        </div>
    </div>
</div>
