"""npm audit for CI with a short list of accepted advisories (ADR-0036).

Fails when any high or critical advisory is found that is not in scripts/npm-audit-accepted.json. Each accepted
entry names the advisory, why it is accepted and when to look again; an entry that npm no longer reports is
flagged so it can be removed. The weekly security audit still runs the plain `npm audit`, so accepted advisories
stay visible there.

Usage: python3 scripts/npm-audit.py
"""
import json
import shutil
import subprocess
import sys
from datetime import date
from pathlib import Path

LEVELS = {"high", "critical"}
root = Path(__file__).resolve().parent.parent
accepted = {entry["id"]: entry for entry in json.loads((root / "scripts" / "npm-audit-accepted.json").read_text(encoding="utf-8"))}

result = subprocess.run([shutil.which("npm") or "npm", "audit", "--json"], cwd=root, capture_output=True, text=True)
try:
    report = json.loads(result.stdout or "{}")
except json.JSONDecodeError:
    print(result.stdout, result.stderr, sep="\n")
    sys.exit("npm audit did not answer with JSON")

found = {}
for name, vulnerability in (report.get("vulnerabilities") or {}).items():
    for via in vulnerability.get("via", []):
        if isinstance(via, dict) and via.get("severity") in LEVELS:
            advisory = str(via.get("url", "")).rstrip("/").rsplit("/", 1)[-1]
            found.setdefault(advisory, {"title": via.get("title", ""), "severity": via["severity"], "packages": set()})["packages"].add(name)

blocking = {advisory: info for advisory, info in found.items() if advisory not in accepted}
for advisory, info in sorted(found.items()):
    mark = "ACCEPTED" if advisory in accepted else "BLOCKING"
    print(f"{mark} {advisory} ({info['severity']}): {info['title']} in {', '.join(sorted(info['packages']))}")
for advisory, entry in sorted(accepted.items()):
    if advisory not in found:
        print(f"NOTE {advisory} is no longer reported: remove it from scripts/npm-audit-accepted.json")
    elif date.fromisoformat(entry["review_by"]) < date.today():
        print(f"NOTE {advisory} passed its review date {entry['review_by']}: check for a fixed release")

if blocking:
    sys.exit(f"{len(blocking)} high or critical advisory(ies) not accepted")
print("npm audit: no blocking advisories")
