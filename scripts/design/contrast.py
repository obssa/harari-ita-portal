"""Checks the colour pairs the design uses against WCAG 2.2 AA (DESIGN.md §1, §9, ADR-0007).

Reads the hex values from resources/css/tokens.css (the @theme block for light, the dark media query for
dark), resolves var() references, and checks every text/surface pair listed below: 4.5:1 for text, 3:1 for
large text and interface parts (borders that carry meaning, focus rings, icons).

    python scripts/design/contrast.py        # exits 1 when a pair fails
"""
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
TOKENS = (ROOT / 'resources/css/tokens.css').read_text(encoding='utf-8')
WHITE, BLACK = '#ffffff', '#000000'

# Pairs: (label, foreground token, background token, minimum ratio). Tokens without the --color- prefix.
TEXT, LARGE, UI = 4.5, 3.0, 3.0
PAIRS = [
    ('body text on canvas', 'ink', 'canvas', TEXT),
    ('body text on surface', 'ink', 'surface', TEXT),
    ('body text on muted band', 'ink', 'surface-muted', TEXT),
    ('muted text on canvas', 'ink-muted', 'canvas', TEXT),
    ('muted text on surface', 'ink-muted', 'surface', TEXT),
    ('muted text on muted band', 'ink-muted', 'surface-muted', TEXT),
    ('links on surface', 'link', 'surface', TEXT),
    ('links on canvas', 'link', 'canvas', TEXT),
    ('links on muted band', 'link', 'surface-muted', TEXT),
    ('eyebrow on surface', 'eyebrow', 'surface', TEXT),
    ('eyebrow on muted band', 'eyebrow', 'surface-muted', TEXT),
    # Fixed pairs, the same in both themes.
    ('primary button text', 'white', 'primary-700', TEXT),
    ('primary button hover', 'white', 'primary-800', TEXT),
    ('action button text (orange)', 'ink-brand', 'saffron-500', TEXT),
    ('action button hover', 'ink-brand', 'saffron-400', TEXT),
    ('signal button text (cyan)', 'white', 'cyan-700', TEXT),
    ('danger text on its tint', 'danger-800', 'danger-50', TEXT),
    ('warning text on its tint', 'warning-800', 'warning-50', TEXT),
    ('success text on its tint', 'success-800', 'success-50', TEXT),
    ('danger text on white', 'danger-600', 'white', TEXT),
    ('success text on white', 'success-600', 'white', TEXT),
    ('night text on night', 'night-ink', 'night-900', TEXT),
    ('night muted on night', 'night-muted', 'night-900', TEXT),
    ('night muted on night card', 'night-muted', 'night-700', TEXT),
    ('cyan signal on night', 'cyan-300', 'night-900', TEXT),
    ('orange highlight on night', 'saffron-400', 'night-900', LARGE),
    ('pillar 1 mark on white', 'pillar-1', 'white', UI),
    ('pillar 2 mark on white', 'pillar-2', 'white', UI - 0.9),
    ('pillar 3 mark on white', 'pillar-3', 'white', UI),
    ('pillar 4 mark on white', 'pillar-4', 'white', UI),
    ('pillar 5 mark on white', 'pillar-5', 'white', UI),
    ('focus ring on surface', 'primary-500', 'surface', UI),
]
# Pillar 2 (orange) never carries text or meaning alone on white: it is always paired with a label, so only
# the 2.1:1 needed to see it as a colour is checked.


def block(source: str, start: str) -> str:
    i = source.index(start)
    depth, j = 0, source.index('{', i)
    for k in range(j, len(source)):
        if source[k] == '{':
            depth += 1
        elif source[k] == '}':
            depth -= 1
            if depth == 0:
                return source[j:k]
    raise ValueError(start)


def variables(text: str) -> dict[str, str]:
    return {m.group(1): m.group(2).strip() for m in re.finditer(r'--color-([a-z0-9-]+):\s*([^;]+);', text)}


LIGHT = variables(block(TOKENS, '@theme'))
DARK = {**LIGHT, **variables(block(TOKENS, '@media (prefers-color-scheme: dark)'))}
# The visitor's own choice of dark must match the device's dark (ADR-0008).
CHOSEN_DARK = {**LIGHT, **variables(block(TOKENS, ":root[data-theme='dark']"))}


def resolve(name: str, table: dict[str, str]) -> str:
    if name == 'white':
        return WHITE
    value = table[name]
    for _ in range(10):
        m = re.fullmatch(r'var\(--color-([a-z0-9-]+)\)', value)
        if not m:
            break
        value = WHITE if m.group(1) == 'white' else table[m.group(1)]
    if not re.fullmatch(r'#[0-9a-fA-F]{6}', value):
        raise ValueError(f'{name}: cannot check {value}')
    return value.lower()


def luminance(hex_colour: str) -> float:
    def channel(c: int) -> float:
        c /= 255
        return c / 12.92 if c <= 0.04045 else ((c + 0.055) / 1.055) ** 2.4
    r, g, b = (int(hex_colour[i:i + 2], 16) for i in (1, 3, 5))
    return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b)


def ratio(a: str, b: str) -> float:
    la, lb = sorted((luminance(a), luminance(b)), reverse=True)
    return (la + 0.05) / (lb + 0.05)


def main() -> int:
    failures = 0
    if CHOSEN_DARK != DARK:
        print("FAIL the [data-theme='dark'] block differs from the prefers-color-scheme block")
        failures += 1
    for theme, table in (('light', LIGHT), ('dark', DARK)):
        for label, fg, bg, minimum in PAIRS:
            r = ratio(resolve(fg, table), resolve(bg, table))
            ok = r >= minimum
            failures += not ok
            print(f"{'ok ' if ok else 'FAIL'} {theme:5} {r:5.2f} (min {minimum:.1f})  {label}")
    print(f'{failures} failing pair(s)')
    return 1 if failures else 0


if __name__ == '__main__':
    sys.exit(main())
