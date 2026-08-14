"""Generate web + mobile brand assets from Shop3DPrinting source images."""
from __future__ import annotations

from pathlib import Path

from PIL import Image

WEB_SRC = Path(r"C:\Users\donpv\Desktop\MOHINH3D\Shop3DPrinting.png")
APP_SRC = Path(r"C:\Users\donpv\Desktop\MOHINH3D\Shop3DPrinting-1.png")
PROJ = Path(r"C:\Users\donpv\Desktop\quanlybanhang")


def trim_to_content(im: Image.Image, bg_threshold: int = 245, pad: int = 24) -> Image.Image:
    """Remove near-white margins and keep content with padding."""
    rgba = im.convert("RGBA")
    datas = rgba.getdata()
    new = []
    for r, g, b, a in datas:
        if r >= bg_threshold and g >= bg_threshold and b >= bg_threshold:
            new.append((r, g, b, 0))
        else:
            new.append((r, g, b, 255 if a > 0 else a))
    rgba.putdata(new)
    bbox = rgba.getbbox()
    if not bbox:
        return im.convert("RGBA")
    left, top, right, bottom = bbox
    left = max(0, left - pad)
    top = max(0, top - pad)
    right = min(rgba.width, right + pad)
    bottom = min(rgba.height, bottom + pad)
    return rgba.crop((left, top, right, bottom))


def square_canvas(im: Image.Image, size: int, bg=(0, 0, 0, 0)) -> Image.Image:
    """Fit image into a square size preserving aspect ratio."""
    im = im.convert("RGBA")
    ratio = min(size / im.width, size / im.height)
    nw = max(1, int(im.width * ratio))
    nh = max(1, int(im.height * ratio))
    resized = im.resize((nw, nh), Image.Resampling.LANCZOS)
    canvas = Image.new("RGBA", (size, size), bg)
    canvas.paste(resized, ((size - nw) // 2, (size - nh) // 2), resized)
    return canvas


def main() -> None:
    logo_dir = PROJ / "public" / "images" / "logo"
    logo_dir.mkdir(parents=True, exist_ok=True)
    storage_brand = PROJ / "storage" / "app" / "public" / "branding"
    storage_brand.mkdir(parents=True, exist_ok=True)

    web = Image.open(WEB_SRC)
    app = Image.open(APP_SRC)
    web_trim = trim_to_content(web, pad=40)
    app_trim = trim_to_content(app, pad=40)
    print("web trim", web_trim.size, "app trim", app_trim.size)

    # Web header logo
    web_logo = web_trim.copy()
    h = 256
    ratio = h / web_logo.height
    web_logo = web_logo.resize(
        (max(1, int(web_logo.width * ratio)), h), Image.Resampling.LANCZOS
    )
    web_logo_path = logo_dir / "shop3dprinting-logo.png"
    web_logo.save(web_logo_path, "PNG", optimize=True)
    print("saved", web_logo_path, web_logo.size)

    # Storage branding assets (used by SiteSetting logo/favicon/og)
    web_full = web_trim.copy()
    if max(web_full.size) > 1200:
        r = 1200 / max(web_full.size)
        web_full = web_full.resize(
            (int(web_full.width * r), int(web_full.height * r)), Image.Resampling.LANCZOS
        )
    web_full.save(storage_brand / "logo.png", "PNG", optimize=True)

    favicon_sizes = [16, 32, 48, 64, 128, 256]
    favicon_imgs = [square_canvas(app_trim, s) for s in favicon_sizes]
    favicon_imgs[-1].save(
        logo_dir / "favicon.ico",
        format="ICO",
        sizes=[(s, s) for s in favicon_sizes],
    )
    favicon_imgs[-1].save(
        PROJ / "public" / "favicon.ico",
        format="ICO",
        sizes=[(s, s) for s in favicon_sizes],
    )
    square_canvas(app_trim, 256).save(storage_brand / "favicon.png", "PNG", optimize=True)

    og = square_canvas(web_trim, 1200, bg=(248, 250, 252, 255)).convert("RGB")
    og.save(storage_brand / "og.png", "PNG", optimize=True)

    # Keep user ico copies in public/images/logo (already present); also copy PNG masters
    web.save(logo_dir / "Shop3DPrinting.png", "PNG")
    app.save(logo_dir / "Shop3DPrinting-1.png", "PNG")

    # Android mipmaps from app icon (-1)
    android_res = PROJ / "mobile" / "admin_app" / "android" / "app" / "src" / "main" / "res"
    android_sizes = {
        "mipmap-mdpi": 48,
        "mipmap-hdpi": 72,
        "mipmap-xhdpi": 96,
        "mipmap-xxhdpi": 144,
        "mipmap-xxxhdpi": 192,
    }
    for folder, size in android_sizes.items():
        out = android_res / folder / "ic_launcher.png"
        out.parent.mkdir(parents=True, exist_ok=True)
        icon = square_canvas(app_trim, size, bg=(248, 250, 252, 255)).convert("RGBA")
        icon.save(out, "PNG", optimize=True)
        print("android", folder, size)

    # iOS icons
    ios_dir = (
        PROJ
        / "mobile"
        / "admin_app"
        / "ios"
        / "Runner"
        / "Assets.xcassets"
        / "AppIcon.appiconset"
    )
    ios_map = {
        "Icon-App-20x20@1x.png": 20,
        "Icon-App-20x20@2x.png": 40,
        "Icon-App-20x20@3x.png": 60,
        "Icon-App-29x29@1x.png": 29,
        "Icon-App-29x29@2x.png": 58,
        "Icon-App-29x29@3x.png": 87,
        "Icon-App-40x40@1x.png": 40,
        "Icon-App-40x40@2x.png": 80,
        "Icon-App-40x40@3x.png": 120,
        "Icon-App-60x60@2x.png": 120,
        "Icon-App-60x60@3x.png": 180,
        "Icon-App-76x76@1x.png": 76,
        "Icon-App-76x76@2x.png": 152,
        "Icon-App-83.5x83.5@2x.png": 167,
        "Icon-App-1024x1024@1x.png": 1024,
    }
    for name, size in ios_map.items():
        out = ios_dir / name
        icon = square_canvas(app_trim, size, bg=(248, 250, 252, 255)).convert("RGB")
        icon.save(out, "PNG", optimize=True)
    print("ios icons done")

    win_ico = PROJ / "mobile" / "admin_app" / "windows" / "runner" / "resources" / "app_icon.ico"
    if win_ico.parent.exists():
        imgs = [square_canvas(app_trim, s, bg=(248, 250, 252, 255)) for s in [16, 32, 48, 256]]
        imgs[-1].save(win_ico, format="ICO", sizes=[(16, 16), (32, 32), (48, 48), (256, 256)])
        print("windows ico")

    assets = PROJ / "mobile" / "admin_app" / "assets" / "branding"
    assets.mkdir(parents=True, exist_ok=True)
    square_canvas(app_trim, 512, bg=(248, 250, 252, 255)).save(assets / "app_icon.png", "PNG")
    web_logo.save(assets / "logo.png", "PNG")
    print("done")


if __name__ == "__main__":
    main()
