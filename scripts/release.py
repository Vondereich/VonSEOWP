import json
import os
import re
import shutil
import zipfile


PLUGIN_SLUG = "vonseo"
DIST_DIR = "dist"
INCLUDE_DIRS = ["admin", "includes", "assets", "languages", "public"]
INCLUDE_FILES = [
    "vonseo.php",
    "readme.txt",
    "index.php",
    "LICENSE",
    "README.md",
    "CHANGELOG.md",
    "uninstall.php",
]


def read_text(path):
    with open(path, "r", encoding="utf-8") as file_handle:
        return file_handle.read()


def get_version():
    match = re.search(r"Version:\s*([\d.]+)", read_text("vonseo.php"))
    if not match:
        raise RuntimeError("Plugin version was not found in vonseo.php")
    return match.group(1)


def validate_metadata(version):
    plugin_source = read_text("vonseo.php")
    constant_match = re.search(r"define\(\s*['\"]VONSEOWP_VERSION['\"]\s*,\s*['\"]([\d.]+)['\"]\s*\)", plugin_source)
    constant_version = constant_match.group(1) if constant_match else ""

    with open("package.json", "r", encoding="utf-8") as package_file:
        package_version = str(json.load(package_file).get("version", ""))

    stable_match = re.search(r"^Stable tag:\s*([\d.]+)\s*$", read_text("readme.txt"), re.MULTILINE)
    stable_version = stable_match.group(1) if stable_match else ""

    plugin_tested_match = re.search(r"Tested up to:\s*([\d.]+)", plugin_source)
    plugin_tested_version = plugin_tested_match.group(1) if plugin_tested_match else ""
    readme_tested_match = re.search(r"^Tested up to:\s*([\d.]+)\s*$", read_text("readme.txt"), re.MULTILINE)
    readme_tested_version = readme_tested_match.group(1) if readme_tested_match else ""

    changelog_has_version = f"## [{version}]" in read_text("CHANGELOG.md")
    mismatches = []
    if constant_version != version:
        mismatches.append(f"VONSEOWP_VERSION={constant_version or 'missing'}")
    if package_version != version:
        mismatches.append(f"package.json={package_version or 'missing'}")
    if stable_version != version:
        mismatches.append(f"readme.txt={stable_version or 'missing'}")
    if not plugin_tested_version:
        mismatches.append("vonseo.php Tested up to missing")
    if not readme_tested_version:
        mismatches.append("readme.txt Tested up to missing")
    if plugin_tested_version and readme_tested_version and plugin_tested_version != readme_tested_version:
        mismatches.append(
            f"Tested up to mismatch: vonseo.php={plugin_tested_version}, readme.txt={readme_tested_version}"
        )
    if not changelog_has_version:
        mismatches.append("CHANGELOG.md entry missing")

    if mismatches:
        raise RuntimeError("Release metadata mismatch: " + ", ".join(mismatches))


def validate_release_tag(version):
    release_tag = os.environ.get("GITHUB_REF_NAME", "")
    if release_tag.startswith("v") and release_tag != f"v{version}":
        raise RuntimeError(f"Git tag {release_tag} does not match plugin version v{version}")


def collect_source_files():
    source_files = {}

    for folder in INCLUDE_DIRS:
        folder_path = os.path.join(".", folder)
        if not os.path.isdir(folder_path):
            raise RuntimeError(f"Required package directory is missing: {folder}")

        for root, _, files in os.walk(folder_path):
            for filename in files:
                file_path = os.path.join(root, filename)
                relative_path = os.path.relpath(file_path, ".").replace(os.sep, "/")
                source_files[relative_path] = file_path

    for filename in INCLUDE_FILES:
        if not os.path.isfile(filename):
            raise RuntimeError(f"Required package file is missing: {filename}")
        source_files[filename] = filename

    return source_files


def validate_runtime_dependencies(source_files):
    for asset in ("public/css/vonseowp-public.css", "public/js/vonseowp-public.js"):
        if asset not in source_files:
            raise RuntimeError(f"Required TOC asset is missing: {asset}")
    dependency_pattern = re.compile(
        r"require(?:_once)?\s+VONSEOWP_PATH\s*\.\s*['\"]([^'\"]+)['\"]"
    )

    for relative_path, file_path in source_files.items():
        if not relative_path.endswith(".php"):
            continue

        for dependency in dependency_pattern.findall(read_text(file_path)):
            normalized_dependency = dependency.replace("\\", "/")
            if normalized_dependency not in source_files:
                raise RuntimeError(
                    f"Runtime dependency {normalized_dependency} required by {relative_path} is missing"
                )


def remove_readonly(func, path, excinfo):
    """WinError 5 handler for shutil.rmtree."""
    import stat

    os.chmod(path, stat.S_IWRITE)
    func(path)


def clean_output(zip_filename):
    for filename in os.listdir("."):
        if filename.startswith(f"{PLUGIN_SLUG}-v") and filename.endswith(".zip"):
            os.remove(filename)
            print(f"   Deleted: {filename}")

    if os.path.exists(DIST_DIR):
        shutil.rmtree(DIST_DIR, onerror=remove_readonly)

    os.makedirs(os.path.join(DIST_DIR, PLUGIN_SLUG), exist_ok=True)
    if os.path.exists(zip_filename):
        os.remove(zip_filename)


def stage_files(staging_dir):
    for folder in INCLUDE_DIRS:
        if os.path.exists(folder):
            shutil.copytree(folder, os.path.join(staging_dir, folder))

    for filename in INCLUDE_FILES:
        if os.path.exists(filename):
            shutil.copy2(filename, staging_dir)


def create_archive(staging_dir, zip_filename):
    with zipfile.ZipFile(zip_filename, "w", zipfile.ZIP_DEFLATED) as archive:
        for root, _, files in os.walk(staging_dir):
            for filename in files:
                file_path = os.path.join(root, filename)
                archive_name = os.path.relpath(file_path, DIST_DIR).replace(os.sep, "/")
                archive.write(file_path, archive_name)


def verify_archive(zip_filename, version, expected_members):
    required_main_file = f"{PLUGIN_SLUG}/vonseo.php"
    forbidden_parts = {".agent", ".git", ".github", "node_modules", "scripts", "docs"}

    with zipfile.ZipFile(zip_filename, "r") as archive:
        members = [name for name in archive.namelist() if not name.endswith("/")]
        member_set = set(members)
        if not members or required_main_file not in members:
            raise RuntimeError(f"Archive is missing {required_main_file}")
        if any(not name.startswith(f"{PLUGIN_SLUG}/") for name in members):
            raise RuntimeError(f"Archive must have one top-level {PLUGIN_SLUG}/ directory")
        if any(forbidden_parts.intersection(name.split("/")) for name in members):
            raise RuntimeError("Archive contains development-only files")
        if member_set != expected_members:
            missing = sorted(expected_members - member_set)
            unexpected = sorted(member_set - expected_members)
            details = []
            if missing:
                details.append("missing=" + ", ".join(missing))
            if unexpected:
                details.append("unexpected=" + ", ".join(unexpected))
            raise RuntimeError("Archive manifest mismatch: " + "; ".join(details))

        main_source = archive.read(required_main_file).decode("utf-8")
        if f"Version:           {version}" not in main_source:
            raise RuntimeError("Archive plugin version does not match the release version")

    print(f"Verified {len(members)} packaged files under {PLUGIN_SLUG}/")


def main():
    version = get_version()
    validate_metadata(version)
    validate_release_tag(version)
    source_files = collect_source_files()
    validate_runtime_dependencies(source_files)

    zip_filename = f"{PLUGIN_SLUG}-v{version}.zip"
    staging_dir = os.path.join(DIST_DIR, PLUGIN_SLUG)
    expected_members = {f"{PLUGIN_SLUG}/{path}" for path in source_files}

    print(f"Starting build for {PLUGIN_SLUG} v{version}...")
    print("Cleaning old release output...")
    clean_output(zip_filename)

    print("Staging files...")
    stage_files(staging_dir)

    print(f"Creating {zip_filename}...")
    create_archive(staging_dir, zip_filename)
    verify_archive(zip_filename, version, expected_members)

    print("Build successful!")
    print(f"   - Release file: {os.path.abspath(zip_filename)}")
    print(f"   - Dashboard-safe folder: {PLUGIN_SLUG}/")


if __name__ == "__main__":
    main()
