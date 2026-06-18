#!/usr/bin/env python3
import os, sys, html, re
from pathlib import Path
from urllib.parse import urljoin, urlparse

VERSION = "1.0.185"
BASE_URL = os.environ.get("SGCE_VISUAL_BASE_URL", "http://127.0.0.1:8080").rstrip("/") + "/"
OUT_DIR = Path(os.environ.get("SGCE_VISUAL_OUT_DIR", "tests/visual-mobile-captures"))
AUTH_TOKEN = os.environ.get("SGCE_VISUAL_AUTH_TOKEN") or os.environ.get("SGCE_VISUAL_COOKIE", "")
LOGIN_USER = os.environ.get("SGCE_VISUAL_LOGIN_USER", "")
LOGIN_PASS = os.environ.get("SGCE_VISUAL_LOGIN_PASSWORD", "")
HEADLESS = os.environ.get("SGCE_VISUAL_HEADLESS", "1") != "0"

PAGES = [
    ("index.php", "login-publico"),
    ("ConsultaPadre.php", "consulta-asistencia"),
    ("ConsultaCalificaciones.php", "consulta-calificaciones"),
    ("Admin.php?Tab=inicio", "admin-inicio"),
    ("Admin.php?Tab=maestros", "admin-maestros"),
    ("Admin.php?Tab=grupos", "admin-grupos"),
    ("Admin.php?Tab=alumnos", "admin-alumnos"),
    ("Admin.php?Tab=materias", "admin-materias"),
    ("Admin.php?Tab=asignaciones", "admin-asignaciones"),
    ("PeriodosAdmin.php", "periodos"),
    ("ReportesAdmin.php", "reportes"),
    ("UsuariosAdmin.php", "usuarios"),
    ("RespaldoBD.php", "respaldos"),
    ("RestaurarBD.php", "restaurar"),
    ("Maestro.php", "maestro"),
    ("Asistencia.php", "asistencia-docente"),
    ("Calificar.php", "calificaciones-docente"),
]

VIEWPORTS = [
    (390, 844, "celular"),
    (430, 932, "celular-grande"),
    (768, 1024, "tablet"),
    (1366, 768, "escritorio"),
]

def parse_auth_cookie(value: str):
    value = value.strip()
    if not value:
        return None
    if "AuthToken=" in value:
        m = re.search(r"AuthToken=([^;\s]+)", value)
        if m:
            return m.group(1)
    if "=" in value and ";" not in value:
        name, val = value.split("=", 1)
        if name.strip() == "AuthToken":
            return val.strip()
    return value

def base_cookie_path(parsed):
    path = parsed.path or "/"
    path = "/" + path.strip("/")
    return "/" if path == "/" else path + "/"

def write_report_start(report):
    report.write_text(
        '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        f'<title>SGCE {VERSION} - Reporte visual móvil</title>'
        '<style>body{font-family:Arial,sans-serif;background:#f6f7fb;color:#172033;margin:24px}h1{font-size:24px}.notice{padding:12px 14px;border-radius:12px;background:#fff7ed;border:1px solid #fed7aa;margin:12px 0}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px}.card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:12px;box-shadow:0 10px 24px rgba(15,23,42,.08)}img{width:100%;border-radius:10px;border:1px solid #e5e7eb}code{font-size:12px;word-break:break-all}</style></head><body>'
        f'<h1>SGCE {VERSION} - Reporte visual móvil</h1>'
        '<p>Revisar: sin scroll horizontal, botones a ancho completo en móvil, hero público en orden correcto y tablas sin desbordar.</p>'
        + ('' if (AUTH_TOKEN or (LOGIN_USER and LOGIN_PASS)) else '<div class="notice"><strong>Aviso:</strong> No se proporcionó sesión. Las páginas administrativas pueden capturarse como login o acceso denegado. Usa SGCE_VISUAL_AUTH_TOKEN o SGCE_VISUAL_LOGIN_USER/SGCE_VISUAL_LOGIN_PASSWORD para sesión real.</div>')
        + '<div class="grid">',
        encoding='utf-8'
    )

def append_card(report, label, width, height, page, filename):
    with report.open('a', encoding='utf-8') as f:
        f.write(f'<article class="card"><h2>{html.escape(label)} · {width}x{height}</h2><p><code>{html.escape(page)}</code></p><img src="{html.escape(filename)}" alt="{html.escape(page)}"></article>\n')

def main():
    try:
        from playwright.sync_api import sync_playwright, TimeoutError as PlaywrightTimeoutError
    except Exception as exc:
        print("No se encontró Playwright para Python. Instala/activa playwright o ejecuta en Desarrollo con el entorno de pruebas.", file=sys.stderr)
        print(str(exc), file=sys.stderr)
        return 2

    OUT_DIR.mkdir(parents=True, exist_ok=True)
    report = OUT_DIR / "reporte-visual.html"
    write_report_start(report)
    parsed = urlparse(BASE_URL)
    cookie_value = parse_auth_cookie(AUTH_TOKEN)

    print(f"SGCE {VERSION} - Capturas visuales móviles")
    print(f"Base URL: {BASE_URL}")
    print(f"Salida: {OUT_DIR}")

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=HEADLESS)
        context = browser.new_context(ignore_https_errors=True)
        if cookie_value:
            context.add_cookies([{
                "name": "AuthToken",
                "value": cookie_value,
                "domain": parsed.hostname or "127.0.0.1",
                "path": base_cookie_path(parsed),
                "httpOnly": True,
                "secure": parsed.scheme == "https",
                "sameSite": "Strict",
            }])
        page = context.new_page()
        if LOGIN_USER and LOGIN_PASS:
            try:
                page.goto(urljoin(BASE_URL, "index.php"), wait_until="networkidle", timeout=30000)
                page.fill('input[name="Username"]', LOGIN_USER)
                page.fill('input[name="Password"]', LOGIN_PASS)
                page.click('button[type="submit"], input[type="submit"]')
                page.wait_for_load_state("networkidle", timeout=30000)
                print("Sesión autenticada con usuario visual.")
            except Exception as exc:
                print(f"Aviso: no fue posible autenticar con formulario: {exc}", file=sys.stderr)
        for width, height, label in VIEWPORTS:
            page.set_viewport_size({"width": width, "height": height})
            for rel, slug in PAGES:
                url = urljoin(BASE_URL, rel)
                out = OUT_DIR / f"{width}x{height}_{slug}.png"
                try:
                    page.goto(url, wait_until="networkidle", timeout=30000)
                    page.screenshot(path=str(out), full_page=True)
                except Exception as exc:
                    print(f"Aviso: fallo captura {url}: {exc}", file=sys.stderr)
                    try:
                        page.screenshot(path=str(out), full_page=True)
                    except Exception:
                        pass
                print(f"Captura: {out}")
                append_card(report, label, width, height, rel, out.name)
        browser.close()
    with report.open('a', encoding='utf-8') as f:
        f.write('</div></body></html>')
    print(f"Reporte: {report}")
    print("Listo. Abre el HTML y revisa visualmente las capturas.")
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
