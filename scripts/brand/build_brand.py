"""Builds the Agency's logo files from the two official originals (ADR-0006).

Sources (as received from the Agency, white background):
    resources/brand/hinta-logo-stacked.source.png     the wordmark, emblem and names, stacked
    resources/brand/hinta-logo-horizontal.source.png  the emblem beside the wordmark and names

Outputs, all with a transparent background unless noted:
    public/brand/emblem-{128,256,512}.png   the round emblem alone (header, footer, admin, placeholders)
    public/brand/icon-{192,512}.png         app icons for the web manifest
    public/brand/lockup-horizontal.png      the emblem, wordmark and names in one row (light surfaces)
    public/brand/lockup-stacked.png         the stacked version (press kit, sign-in)
    public/brand/wordmark.png               "HinTA" alone
    public/brand/*-dark.png                 the lockups and wordmark with light lettering, for dark surfaces
    public/favicon.ico (16/32/48), public/favicon-32.png, public/favicon-96.png
    public/apple-touch-icon.png             180 px on white (iOS ignores transparency)

Only the white that touches the image's edge is removed (with its anti-aliased rim); the white inside the
emblem's circle is part of the design and stays (new names, not overwritten ones, so caches pick them up); in the lettering every white goes (the inside of the "A").
The dark-surface variants recolour the lettering to plaster and keep the orange ring. Run after the Agency sends new originals and commit the
output:

    python scripts/brand/build_brand.py
"""
from collections import deque
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[2]
SRC = ROOT / 'resources/brand'
PUBLIC = ROOT / 'public'
BRAND = PUBLIC / 'brand'


def transparent_outside(img: Image.Image, threshold: int = 200, rim: int = 2) -> Image.Image:
    """White connected to the border becomes transparent; its rim is un-blended from white."""
    img = img.convert('RGBA')
    w, h = img.size
    px = img.load()
    outside = bytearray(w * h)
    queue = deque()
    for x in range(w):
        queue.extend([(x, 0), (x, h - 1)])
    for y in range(h):
        queue.extend([(0, y), (w - 1, y)])
    while queue:
        x, y = queue.popleft()
        i = y * w + x
        if outside[i]:
            continue
        r, g, b, a = px[x, y]
        if min(r, g, b) < threshold:
            continue
        outside[i] = 1
        for nx, ny in ((x + 1, y), (x - 1, y), (x, y + 1), (x, y - 1)):
            if 0 <= nx < w and 0 <= ny < h and not outside[ny * w + nx]:
                queue.append((nx, ny))
    # The anti-aliased rim: pixels within `rim` of the outside region.
    edge = bytearray(outside)
    for _ in range(rim):
        grown = bytearray(edge)
        for y in range(h):
            for x in range(w):
                if edge[y * w + x]:
                    continue
                if any(0 <= nx < w and 0 <= ny < h and edge[ny * w + nx]
                       for nx, ny in ((x + 1, y), (x - 1, y), (x, y + 1), (x, y - 1))):
                    grown[y * w + x] = 1
        edge = grown
    for y in range(h):
        for x in range(w):
            if not edge[y * w + x]:
                continue
            r, g, b, _ = px[x, y]
            alpha = max(255 - r, 255 - g, 255 - b) / 255
            if alpha <= 0.0:
                px[x, y] = (255, 255, 255, 0)
                continue
            un = [round(min(255, max(0, (c - 255 * (1 - alpha)) / alpha))) for c in (r, g, b)]
            px[x, y] = (*un, round(alpha * 255))
    return img


def knockout(img: Image.Image, box: tuple[int, int, int, int]) -> Image.Image:
    """Every white inside `box` becomes transparent (the counters of the letters)."""
    px = img.load()
    for y in range(box[1], box[3]):
        for x in range(box[0], box[2]):
            r, g, b, a = px[x, y]
            if a == 0:
                continue
            alpha = max(255 - r, 255 - g, 255 - b) / 255
            if alpha <= 0.0:
                px[x, y] = (255, 255, 255, 0)
                continue
            un = [round(min(255, max(0, (c - 255 * (1 - alpha)) / alpha))) for c in (r, g, b)]
            px[x, y] = (*un, round(alpha * 255 * a / 255))
    return img


def for_dark(img: Image.Image, box: tuple[int, int, int, int], light=(247, 242, 233)) -> Image.Image:
    """Recolours the lettering inside `box` to plaster; the orange ring keeps its colour."""
    out = img.copy()
    px = out.load()
    for y in range(box[1], box[3]):
        for x in range(box[0], box[2]):
            r, g, b, a = px[x, y]
            if a and not (r > g + 40 and g > b + 40):
                px[x, y] = (*light, a)
    return out


def square(img: Image.Image, pad: float = 0.04) -> Image.Image:
    """Centres the image on a transparent square with a little padding."""
    side = round(max(img.size) * (1 + 2 * pad))
    canvas = Image.new('RGBA', (side, side), (0, 0, 0, 0))
    canvas.paste(img, ((side - img.width) // 2, (side - img.height) // 2), img)
    return canvas


def save(img: Image.Image, path: Path, size: int | None = None) -> None:
    out = img.resize((size, size), Image.LANCZOS) if size else img
    out.save(path, optimize=True)
    print(f'{path.relative_to(ROOT).as_posix():40} {out.size[0]}x{out.size[1]}')


def main() -> None:
    stacked = Image.open(SRC / 'hinta-logo-stacked.source.png')
    horizontal = Image.open(SRC / 'hinta-logo-horizontal.source.png')

    # Regions measured on the originals (800x800 and 1280x480), with a few pixels of margin.
    emblem = square(transparent_outside(stacked.crop((222, 210, 579, 631))))
    # Lettering boxes are relative to each crop: right of the emblem, or above and below it.
    lockup_h = transparent_outside(horizontal.crop((188, 60, 1108, 430)))
    text_h = (330, 0, lockup_h.width, lockup_h.height)
    knockout(lockup_h, text_h)
    lockup_s = transparent_outside(stacked.crop((138, 92, 662, 721)))
    knockout(lockup_s, (0, 0, lockup_s.width, 112))
    knockout(lockup_s, (0, 545, lockup_s.width, lockup_s.height))
    wordmark = transparent_outside(horizontal.crop((558, 150, 945, 262)))
    knockout(wordmark, (0, 0, wordmark.width, wordmark.height))

    for size in (128, 256, 512):
        save(emblem, BRAND / f'emblem-{size}.png', size)
    for size in (192, 512):
        save(emblem, BRAND / f'icon-{size}.png', size)
    save(lockup_h, BRAND / 'lockup-horizontal.png')
    save(lockup_s, BRAND / 'lockup-stacked.png')
    save(wordmark, BRAND / 'wordmark.png')
    save(for_dark(lockup_h, text_h), BRAND / 'lockup-horizontal-dark.png')
    dark_s = for_dark(lockup_s, (0, 0, lockup_s.width, 112))
    save(for_dark(dark_s, (0, 545, lockup_s.width, lockup_s.height)), BRAND / 'lockup-stacked-dark.png')
    save(for_dark(wordmark, (0, 0, wordmark.width, wordmark.height)), BRAND / 'wordmark-dark.png')

    save(emblem, PUBLIC / 'favicon-32.png', 32)
    save(emblem, PUBLIC / 'favicon-96.png', 96)
    emblem.resize((48, 48), Image.LANCZOS).save(PUBLIC / 'favicon.ico', sizes=[(16, 16), (32, 32), (48, 48)])
    print('public/favicon.ico                       16/32/48')
    touch = Image.new('RGBA', (180, 180), (255, 255, 255, 255))
    small = emblem.resize((160, 160), Image.LANCZOS)
    touch.paste(small, (10, 10), small)
    touch.convert('RGB').save(PUBLIC / 'apple-touch-icon.png', optimize=True)
    print('public/apple-touch-icon.png              180x180 on white')


if __name__ == '__main__':
    main()
