#!/usr/bin/env bash
# Refactor invariants (PRD §13.14). Exits non-zero on a regression.
#
# These are the properties that make the A-01 / A-02 / B-06 class of bug
# structurally impossible: no duplicated pagination, no duplicated helpers, no
# hardcoded host or role, no CSS pasted back into a template.
set -u
cd "$(dirname "$0")/.." || exit 1

fail=0
chk() { # name, actual, max
  if [ "$2" -gt "$3" ]; then
    printf 'FAIL %-38s %s (max %s)\n' "$1" "$2" "$3"; fail=1
  else
    printf 'ok   %-38s %s\n' "$1" "$2"
  fi
}

inline_css=0
for f in templates/page-*.php; do
  inline_css=$((inline_css + $(awk '/<style>/,/<\/style>/' "$f" | wc -l)))
done
chk "inline CSS in templates"        "$inline_css" 0
chk "lines in templates/"            "$(cat templates/page-*.php | wc -l)" 12000
chk "copies of pagination markup"    "$(grep -l 'gti-ue-page-btn' templates/*.php 2>/dev/null | wc -l)" 0
chk "copies of showUeToast()"        "$(grep -l 'function showUeToast' templates/*.php 2>/dev/null | wc -l)" 0
chk "copies of formatDateID()"       "$(grep -l 'function formatDateID' templates/*.php 2>/dev/null | wc -l)" 0
chk "copies of closeDetailDrawer()"  "$(grep -l 'function closeDetailDrawer' templates/*.php 2>/dev/null | wc -l)" 0
chk "hardcoded .test URLs"           "$(grep -rl 'global-tractors.test' templates/ inc/ includes/ 2>/dev/null | wc -l)" 0
chk "hardcoded 'Super Admin'"        "$(grep -rl '<small>Super Admin</small>' templates/ template-parts/ 2>/dev/null | wc -l)" 0
chk "reserved query var 'paged'"     "$(grep -rl "_GET\['paged'\]" templates/ inc/ 2>/dev/null | wc -l)" 0
chk "native alert()/confirm()"       "$(grep -rlE '(^|[^.\w])(alert|confirm)\(' templates/*.php 2>/dev/null | wc -l)" 0
chk "debug log writes"               "$(grep -rlE "file_put_contents\\([^)]*(/tmp/|debug[^)]*\\.log)" inc/ includes/ functions.php 2>/dev/null | wc -l)" 0
chk "inline <script> in templates"   "$(grep -lE '^[[:space:]]*<script' templates/page-*.php 2>/dev/null | grep -vE 'page-(login|register|forgot-password)' | wc -l)" 0

# Duplicate name= within one form is what silently dropped Step 1 values (A-02).
if command -v php >/dev/null 2>&1; then
  if form_out=$(php tests/check-form-fields.php 2>&1); then
    printf 'ok   %-38s %s\n' "duplicate form field names" 0
  else
    echo "$form_out"
    printf 'FAIL %-38s\n' "duplicate form field names"; fail=1
  fi
fi

# Every PHP file must parse.
if command -v php >/dev/null 2>&1; then
  bad=0
  while IFS= read -r f; do
    php -l "$f" >/dev/null 2>&1 || { echo "  syntax error: $f"; bad=$((bad+1)); }
  done < <(find . -name '*.php' -not -path './.git/*')
  chk "PHP syntax errors" "$bad" 0
fi

exit $fail
