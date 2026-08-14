@if(isset($socialVideos) && $socialVideos->isNotEmpty())
{{-- Floating live video strip — ngang, thu/mở (expand) --}}
<div class="live-float is-open" id="liveFloat" aria-label="Video mạng xã hội">
    {{-- Nút thu gọn: hiện khi đang mở --}}
    <button
        type="button"
        class="live-float__chip live-float__chip--collapse"
        id="liveFloatCollapse"
        title="Thu gọn dải video"
        aria-label="Thu gọn dải video"
    >
        <span class="live-float__chip-live">LIVE</span>
        <span class="live-float__chip-text">Video</span>
        <span class="live-float__chip-count">{{ min(10, $socialVideos->count()) }}</span>
        <i class="bi bi-chevron-down live-float__chip-chevron" aria-hidden="true"></i>
    </button>

    {{-- Nút mở ra: hiện khi đang thu --}}
    <button
        type="button"
        class="live-float__chip live-float__chip--expand"
        id="liveFloatToggle"
        aria-expanded="true"
        aria-controls="liveFloatStack"
        title="Mở video thực tế"
    >
        <span class="live-float__chip-live">LIVE</span>
        <span class="live-float__chip-text">Video</span>
        <span class="live-float__chip-count">{{ min(10, $socialVideos->count()) }}</span>
        <i class="bi bi-chevron-up live-float__chip-chevron" aria-hidden="true"></i>
    </button>

    <div class="live-float__panel" id="liveFloatStack" data-live-video-rail>
        <div class="live-float__track" tabindex="0" aria-label="Danh sách video mới nhất — vuốt ngang">
            @foreach($socialVideos as $video)
                <a
                    href="{{ $video->url }}"
                    class="live-float-card"
                    target="_blank"
                    rel="noopener noreferrer"
                    data-platform="{{ $video->platform }}"
                    data-can-preview="{{ $video->can_hover_preview ? '1' : '0' }}"
                    data-preview-url="{{ $video->preview_url ?: '' }}"
                    data-embed-url="{{ $video->embed_url ?: '' }}"
                    title="{{ $video->title }}"
                    aria-label="{{ $video->title }} — mở trên {{ $video->platform_label }}"
                >
                    <div class="live-float-card__media">
                        <img
                            class="live-float-card__thumb"
                            src="{{ $video->thumbnail_url }}"
                            alt=""
                            loading="lazy"
                            decoding="async"
                        >
                        <div class="live-float-card__preview" hidden aria-hidden="true"></div>
                        <span class="live-float-card__live">LIVE</span>
                        <span class="live-float-card__platform live-float-card__platform--{{ $video->platform }}" aria-hidden="true">
                            @if($video->platform === 'youtube')
                                <i class="bi bi-youtube"></i>
                            @elseif($video->platform === 'tiktok')
                                <i class="bi bi-tiktok"></i>
                            @elseif($video->platform === 'facebook')
                                <i class="bi bi-facebook"></i>
                            @else
                                <i class="bi bi-play-btn"></i>
                            @endif
                        </span>
                        <span class="live-float-card__play" aria-hidden="true">
                            <i class="bi bi-play-fill"></i>
                        </span>
                        <div class="live-float-card__shade"></div>
                        <div class="live-float-card__meta">
                            <strong class="live-float-card__title">{{ $video->title }}</strong>
                            @if($video->channel_name)
                                <span class="live-float-card__channel">{{ $video->channel_name }}</span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif
