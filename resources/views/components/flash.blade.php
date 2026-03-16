@php
    $allowedTypes = ['success', 'danger', 'warning', 'info'];
    $flashes = [];

    foreach ($allowedTypes as $type) {
        if (session()->has($type)) {
            $flashes[] = [
                'type' => $type,
                'message' => session($type)
            ];
        }
    }
@endphp

@if(!empty($flashes))
<div id="flash-message-container" class="position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    @foreach($flashes as $flash)
        <div class="alert alert-{{ $flash['type'] }} alert-dismissible fade show" role="alert">
            {{ $flash['message'] }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endforeach
</div>

@endif
