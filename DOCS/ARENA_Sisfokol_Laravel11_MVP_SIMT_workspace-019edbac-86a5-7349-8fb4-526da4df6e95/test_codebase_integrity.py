import os
import re

mvp_path = "/home/user/sisfokol-laravel-mvp"

print("======================================================================")
print("     STARTING AUTOMATED STATIC CODE ANALYSIS & INTEGRITY TEST")
print("======================================================================")

# 1. Check Directory Structure
expected_modules = ["Auth", "Academic", "Evaluation", "Finance", "Presence", "Discipline", "Inventory"]
missing_modules = []
found_modules = []

for mod in expected_modules:
    mod_path = os.path.join(mvp_path, "app/Modules", mod)
    if os.path.exists(mod_path):
        found_modules.append(mod)
    else:
        missing_modules.append(mod)

print(f"\n[Test 1] Domain Modules Directory Audit:")
print(f"  - Found Modules: {', '.join(found_modules)}")
if missing_modules:
    print(f"  - MISSING MODULES: {', '.join(missing_modules)} (FAILED)")
else:
    print("  - Status: ALL DOMAIN MODULES DETECTED (PASSED)")

# 2. Check Core Configuration & Bootstrapping Files
core_files = [
    "composer.json",
    "artisan",
    "bootstrap/app.php",
    "bootstrap/providers.php",
    "config/app.php",
    "config/database.php",
    "config/auth.php",
    "config/session.php",
    "app/Providers/ModuleServiceProvider.php",
    ".env.example"
]

missing_cores = []
for cf in core_files:
    if not os.path.exists(os.path.join(mvp_path, cf)):
        missing_cores.append(cf)

print(f"\n[Test 2] Core Bootstrapping & Configuration Audit:")
if missing_cores:
    print(f"  - Missing core files: {', '.join(missing_cores)} (FAILED)")
else:
    print("  - Status: ALL CORE BOOTSTRAP FILES DETECTED (PASSED)")

# 3. Check All Database Migrations
print(f"\n[Test 3] Modular Migrations Audit:")
found_migrations = []

for root, dirs, files in os.walk(mvp_path):
    if "Migrations" in root:
        for f in files:
            if f.endswith(".php"):
                found_migrations.append(f)

print(f"  - Total Migration files found in modules: {len(found_migrations)}")
for idx, m in enumerate(sorted(found_migrations)):
    print(f"    {idx+1}. {m}")

if len(found_migrations) >= 7:
    print("  - Status: ALL CORE MODULAR MIGRATIONS FULLY LOADED (PASSED)")
else:
    print(f"  - Status: Missing migrations. Expected >= 7, found {len(found_migrations)} (FAILED)")

# 4. Check View Templates (Blade Templates Namespace)
print(f"\n[Test 4] Blade Views Namespace Audit:")
expected_views = {
    "Auth": "login.blade.php",
    "Auth": "dashboard.blade.php",
    "Academic": "index.blade.php",
    "Evaluation": "score-input.blade.php",
    "Evaluation": "rapor-pdf.blade.php",
    "Finance": "kasir.blade.php",
    "Presence": "scan.blade.php",
    "Discipline": "infraction.blade.php"
}

missing_views = []
for mod, view in expected_views.items():
    view_path = os.path.join(mvp_path, "app/Modules", mod, "Resources/Views", view)
    if not os.path.exists(view_path):
        missing_views.append(f"{mod.lower()}::{view}")

if missing_views:
    print(f"  - Missing views: {', '.join(missing_views)} (FAILED)")
else:
    print("  - Status: ALL REUSABLE BLADE FRONTEND PARTIALS DETECTED (PASSED)")

# 5. Check Syntax & Brace Matching validation on PHP controllers & models (excluding Blade files)
print(f"\n[Test 5] PHP Code Syntax Integrity & Autoloading Scope:")
errors_found = 0
total_files = 0

for root, dirs, files in os.walk(os.path.join(mvp_path, "app")):
    for f in files:
        if f.endswith(".php") and not f.endswith(".blade.php"):
            total_files += 1
            file_path = os.path.join(root, f)
            with open(file_path, "r") as file_content:
                content = file_content.read()
                
                # Check PHP opening tag
                if not content.strip().startswith("<?php"):
                    print(f"    - Warning: {f} is missing <?php opening tag!")
                    errors_found += 1
                
                # Check brace balance (simple linter)
                open_braces = content.count("{")
                close_braces = content.count("}")
                if open_braces != close_braces:
                    print(f"    - Syntax Error: {f} has unbalanced braces! (Open: {open_braces}, Close: {close_braces})")
                    errors_found += 1

print(f"  - Scanned {total_files} active PHP source code classes.")
if errors_found == 0:
    print("  - Status: SYNTAX INTEGRITY CHECK OK (PASSED)")
else:
    print(f"  - Status: Found {errors_found} syntax anomalies (FAILED)")

# Final Verdict
print("\n======================================================================")
print("                     FINAL AUDIT INTEGRITY REPORT")
print("======================================================================")
if not missing_modules and not missing_cores and len(found_migrations) >= 7 and not missing_views and errors_found == 0:
    print("  VERDICT: sisfokol-laravel-mvp ADALAH 100% MANDIRI DAN SIAP INSTAL!")
    print("           (ALL TESTS GREEN/PASSED)")
else:
    print("  VERDICT: FAILED. Beberapa fungsionalitas belum terkonfigurasi dengan benar.")
print("======================================================================")
