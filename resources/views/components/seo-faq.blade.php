{{--
    Componente: <x-seo-faq />
    ─────────────────────────────────────────────────────────────────────────────
    Sezione FAQ riusabile con structured data FAQPage (rich snippet in SERP).

    Props attese:
      title    string  — titolo della sezione (default "Domande frequenti")
      subtitle string? — sottotitolo opzionale
      items    array   — lista di { q: string, a: string } (a può contenere HTML)

    Esempio:
      <x-seo-faq
          title="Domande frequenti sui corsi di inglese a Roma"
          :items="[
              ['q' => '...', 'a' => '<p>...</p>'],
              ...
          ]"
      />

    Lo schema FAQPage viene emesso solo se sono presenti voci.
--}}
@props([
    'title'    => 'Domande frequenti',
    'subtitle' => null,
    'items'    => [],
])

@push('styles')
<style>
.seo-faq { padding: 80px 0; }
.seo-faq-list {
    max-width: 820px; margin: 0 auto;
    display: flex; flex-direction: column; gap: 12px;
}
.seo-faq-item {
    background: var(--white, #fff);
    border: 1.5px solid var(--border, #DDE4EE);
    border-radius: var(--radius-lg, 18px);
    transition: border-color .25s, box-shadow .25s;
    overflow: hidden;
}
.seo-faq-item[open] {
    border-color: var(--blue, #1A56DB);
    box-shadow: 0 10px 32px rgba(26,86,219,.08);
}
.seo-faq-item summary {
    list-style: none;
    cursor: pointer;
    padding: 20px 24px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 1rem;
    color: var(--navy, #0D1B2E);
    position: relative;
    padding-right: 56px;
}
.seo-faq-item summary::-webkit-details-marker { display: none; }
.seo-faq-item summary::after {
    content: '+';
    position: absolute;
    right: 22px; top: 50%;
    transform: translateY(-50%);
    width: 28px; height: 28px;
    background: var(--blue-l, #EEF3FF);
    color: var(--blue, #1A56DB);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; font-weight: 400;
    line-height: 1;
    transition: transform .25s, background .25s;
}
.seo-faq-item[open] summary::after {
    content: '−';
    background: var(--blue, #1A56DB);
    color: #fff;
}
.seo-faq-answer {
    padding: 0 24px 22px;
    color: var(--muted, #4E5D72);
    font-size: .925rem;
    line-height: 1.75;
}
.seo-faq-answer p:not(:last-child) { margin-bottom: 12px; }
.seo-faq-answer a {
    color: var(--blue, #1A56DB);
    text-decoration: underline;
    font-weight: 600;
}
</style>
@endpush

<section class="seo-faq" aria-labelledby="seo-faq-title-{{ md5($title) }}">
    <div class="c">
        <div class="sec-header center">
            <div class="section-label" style="justify-content:center;">FAQ</div>
            <h2 class="sec-heading" id="seo-faq-title-{{ md5($title) }}">{{ $title }}</h2>
            @if($subtitle)
                <p class="sec-subtext" style="text-align:center;margin:0 auto;">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="seo-faq-list">
            @foreach($items as $i => $item)
                <details class="seo-faq-item">
                    <summary>{{ $item['q'] }}</summary>
                    <div class="seo-faq-answer">{!! $item['a'] !!}</div>
                </details>
            @endforeach
        </div>
    </div>
</section>

@if(count($items))
@push('scripts')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        @foreach($items as $i => $item)
        {
            "@@type": "Question",
            "name": @json($item['q']),
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": @json(strip_tags($item['a']))
            }
        }@if(! $loop->last),@endif
        @endforeach
    ]
}
</script>
@endpush
@endif
