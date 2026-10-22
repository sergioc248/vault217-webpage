"""Pruebas de integración contra un servidor PHP real, sin dependencias externas."""

from contextlib import suppress
from hashlib import sha256
from http.cookiejar import CookieJar
import json
import os
from pathlib import Path
import re
import shutil
import socket
import subprocess
import tempfile
import time
import unittest
from urllib.error import HTTPError, URLError
from urllib.request import build_opener, HTTPCookieProcessor, Request


ROOT = Path(__file__).resolve().parents[1]
REPOSITORY = Path(os.environ.get("VAULT217_SOURCE_REPO", str(Path.home() / "Documents" / "Vault_Setup"))).expanduser()
FLAG_SOURCES = [
    "source/homes/maint217/flag1.txt",
    "source/homes/ncole/flag2.txt",
    "source/web/internal/cameras/archive.php",
    "source/mission4/emergency/AURORA_EMERGENCY/flag4.txt",
    "source/web/janus/override/index.php",
]
TITLES = ["Señal desde el Yermo", "El Supervisor", "Ecos del Vault", "Sombras de AURORA", "El Último Protocolo"]


class Browser:
    def __init__(self, base):
        self.base = base
        self.cookies = CookieJar()
        self.opener = build_opener(HTTPCookieProcessor(self.cookies))
        self.csrf = None

    def request(self, path="api.php", method="GET", body=None, raw=None):
        data = raw if raw is not None else json.dumps(body).encode() if body is not None else None
        req = Request(self.base + "/" + path, data=data, method=method, headers={"Content-Type": "application/json"})
        try:
            response = self.opener.open(req, timeout=5)
        except HTTPError as error:
            response = error
        text = response.read().decode()
        status, headers = response.status, response.headers
        response.close()
        try:
            payload = json.loads(text)
        except json.JSONDecodeError:
            payload = text
        if isinstance(payload, dict) and "csrf" in payload:
            self.csrf = payload["csrf"]
        return status, payload, headers

    def action(self, action, **fields):
        return self.request(method="POST", body={"action": action, "csrf": self.csrf, **fields})


class CompanionIntegration(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        external_url = os.environ.get("VAULT217_TEST_URL")
        php = shutil.which("php")
        if not external_url and not php:
            raise unittest.SkipTest("PHP no está instalado")
        if not external_url:
            required = tuple(int(part) for part in (ROOT / ".php-version").read_text().strip().split("."))
            minimum = required[0] * 10000 + required[1] * 100 + required[2]
            installed = int(subprocess.check_output([php, "-r", "echo PHP_VERSION_ID;"], text=True))
            if installed < minimum:
                raise RuntimeError("PHP es anterior a la versión fijada. Usa PHP 8.5.12 o dirige VAULT217_TEST_URL al contenedor actualizado.")
        cls.flags = []
        if not all((REPOSITORY / relative).is_file() for relative in FLAG_SOURCES):
            raise RuntimeError("Faltan los artefactos originales de la máquina. Define VAULT217_SOURCE_REPO con la ruta a Vault_Setup.")
        for relative in FLAG_SOURCES:
            content = (REPOSITORY / relative).read_text()
            if relative.endswith(".php"):
                values = re.findall(r"\b[A-Za-z][A-Za-z0-9_]*\{[A-Za-z0-9_-]+\}", content)
                if len(set(values)) != 1:
                    raise RuntimeError("La fuente canónica no contiene una flag inequívoca")
                content = values[0]
            cls.flags.append(content.strip())
        cls.server = None
        cls.temp = None
        if external_url:
            cls.base = external_url.rstrip("/")
            status, _, _ = Browser(cls.base).request()
            if status != 200:
                raise RuntimeError("El servidor de prueba no responde correctamente")
            return
        cls.temp = tempfile.TemporaryDirectory(prefix="vault217-test-")
        with socket.socket() as reservation:
            reservation.bind(("127.0.0.1", 0))
            port = reservation.getsockname()[1]
        cls.base = f"http://127.0.0.1:{port}"
        cls.server = subprocess.Popen(
            [php, "-S", f"127.0.0.1:{port}", "-t", str(ROOT / "public")],
            stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
            env={**os.environ, "VAULT217_SESSION_PATH": cls.temp.name},
        )
        for _ in range(60):
            try:
                Browser(cls.base).request()
                break
            except (URLError, ConnectionError):
                time.sleep(0.05)
        else:
            cls.server.terminate()
            cls.temp.cleanup()
            raise RuntimeError("No se pudo iniciar el servidor de prueba")

    @classmethod
    def tearDownClass(cls):
        if cls.server is None:
            return
        cls.server.terminate()
        with suppress(subprocess.TimeoutExpired):
            cls.server.wait(timeout=5)
        if cls.server.poll() is None:
            cls.server.kill()
        cls.temp.cleanup()

    def setUp(self):
        self.browser = Browser(self.base)
        self.browser.request()

    def assert_no_answers(self, data):
        text = json.dumps(data, ensure_ascii=False)
        for secret in self.flags:
            # No se imprime la respuesta esperada si una prueba falla.
            self.assertFalse(secret in text, "Una respuesta pública expone una flag")
            self.assertFalse(sha256(secret.encode()).hexdigest() in text, "Una respuesta pública expone un hash privado")

    def begin(self):
        status, data, _ = self.browser.action("begin")
        self.assertEqual(status, 200)
        return data

    def test_full_five_mission_journey_and_replay(self):
        data = self.begin()
        for mission in range(1, 6):
            status, data, _ = self.browser.request(f"api.php?mission={mission}")
            self.assertEqual(status, 200)
            self.assertEqual(data["mission"]["title"], TITLES[mission - 1])
            self.assert_no_answers(data)
            for future in data["missions"][mission:]:
                self.assertIsNone(future["title"])
                self.assertFalse(future["unlocked"])
            if mission < 5:
                status, _, _ = self.browser.action("flag", mission=mission + 1, flag=self.flags[mission])
                self.assertEqual(status, 403, "No debe saltarse una misión con una flag futura válida")
            status, _, _ = self.browser.action("flag", mission=mission, flag="evidencia-incorrecta")
            self.assertEqual(status, 422)
            status, data, _ = self.browser.action("flag", mission=mission, flag="  " + self.flags[mission - 1] + "\n")
            self.assertEqual(status, 200)
            self.assertEqual(data["completed"], mission)
            self.assertTrue(data["mission"]["completed"])
            self.assertTrue(data["mission"]["debrief"])
            self.assert_no_answers(data)
        self.assertIn("ending", data)
        _, resumed, _ = self.browser.request()
        self.assertEqual(resumed["completed"], 5)
        status, reviewed, _ = self.browser.request("api.php?mission=1")
        self.assertEqual(status, 200)
        self.assertTrue(reviewed["mission"]["completed"])
        self.assertEqual(reviewed["mission"]["title"], TITLES[0])
        _, replay, _ = self.browser.action("flag", mission=1, flag=self.flags[0])
        self.assertEqual(replay["completed"], 5, "Revisar una misión no debe reducir el avance")

    def test_locks_exist_on_server_and_hide_future_briefings(self):
        for mission in range(1, 6):
            self.assertEqual(self.browser.request(f"api.php?mission={mission}")[0], 403)
        data = self.begin()
        self.assert_no_answers(data)
        for mission in range(2, 6):
            self.assertEqual(self.browser.request(f"api.php?mission={mission}")[0], 403)
            self.assertEqual(self.browser.action("hint", mission=mission)[0], 403)
        self.assertNotIn("ending", data)

    def test_hints_are_optional_persistent_and_scoped(self):
        data = self.begin()
        self.assertIsNone(data["mission"]["hint"])
        _, hinted, _ = self.browser.action("hint", mission=1)
        self.assertTrue(hinted["mission"]["hint"])
        self.assertEqual(hinted["hintsUsed"], 1)
        _, repeated, _ = self.browser.action("hint", mission=1)
        self.assertEqual(repeated["hintsUsed"], 1)
        _, resumed, _ = self.browser.request()
        self.assertEqual(resumed["mission"]["hint"], hinted["mission"]["hint"])

    def test_browser_isolation_resume_and_reset(self):
        other = Browser(self.base)
        other.request()
        other.action("begin")
        self.begin()
        self.browser.action("hint", mission=1)
        self.browser.action("flag", mission=1, flag=self.flags[0])
        _, own, _ = self.browser.request()
        _, separate, _ = other.request()
        self.assertEqual(own["completed"], 1)
        self.assertEqual(separate["completed"], 0)
        self.assertIsNone(separate["mission"]["hint"])
        _, reset, _ = self.browser.action("reset")
        self.assertFalse(reset["started"])
        self.assertEqual(reset["completed"], 0)
        self.assertEqual(reset["hintsUsed"], 0)
        _, separate, _ = other.request()
        self.assertTrue(separate["started"])
        self.assertEqual(self.browser.request("api.php?mission=2")[0], 403)

    def test_invalid_requests_csrf_and_input_boundaries(self):
        self.assertEqual(self.browser.request(method="POST", body={"action": "begin"})[0], 403)
        self.assertEqual(self.browser.request(method="POST", raw=b"{")[0], 400)
        self.assertEqual(self.browser.request(method="POST", raw=b"x" * 4097)[0], 413)
        self.assertEqual(self.browser.request(method="DELETE")[0], 405)
        self.assertEqual(self.browser.action("unknown")[0], 400)
        self.begin()
        for value in [0, 6, -1, "1", True, [], None]:
            self.assertEqual(self.browser.action("hint", mission=value)[0], 400)
        for value in ["", " ", "x" * 257, 123, []]:
            self.assertEqual(self.browser.action("flag", mission=1, flag=value)[0], 400)
        self.assertEqual(self.browser.request("api.php?mission[]=1")[0], 400)

    def test_excessive_submission_throttle(self):
        self.begin()
        for _ in range(12):
            self.assertEqual(self.browser.action("flag", mission=1, flag="incorrecta")[0], 422)
        status, _, headers = self.browser.action("flag", mission=1, flag="incorrecta")
        self.assertEqual(status, 429)
        self.assertTrue(0 < int(headers["Retry-After"]) <= 60)

    def test_home_and_mission_routes_preserve_progress(self):
        self.begin()
        self.browser.action("flag", mission=1, flag=self.flags[0])
        for path, page in [("", "home"), ("misiones/", "missions")]:
            status, html, _ = self.browser.request(path)
            self.assertEqual(status, 200)
            self.assertIn(f'data-page="{page}"', html)
            self.assertIn('href="/"', html)
            self.assertIn('href="/misiones/"', html)
            self.assertIn('src="/assets/app.js"', html)
            self.assertIn('href="/assets/style.css"', html)
            self.assert_no_answers(html)
            _, state, _ = self.browser.request()
            self.assertEqual(state["completed"], 1)
            self.assertTrue(state["started"])
        self.assertNotIn('id="intro"', self.browser.request("misiones/")[1])
        self.assertNotIn('id="operation"', self.browser.request("")[1])

    def test_private_files_and_client_assets_do_not_expose_answers(self):
        for path in ["app/content.php", "app/flag-hashes.php", "var/sessions/", ".scratch/"]:
            self.assertEqual(self.browser.request(path)[0], 404)
        # Apache rechaza la ruta ascendente antes de PHP; el servidor CLI devuelve 404.
        self.assertIn(self.browser.request("../app/flag-hashes.php")[0], (400, 404))
        for path in ["", "assets/app.js", "assets/style.css"]:
            status, data, _ = self.browser.request(path)
            self.assertEqual(status, 200)
            self.assert_no_answers(data)
        _, _, headers = self.browser.request("")
        self.assertIn("default-src 'self'", headers["Content-Security-Policy"])
        _, _, headers = self.browser.request()
        self.assertIn("HttpOnly", headers["Set-Cookie"])
        self.assertIn("SameSite=Lax", headers["Set-Cookie"])
        self.assertIn("no-store", headers["Cache-Control"])


if __name__ == "__main__":
    unittest.main(verbosity=2)
