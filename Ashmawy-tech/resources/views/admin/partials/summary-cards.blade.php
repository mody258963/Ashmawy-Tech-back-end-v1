@php($cards = $cards ?? [])

@if (count($cards))
    <div class="row mb-3">
        @foreach ($cards as $card)
            <div class="col-lg-3 col-6 mb-3">
                <div class="small-box {{ $card['class'] ?? 'bg-info' }}">
                    <div class="inner">
                        <h3>{{ $card['value'] ?? 0 }}</h3>
                        <p>{{ $card['label'] ?? '' }}</p>
                    </div>
                    @if (! empty($card['icon']))
                        <div class="icon"><i class="{{ $card['icon'] }}"></i></div>
                    @endif
                    @if (! empty($card['url']))
                        <a href="{{ $card['url'] }}" class="small-box-footer">
                            {{ __('messages.more') }} <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

