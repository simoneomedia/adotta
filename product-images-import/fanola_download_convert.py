#!/usr/bin/env python3
"""Scarica i packshot ufficiali Fanola (CDN thron.com) e li converte in WebP
quadrato max 1200px su fondo bianco, qualita' 85. Output: ./fanola_webp/{SKU}.webp
Requisiti: pip install pillow requests
Uso: python3 fanola_download_convert.py [fanola_immagini_urls.csv]
"""
import csv, io, os, sys
import requests
from PIL import Image

def to_webp(data, dst, pad_frac=0.06):
    im = Image.open(io.BytesIO(data))
    if im.mode in ('RGBA','LA','P'):
        im = im.convert('RGBA')
        bg = Image.new('RGBA', im.size, (255,255,255,255))
        bg.alpha_composite(im); im = bg.convert('RGB')
    else:
        im = im.convert('RGB')
    gray = im.convert('L')
    mask = gray.point(lambda p: 255 if p < 242 else 0)
    bbox = mask.getbbox()
    if bbox:
        x0,y0,x1,y1 = bbox
        bbox = (max(0,x0-2), max(0,y0-2), min(im.width,x1+2), min(im.height,y1+2))
        im = im.crop(bbox)
    side = max(im.width, im.height)
    canvas = 1200 if side >= 600 else max(400, min(1200, side*2))
    inner = int(canvas*(1-2*pad_frac)); scale = inner/side
    im = im.resize((max(1,int(im.width*scale)), max(1,int(im.height*scale))), Image.LANCZOS)
    out = Image.new('RGB', (canvas, canvas), 'white')
    out.paste(im, ((canvas-im.width)//2, (canvas-im.height)//2))
    out.save(dst, 'WEBP', quality=85, method=6)

src = sys.argv[1] if len(sys.argv)>1 else 'fanola_immagini_urls.csv'
os.makedirs('fanola_webp', exist_ok=True)
ok=fail=0
for row in csv.DictReader(open(src, encoding='utf-8')):
    dst = os.path.join('fanola_webp', row['FILE_WEBP'])
    if os.path.exists(dst): continue
    try:
        r = requests.get(row['URL_IMMAGINE_UFFICIALE'], timeout=60)
        r.raise_for_status()
        to_webp(r.content, dst)
        ok+=1; print('OK ', row['SKU'])
    except Exception as ex:
        fail+=1; print('FAIL', row['SKU'], ex)
print(f'finiti: {ok} ok, {fail} falliti')
