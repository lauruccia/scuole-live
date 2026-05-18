{{-- @generated cache-bust 2026-05-18 v3 — non rimuovere il commento --}}
@props([
    'title'    => 'Domande frequenti',
    'subtitle' => '',
    'items'    => [],
])

@php
    // Build JSON-LD via array PHP + json_encode (mai direttive @type/@context
    // letterali nel template, così Blade non rischia di confondersi).
    $faqJsonLd = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(function ($item) {
            return [
                '@type'          => 'Question',
                'name'           => strip_tags($item['q'] ?? ''),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => strip_tags($item['a'] ?? ''),
                ],
            ];
        }, $items ?? []),
    ];
@endphp

{{-- JSON-LD FAQPage Schema --}}
<script type="application/ld+json">
{!! json_encode($faqJsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>

<section class="faq-section" aria-labelledby="faq-title">
    <div class="c">
        <h2 id="faq-title" class="section-title">{{ $title }}</h2>
        @if($subtitle)
            <p class="section-subtitle">{{ $subtitle }}</p>
        @endif

        <div class="faq-list" itemscope itemtype="https://schema.org/FAQPage">
            @foreach($items as $i => $item)
            <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <button
                    class="faq-question"
                    aria-expanded="false"
                    aria-controls="faq-answer-{{ $i }}"
                    itemprop="name"
                    onclick="toggleFaq(this)"
                >
                    {{ $item['q'] }}
                    <span class="faq-icon" aria-hidden="true">+</span>
                </button>
                <div
                    id="faq-answer-{{ $i }}"
                    class="faq-answer"
                    itemscope
                    itemprop="acceptedAnswer"
                    itemtype="https://schema.org/Answer"
                    hidden
                >
                    <div itemprop="text">{!! $item['a'] !!}</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<style>
.faq-section { padding: 64px 0; background: #f8fafc; }
.faq-section .section-title { text-align: center; font-size: clamp(1.5rem, 3vw, 2rem); color: #071428; margin-bottom: 8px; }
.faq-section .section-subtitle { text-align: center; color: #556; margin-bottom: 40px; font-size: 1.05rem; }
.faq-list { max-width: 820px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }
.faq-item { background: #fff; border: 1px solid #dde3ee; border-radius: 10px; overflow: hidden; }
.faq-question {
    width: 100%; display: flex; justify-content: space-between; align-items: center;
    padding: 18px 22px; background: none; border: none; cursor: pointer;
    font-size: 1rem; font-weight: 600; color: #071428; text-align: left; gap: 12px;
    transition: background .2s;
}
.faq-question:hover { background: #f0f4ff; }
.faq-question[aria-expanded="true"] { color: #1A56DB; }
.faq-question[aria-expanded="true"] .faq-icon { transform: rotate(45deg); color: #1A56DB; }
.faq-icon { font-size: 1.4rem; font-weight: 300; flex-shrink: 0; transition: transform .2s; }
.faq-answer { padding: 0 22px 18px; color: #334; font-size: .97rem; line-height: 1.7; }
.faq-answer p { margin: 0 0 8px; }
.faq-answer a { color: #1A56DB; }
</style>

<script>
function toggleFaq(btn) {
    const expanded = btn.getAttribute('aria-expanded') === 'true';
    document.querySelectorAll('.faq-question').forEach(b => {
        b.setAttribute('aria-expanded', 'false');
        const ans = document.getElementById(b.getAttribute('aria-controls'));
        if (ans) ans.hidden = true;
    });
    if (!expanded) {
        btn.setAttribute('aria-expanded', 'true');
        const ans = document.getElementById(btn.getAttribute('aria-controls'));
        if (ans) ans.hidden = false;
    }
}
</script>
