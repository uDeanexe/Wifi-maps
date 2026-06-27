import fs from 'node:fs';
import path from 'node:path';
import PDFDocument from 'pdfkit';
import QRCode from 'qrcode';

const [inputPath, outputPath] = process.argv.slice(2);
if (!inputPath || !outputPath) {
  console.error('Penggunaan: node resources/js/link-stickers-report.mjs <input.json> <output.pdf>');
  process.exit(1);
}

if (!fs.existsSync(inputPath)) {
  console.error(`File input report tidak ditemukan: ${inputPath}`);
  process.exit(1);
}

let payload;
try {
  payload = JSON.parse(fs.readFileSync(inputPath, 'utf8').replace(/^\uFEFF/, ''));
} catch (error) {
  console.error(`File input report tidak valid: ${error.message}`);
  process.exit(1);
}

fs.mkdirSync(path.dirname(path.resolve(outputPath)), { recursive: true });

const doc = new PDFDocument({ size: 'A4', margin: 28, bufferPages: true, autoFirstPage: false });
const stream = fs.createWriteStream(outputPath);
doc.pipe(stream);

const page = { width: 595.28, height: 841.89, margin: 28 };
const colors = {
  ink: '#0f172a',
  muted: '#64748b',
  faint: '#94a3b8',
  line: '#dbe3ef',
  blue: '#0284c7',
  blueDark: '#075985',
  softBlue: '#eef6ff',
  soft: '#f8fafc',
  green: '#059669',
};

function val(value, fallback = '-') {
  if (value === null || value === undefined || value === '') return fallback;
  return String(value);
}

function fit(value, length = 48) {
  const text = val(value, '');
  return text.length > length ? `${text.slice(0, length - 3)}...` : text;
}

function normalizedCableType(link) {
  const cableType = val(link?.cable_type, '').trim();
  return cableType.toLowerCase() === 'manual drawing' ? '' : cableType;
}

function cableNameFromNotes(notes) {
  const text = val(notes, '');
  const match = text.match(/Nama kabel:\s*(.+)$/iu);
  return match ? match[1].trim() : '';
}

function cableName(link) {
  return val(
    link?.cable_name || link?.name || cableNameFromNotes(link?.notes) || normalizedCableType(link) || `${val(link?.source_code)} → ${val(link?.target_code)}`,
  );
}

function routeLine(link) {
  return `${val(link?.source_code)} → ${val(link?.target_code)}`;
}

function coreLine(link) {
  const core = [link?.core_count, link?.core_number].filter(Boolean).map((v) => val(v)).join(' / ');
  return core ? `Core: ${core}` : 'Core: -';
}

function ponOdcLine(link) {
  const parts = [link?.pon_name, link?.odc_name].filter(Boolean).map((v) => val(v));
  return parts.length ? `PON/ODC: ${fit(parts.join(' / '), 40)}` : 'PON/ODC: -';
}

function qrValue(link) {
  const name = cableName(link);
  const cableType = normalizedCableType(link);
  const rows = [
    'WIFI_MAPS_LINK',
    `id=${val(link?.id, '')}`,
    `name=${name}`,
    `from=${val(link?.source_code, '')}`,
    `to=${val(link?.target_code, '')}`,
    cableType ? `type=${cableType}` : null,
    link?.core_count ? `core_count=${val(link.core_count)}` : null,
    link?.core_number ? `core_no=${val(link.core_number)}` : null,
    link?.pon_name ? `pon=${val(link.pon_name)}` : null,
    link?.odc_name ? `odc=${val(link.odc_name)}` : null,
  ];

  return rows.filter(Boolean).join('|').slice(0, 480);
}

function safeText(text, x, y, width, font = 'Helvetica', size = 8, color = colors.ink, height = size + 3) {
  doc.font(font).fontSize(size).fillColor(color);
  doc.text(String(text), x, y, { width, height, lineBreak: false, ellipsis: true });
}

async function drawSticker(link, x, y, width, height, qrCache) {
  doc.roundedRect(x, y, width, height, 10).fillAndStroke('#ffffff', colors.line);
  doc.roundedRect(x, y, 5, height, 2).fill(colors.blue);

  const pad = 11;
  const qrSize = 86;
  const value = qrValue(link);
  let qr = qrCache.get(value);
  if (!qr) {
    qr = await QRCode.toBuffer(value, { type: 'png', margin: 1, width: 172 });
    qrCache.set(value, qr);
  }

  doc.image(qr, x + pad, y + pad, { width: qrSize, height: qrSize });

  const textX = x + pad + qrSize + 12;
  const textW = width - (textX - x) - pad;
  const name = cableName(link);
  const type = normalizedCableType(link);

  safeText(fit(name, 32), textX, y + pad + 1, textW, 'Helvetica-Bold', 10, colors.ink, 13);
  safeText(`Rute: ${fit(routeLine(link), 34)}`, textX, y + pad + 17, textW, 'Helvetica', 8, colors.ink, 11);
  safeText(type ? `Jenis: ${fit(type, 30)}` : 'Jenis: -', textX, y + pad + 30, textW, 'Helvetica', 8, colors.ink, 11);
  safeText(coreLine(link), textX, y + pad + 43, textW, 'Helvetica', 8, colors.ink, 11);
  safeText(ponOdcLine(link), textX, y + pad + 56, textW, 'Helvetica', 8, colors.ink, 11);

  doc.roundedRect(textX, y + height - pad - 18, textW, 16, 5).fill(colors.softBlue);
  safeText(`ID: ${val(link?.id)}   Scan untuk identitas link`, textX + 7, y + height - pad - 13.2, textW - 14, 'Helvetica-Bold', 7.2, colors.blueDark, 10);

  safeText(fit(value, 78), x + pad, y + height - pad - 9, width - pad * 2, 'Helvetica', 5.7, colors.faint, 8);
}

function drawHeader(copy, copies) {
  doc.font('Helvetica-Bold').fontSize(14).fillColor(colors.ink)
    .text(`Sticker Link QR - Lembar ${copy} / ${copies}`, page.margin, page.margin, { width: page.width - page.margin * 2 });
  doc.font('Helvetica').fontSize(8.5).fillColor(colors.muted)
    .text('Tempel sticker pada kabel/perangkat. QR memuat nama kabel, rute node, core, PON/ODC, dan ID link.', page.margin, page.margin + 18, { width: page.width - page.margin * 2 });
  doc.moveTo(page.margin, page.margin + 38).lineTo(page.width - page.margin, page.margin + 38).strokeColor(colors.line).stroke();
}

function drawCutGuides(startY, cols, rows, stickerW, stickerH, gap) {
  const left = page.margin;
  const right = page.width - page.margin;
  const bottom = startY + rows * stickerH + (rows - 1) * gap;

  doc.save();
  doc.strokeColor('#cbd5e1').lineWidth(0.7).dash(3, { space: 3 });
  doc.rect(left, startY, right - left, bottom - startY).stroke();
  for (let c = 1; c < cols; c += 1) {
    const x = left + c * stickerW + (c - 0.5) * gap;
    doc.moveTo(x, startY).lineTo(x, bottom).stroke();
  }
  for (let r = 1; r < rows; r += 1) {
    const y = startY + r * stickerH + (r - 0.5) * gap;
    doc.moveTo(left, y).lineTo(right, y).stroke();
  }
  doc.undash();
  doc.restore();
}

async function render() {
  const links = Array.isArray(payload.links) ? payload.links : [];
  const copies = Math.max(1, Math.min(Number(payload?.stickers?.copies) || 3, 6));
  const cols = 2;
  const rows = 5;
  const gap = 12;
  const startY = page.margin + 48;
  const usableW = page.width - page.margin * 2;
  const usableH = page.height - startY - page.margin;
  const stickerW = (usableW - gap * (cols - 1)) / cols;
  const stickerH = (usableH - gap * (rows - 1)) / rows;
  const perPage = cols * rows;
  const qrCache = new Map();

  for (let copy = 1; copy <= copies; copy += 1) {
    let offset = 0;
    do {
      doc.addPage();
      drawHeader(copy, copies);
      drawCutGuides(startY, cols, rows, stickerW, stickerH, gap);

      const chunk = links.slice(offset, offset + perPage);
      if (!chunk.length) {
        doc.font('Helvetica').fontSize(10).fillColor(colors.muted).text('Tidak ada data link.', page.margin, startY + 8);
        break;
      }

      for (let i = 0; i < chunk.length; i += 1) {
        const row = Math.floor(i / cols);
        const col = i % cols;
        const x = page.margin + col * (stickerW + gap);
        const y = startY + row * (stickerH + gap);
        await drawSticker(chunk[i], x, y, stickerW, stickerH, qrCache);
      }

      offset += perPage;
    } while (offset < links.length);
  }
}

await render();
doc.end();

await new Promise((resolve, reject) => {
  stream.on('finish', resolve);
  stream.on('error', reject);
});
