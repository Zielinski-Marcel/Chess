<template>
    <svg viewBox="0 0 100 124" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <clipPath id="body-clip">
                <!-- góra: x 22-78 (56px), dół: x 14-86 (72px) — delikatne zwężenie -->
                <polygon points="22,28 78,28 88,102 12,102" />
            </clipPath>
        </defs>

        <!-- ── BLANKI ─────────────────────────────── -->
        <rect x="16" y="8"  width="16" height="14" rx="2"/>
        <rect x="42" y="8"  width="16" height="14" rx="2"/>
        <rect x="68" y="8"  width="16" height="14" rx="2"/>

        <!-- ── ŁĄCZNIK pod blankami ───────────────── -->
        <rect x="16" y="20" width="68" height="8"/>

        <!-- ── KORPUS trapezowy ───────────────────── -->
        <polygon points="22,28 78,28 88,102 12,102"/>

        <!-- ── SPOINY POZIOME ─────────────────────── -->
        <rect x="10" y="42" width="80" height="2.5" fill="#555" clip-path="url(#body-clip)"/>
        <rect x="10" y="56" width="80" height="2.5" fill="#555" clip-path="url(#body-clip)"/>
        <rect x="10" y="70" width="80" height="2.5" fill="#555" clip-path="url(#body-clip)"/>
        <rect x="10" y="85" width="80" height="2.5" fill="#555" clip-path="url(#body-clip)"/>

        <!-- ── SPOINY PIONOWE ─────────────────────── -->
        <rect x="50" y="28" width="2.5" height="14" fill="#555" clip-path="url(#body-clip)"/>

        <rect x="34" y="44" width="2.5" height="12" fill="#555" clip-path="url(#body-clip)"/>
        <rect x="66" y="44" width="2.5" height="12" fill="#555" clip-path="url(#body-clip)"/>

        <rect x="49" y="58" width="2.5" height="12" fill="#555" clip-path="url(#body-clip)"/>

        <rect x="33" y="72" width="2.5" height="13" fill="#555" clip-path="url(#body-clip)"/>
        <rect x="63" y="72" width="2.5" height="13" fill="#555" clip-path="url(#body-clip)"/>

        <rect x="48" y="87" width="2.5" height="15" fill="#555" clip-path="url(#body-clip)"/>

        <!-- ── PODSTAWA ───────────────────────────── -->
        <rect x="10" y="102" width="80" height="8" rx="1"/>

        <!-- ── STOPKA ─────────────────────────────── -->
        <rect x="4"  y="110" width="92" height="10" rx="3"/>
    </svg>
</template>
