"""Builds the self-hosted web fonts in public/fonts/portal/ (PLAN.md §9, §12).

- Inter (variable weight): the Latin and Latin Extended files from
  @fontsource-variable/inter, copied as they are.
- Noto Sans Ethiopic (variable weight): subset to the main Ethiopic block
  (U+1200-U+137F: every syllable, punctuation mark and numeral used by
  Amharic, Harari and Tigrinya) plus spaces, keeping all OpenType features.
- Space Grotesk (variable weight, display headings in Latin scripts): the Latin
  file as it is (ADR-0007).
- JetBrains Mono (variable weight, eyebrows, figures and codes): subset to Basic
  Latin, Latin-1 and the few arrows, bullets and dashes the labels use.

File names carry the font version, so they can be cached for a year. Run
after updating the font packages (bump VERSION) and commit the output:

    python scripts/fonts/build_fonts.py
"""
import shutil
from pathlib import Path

from fontTools import subset

VERSION = "v5"
ROOT = Path(__file__).resolve().parents[2]
PACKAGES = ROOT / "node_modules/@fontsource-variable"
TARGET = ROOT / "public/fonts/portal"
TARGET.mkdir(parents=True, exist_ok=True)

for part in ("latin", "latin-ext"):
    source = PACKAGES / f"inter/files/inter-{part}-wght-normal.woff2"
    target = TARGET / f"inter-{part}-wght-{VERSION}.woff2"
    shutil.copyfile(source, target)
    print(f"{target.relative_to(ROOT)}: {target.stat().st_size} bytes")

options = subset.Options()
options.flavor = "woff2"
options.layout_features = ["*"]
options.name_IDs = ["*"]
options.notdef_outline = True

source = PACKAGES / "noto-sans-ethiopic/files/noto-sans-ethiopic-ethiopic-wght-normal.woff2"
target = TARGET / f"noto-sans-ethiopic-wght-{VERSION}.woff2"
font = subset.load_font(str(source), options)
subsetter = subset.Subsetter(options)
subsetter.populate(unicodes=[0x0020, 0x00A0, *range(0x1200, 0x1380)])
subsetter.subset(font)
subset.save_font(font, str(target), options)
print(f"{target.relative_to(ROOT)}: {source.stat().st_size} -> {target.stat().st_size} bytes")

shutil.copyfile(PACKAGES / "space-grotesk/files/space-grotesk-latin-wght-normal.woff2",
                TARGET / f"space-grotesk-latin-wght-{VERSION}.woff2")
print(f"public/fonts/portal/space-grotesk-latin-wght-{VERSION}.woff2: "
      f"{(TARGET / f'space-grotesk-latin-wght-{VERSION}.woff2').stat().st_size} bytes")

source = PACKAGES / "jetbrains-mono/files/jetbrains-mono-latin-wght-normal.woff2"
target = TARGET / f"jetbrains-mono-latin-wght-{VERSION}.woff2"
font = subset.load_font(str(source), options)
subsetter = subset.Subsetter(options)
subsetter.populate(unicodes=[*range(0x0020, 0x007F), *range(0x00A0, 0x0100), 0x2013, 0x2014, 0x2022, 0x2026,
                             0x2190, 0x2191, 0x2192, 0x2193, 0x2197, 0x25CF, 0x25CB])
subsetter.subset(font)
subset.save_font(font, str(target), options)
print(f"{target.relative_to(ROOT)}: {source.stat().st_size} -> {target.stat().st_size} bytes")
