"""Exercise release switching with isolated filesystem and server command doubles."""

import fcntl
import io
import os
from pathlib import Path
import subprocess
import tarfile
import tempfile
import unittest


class DeploymentTest(unittest.TestCase):
    def test_release_activation_and_failure_recovery(self):
        source = (Path(__file__).resolve().parents[1] / "deploy.sh").read_text()
        scenarios = [
            ("first release", False, False, False, False, False),
            ("upgrade", True, False, False, False, False),
            ("migration failure", True, False, True, False, False),
            ("health failure restores previous", True, True, False, False, False),
            ("first release health failure", False, True, False, False, False),
            ("missing environment", True, False, False, True, False),
            ("concurrent deployment", True, False, False, False, True),
        ]
        for name, previous, health, migration, missing_env, locked in scenarios:
            with self.subTest(name=name), tempfile.TemporaryDirectory() as tmp:
                base = Path(tmp)
                stubs = base / "bin"
                stubs.mkdir()
                for command, body in {
                    "php": 'if [[ "${2:-}" == migrate && "${FAIL_MIGRATION:-0}" == 1 ]]; then exit 42; fi\nexit 0\n',
                    "curl": 'exit "${FAIL_HEALTH:-0}"\n',
                    "chgrp": "exit 0\n",
                }.items():
                    file = stubs / command
                    file.write_text("#!/bin/bash\n" + body)
                    file.chmod(0o755)

                for folder in ["shared", "releases", "incoming"]:
                    (base / folder).mkdir()
                if not missing_env:
                    (base / "shared/.env").write_text("APP_ENV=staging\n")
                old = base / "releases/previous"
                if previous:
                    old.mkdir()
                    (old / "artisan").touch()
                    (base / "current").symlink_to(old)

                release_id = "a" * 40 + "-123-1"
                archive = base / "incoming" / (release_id + ".tar.gz")
                with tarfile.open(archive, "w:gz") as tar:
                    for directory in ["storage", "bootstrap", "bootstrap/cache", "public", "vendor"]:
                        entry = tarfile.TarInfo(directory)
                        entry.type = tarfile.DIRTYPE
                        entry.mode = 0o755
                        tar.addfile(entry)
                    for filename in ["artisan", "vendor/autoload.php"]:
                        entry = tarfile.TarInfo(filename)
                        data = b"<?php\n"
                        entry.size = len(data)
                        tar.addfile(entry, io.BytesIO(data))

                script = base / "deploy.sh"
                script.write_text(
                    source.replace("base=/var/www/ai-hiring-dev", "base=" + str(base))
                    .replace("/usr/bin/php8.4", str(stubs / "php"))
                )
                env = dict(
                    os.environ,
                    PATH=str(stubs) + ":" + os.environ["PATH"],
                    FAIL_HEALTH="22" if health else "0",
                    FAIL_MIGRATION="1" if migration else "0",
                )
                with (base / "deploy.lock").open("w") as lock_file:
                    if locked:
                        fcntl.flock(lock_file, fcntl.LOCK_EX | fcntl.LOCK_NB)
                    result = subprocess.run(
                        ["bash", str(script), release_id, "https://dev.example.com/up"],
                        env=env, capture_output=True, text=True, timeout=10,
                    )

                failed = health or migration or missing_env or locked
                if failed:
                    self.assertNotEqual(result.returncode, 0, result.stdout)
                    if previous:
                        self.assertEqual((base / "current").resolve(), old)
                    else:
                        self.assertFalse((base / "current").is_symlink())
                    self.assertTrue(archive.exists())
                else:
                    self.assertEqual(result.returncode, 0, result.stderr)
                    release = base / "releases" / release_id
                    self.assertEqual((base / "current").resolve(), release)
                    self.assertEqual((release / "storage").resolve(), base / "shared/storage")
                    self.assertEqual((release / ".env").resolve(), base / "shared/.env")
                    self.assertFalse(archive.exists())


if __name__ == "__main__":
    unittest.main()
