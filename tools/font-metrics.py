#!/usr/bin/env python3
"""Regenerate the metric-matched fallback @font-face rules in bigdinosaur/style.css.

Needs fonttools + brotli (pip install fonttools brotli). Run from this tools/ directory:

    python3 font-metrics.py > /tmp/fallbacks.css

then paste the output over the "Fallback" @font-face lines in style.css.
Android fallbacks (Roboto, Noto Serif) are read from a directory given as the
first argument containing roboto-roman.woff2, roboto-italic.woff2,
notoserif-roman.woff2, notoserif-italic.woff2 (the latin subsets served by
fonts.googleapis.com are fine); macOS system fonts cover the rest.

Method: size-adjust scales the fallback so its frequency-weighted average
advance for running English text (lowercase + space; mixed-case "title" text
for the display faces) equals the web font's. ascent/descent/line-gap
overrides copy the web font's vertical metrics (typo metrics when
USE_TYPO_METRICS is set, else hhea), divided by size-adjust. Variable web
fonts are instanced at the weight the theme actually sets.

The frequency estimate lands within ~0.6% of the truth, which is still a
line per paragraph at phone widths, so MEASURED below overrides it where we
have a better number: for the macOS/Windows fonts, canvas measureText in
Brave of the homepage's real intro + excerpt text (9.5k chars) and post
titles, web font vs fallback (2026-11-09); for the Android fonts, glyph
advances over the same text (no engine to hand). Re-measure if the fonts or
the body/title weights change; delete an entry to fall back to the estimate.
"""
import sys
from fontTools.ttLib import TTFont
from fontTools.varLib import instancer

FONTS = '../bigdinosaur/assets/fonts'
SYS = '/System/Library/Fonts/Supplemental'
FREQ = dict(a=8.2,b=1.5,c=2.8,d=4.3,e=12.7,f=2.2,g=2.0,h=6.1,i=7.0,j=0.15,k=0.77,l=4.0,m=2.4,n=6.7,o=7.5,p=1.9,q=0.095,r=6.0,s=6.3,t=9.1,u=2.8,v=0.98,w=2.4,x=0.15,y=2.0,z=0.074)

def load(path, number=0, wght=None):
    f = TTFont(path, fontNumber=number)
    if wght is not None and 'fvar' in f:
        f = instancer.instantiateVariableFont(f, {'wght': wght})
    return f

def adv(f, ch):
    g = f.getBestCmap().get(ord(ch))
    return f['hmtx'][g][0]

def widths(f):
    upm = f['head'].unitsPerEm
    tot = sum(FREQ.values())
    lower = sum(adv(f, c) * w for c, w in FREQ.items()) / tot
    caps = sum(adv(f, c.upper()) * w for c, w in FREQ.items()) / tot
    space = adv(f, ' ')
    return dict(body=(lower * 0.82 + space * 0.18) / upm,
                title=(lower * 0.70 + caps * 0.12 + space * 0.18) / upm)

def vmetrics(f):
    upm = f['head'].unitsPerEm; os2 = f['OS/2']; hh = f['hhea']
    if os2.fsSelection & (1 << 7):
        a, d, g = os2.sTypoAscender, os2.sTypoDescender, os2.sTypoLineGap
    else:
        a, d, g = hh.ascent, hh.descent, hh.lineGap
    return a / upm, abs(d) / upm, g / upm

MEASURED = {  # (family, style) -> size-adjust %
    ('Crimson Pro Fallback', 'normal'): 87.33,
    ('Crimson Pro Fallback', 'italic'): 77.46,
    ('Crimson Pro Fallback Android', 'normal'): 80.96,
    ('Crimson Pro Fallback Android', 'italic'): 75.20,
    ('Jost Fallback', 'normal'): 95.00,
    ('Jost Fallback', 'italic'): 94.06,
    ('Jost Fallback Android', 'normal'): 96.45,
    ('Jost Fallback Android', 'italic'): 98.05,
    ('Michroma Fallback', 'normal'): 140.86,
    ('Michroma Fallback Android', 'normal'): 143.35,
}

def css(family, web, fb, local_names, style='normal', weight='400', key='body'):
    sa = widths(web)[key] / widths(fb)[key]
    if (family, style) in MEASURED:
        sa = MEASURED[(family, style)] / 100
    asc, desc, gap = (m / sa for m in vmetrics(web))
    src = ', '.join(f"local('{n}')" for n in local_names)
    return (f"@font-face {{ font-family: '{family}'; font-style: {style}; font-weight: {weight}; src: {src}; "
            f"size-adjust: {sa*100:.2f}%; ascent-override: {asc*100:.2f}%; descent-override: {desc*100:.2f}%; line-gap-override: {gap*100:.2f}%; }}")

def main(android):
    w = lambda n, **k: load(f'{FONTS}/{n}', **k)
    s = lambda n: load(f'{SYS}/{n}')
    a = lambda n, **k: load(f'{android}/{n}', **k)
    cp, cpi = w('crimsonpro-normal-latin.woff2', wght=200), w('crimsonpro-italic-latin.woff2', wght=200)
    jo, joi = w('jost-roman-latin.woff2', wght=400), w('jost-italic-latin.woff2', wght=400)
    mi, pm = w('michroma-400-latin.woff2'), w('ibmplexmono-400-latin.woff2')
    ro, roi = a('roboto-roman.woff2', wght=400), a('roboto-italic.woff2', wght=400)
    ns, nsi = a('notoserif-roman.woff2', wght=400), a('notoserif-italic.woff2', wght=400)
    print('\n'.join([
        css('Crimson Pro Fallback', cp, s('Georgia.ttf'), ['Georgia'], 'normal', '200 900'),
        css('Crimson Pro Fallback', cpi, s('Georgia Italic.ttf'), ['Georgia Italic', 'Georgia-Italic'], 'italic', '200 900'),
        css('Crimson Pro Fallback Android', cp, ns, ['Noto Serif', 'NotoSerif', 'Noto Serif Regular', 'NotoSerif-Regular'], 'normal', '200 900'),
        css('Crimson Pro Fallback Android', cpi, nsi, ['Noto Serif Italic', 'NotoSerif-Italic'], 'italic', '200 900'),
        css('Jost Fallback', jo, s('Arial.ttf'), ['Arial', 'ArialMT'], 'normal', '100 900'),
        css('Jost Fallback', joi, s('Arial Italic.ttf'), ['Arial Italic', 'Arial-ItalicMT'], 'italic', '100 900'),
        css('Jost Fallback Android', jo, ro, ['Roboto', 'Roboto Regular', 'Roboto-Regular'], 'normal', '100 900'),
        css('Jost Fallback Android', joi, roi, ['Roboto Italic', 'Roboto-Italic'], 'italic', '100 900'),
        css('Michroma Fallback', mi, s('Arial.ttf'), ['Arial', 'ArialMT'], 'normal', '400', 'title'),
        css('Michroma Fallback Android', mi, ro, ['Roboto', 'Roboto Regular', 'Roboto-Regular'], 'normal', '400', 'title'),
        css('IBM Plex Mono Fallback', pm, load('/System/Library/Fonts/Menlo.ttc', 0), ['Menlo', 'Menlo-Regular'], 'normal', '400 500'),
        css('IBM Plex Mono Fallback Courier', pm, s('Courier New.ttf'), ['Courier New', 'CourierNewPSMT'], 'normal', '400 500'),
    ]))

if __name__ == '__main__':
    main(sys.argv[1] if len(sys.argv) > 1 else 'android-fonts')
