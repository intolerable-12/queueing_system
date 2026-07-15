<!doctype html>
<html>

<head>
    <script src="https://cdn.jsdelivr.net/npm/axios@1.6.7/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.2.6/dist/echo.iife.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <title>Counter</title>

    <style>
        
    </style>

    <link rel="stylesheet" href="{{ asset('styles/counter.css') }}">
</head>

<body>

    <!-- HEADER -->
    <div class="header-bar">
        <div class="d-flex align-items-center">
            <div class="circle"></div>
            <h5 class="fw-bold text-white mb-0">
                {{ ucfirst($counter->type) }} {{ $counter->name }}
            </h5>
        </div>
        @php
            $closed = \App\Models\QueueCutoff::whereDate('cutoff_date', today())
                ->where('is_closed', true)
                ->exists();
        @endphp

        <div class="d-flex gap-2">
            <a href="{{ route('queue.restart.index') }}" class="btn btn-light fw-bold">Restart Queue</a>
            @if(!$closed)

                <form method="POST" action="{{ route('counter.cutoff') }}"
                    onsubmit="return confirm('Stop accepting queue tickets for today?')">

                    @csrf

                    <button class="btn btn-warning fw-bold">

                        CUT-OFF

                    </button>

                </form>

            @else

                <form method="POST" action="{{ route('counter.reopen') }}" onsubmit="return confirm('Reopen queue today?')">

                    @csrf

                    <button class="btn btn-success fw-bold">

                        REOPEN

                    </button>

                </form>

            @endif
            <a href="{{ route('media.index') }}" class="btn btn-light fw-bold">Manage TV Content</a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="btn btn-danger fw-bold">Logout</button>
            </form>
        </div>
    </div>

    <!-- MAIN -->
    <div class="main-wrapper">

        <!-- LEFT -->
        <div class="left-panel">

            <!-- CALL AGAIN for currently serving: only show if nowServing exists -->
            @if($nowServing)
                <form method="post" action="{{ route('counter.callAgain', [$counter->id, $nowServing->id]) }}"
                    style="display:inline">
                    @csrf
                    <button type="submit" class="call-again-btn">CALL AGAIN</button>
                </form>
            @endif

            <!-- CENTERED SERVING -->
            <div class="serving-center">
                <div class="serving-label">CURRENTLY SERVING:</div>

                @if($nowServing)
                    <div class="serving-code">{{ $nowServing->code }}</div>
                @else
                    <div class="serving-code">—</div>
                @endif
            </div>

            <div class="bottom-actions">
                @if($nowServing)
                    <form method="post" action="{{ route('counter.hold', [$counter->id, $nowServing->id]) }}">
                        @csrf
                        <button class="btn btn-dark">ON-HOLD</button>
                    </form>
                @endif

                <form method="post" action="{{ route('counter.next', $counter->id) }}">
                    @csrf
                    <button class="btn btn-dark">NEXT</button>
                </form>
            </div>
        </div>

        <!-- RIGHT -->
        <div class="right-panel">

            <div class="panel-title">QUEUE</div>
            <div class="queue-list-container">
                <ul class="list-group mb-0">
                    @forelse($queue as $index => $t)
                        <li class="list-group-item text-center fw-bold">
                            {{ $t->code }}
                            @if($index === 0 && !$nowServing)
                                <span class="next-badge">NEXT</span>
                            @endif
                        </li>
                    @empty
                        <li class="list-group-item text-center">No tickets.</li>
                    @endforelse
                </ul>
            </div>

            <div class="panel-title">ON-HOLD</div>
            <div class="onhold-list-container">
                <ul class="list-group mb-0">
                    @forelse($onHold as $t)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-bold">{{ $t->code }}</span>

                            <div class="btn-group">
                                <form method="post" action="{{ route('counter.callAgain', [$counter->id, $t->id]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm">Call Again</button>
                                </form>
                                <form method="post" action="{{ route('counter.removeHold', [$counter->id, $t->id]) }}">
                                    @method('DELETE')
                                    @csrf
                                    <button class="btn btn-outline-danger btn-sm">✕</button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center">No on-hold tickets.</li>
                    @endforelse
                </ul>
            </div>

        </div>
    </div>

    <!-- JS / ECHO  -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const counterId = {{ $counter->id }};
            window.Echo.channel('queue.{{ $counter->type }}')
                .listen('.ticket.created', () => location.reload())
                .listen('.ticket.serving', () => location.reload())
                .listen('.ticket.on_hold', () => location.reload())
                .listen('.ticket.done', () => location.reload());

            // disable both NEXT and ON-HOLD buttons for 6 seconds
            const nextPressTime = sessionStorage.getItem('nextPressTime_{{ $counter->id }}');
            if (nextPressTime) {
                const elapsed = Date.now() - parseInt(nextPressTime);
                const remaining = 6000 - elapsed;

                if (remaining > 0) {
                    // Disable ON-HOLD button
                    const holdBtn = document.querySelector('.bottom-actions form[action*="hold"] button');
                    if (holdBtn) {
                        holdBtn.disabled = true;
                        const originalText = holdBtn.textContent;
                        holdBtn.textContent = 'Wait...';
                        holdBtn.style.opacity = '0.6';

                        setTimeout(() => {
                            holdBtn.disabled = false;
                            holdBtn.textContent = originalText;
                            holdBtn.style.opacity = '1';
                        }, remaining);
                    }

                    // Disable NEXT button
                    const nextBtn = document.querySelector('.bottom-actions form[action*="next"] button');
                    if (nextBtn) {
                        nextBtn.disabled = true;
                        const originalNextText = nextBtn.textContent;
                        nextBtn.textContent = 'Wait...';
                        nextBtn.style.opacity = '0.6';

                        setTimeout(() => {
                            nextBtn.disabled = false;
                            nextBtn.textContent = originalNextText;
                            nextBtn.style.opacity = '1';
                            sessionStorage.removeItem('nextPressTime_{{ $counter->id }}');
                        }, remaining);
                    }
                }
            }

            // Debounce for NEXT and ON-HOLD buttons
            const forms = document.querySelectorAll('.bottom-actions form, .left-panel > form');
            forms.forEach(form => {
                form.addEventListener('submit', function (e) {
                    const btn = this.querySelector('button[type="submit"]');
                    if (btn && btn.disabled) {
                        e.preventDefault();
                        return false;
                    }

                    // If this is the NEXT button, store timestamp
                    if (this.action.includes('next')) {
                        sessionStorage.setItem('nextPressTime_{{ $counter->id }}', Date.now().toString());
                    }

                    if (btn) {
                        btn.disabled = true;
                        const originalText = btn.textContent;
                        btn.textContent = 'Please wait...';
                        btn.style.opacity = '0.6';

                        // Re-enable after 10 seconds as fallback
                        setTimeout(() => {
                            btn.disabled = false;
                            btn.textContent = originalText;
                            btn.style.opacity = '1';
                        }, 10000);
                    }
                });
            });
        });
    </script>

</body>

</html>