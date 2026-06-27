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

const doc = new PDFDocument({ size: 'A4', margin: 26, bufferPages: true, autoFirstPage: false });
const stream = fs.createWriteStream(outputPath);
doc.pipe(stream);

const page = { width: 595.28, height: 841.89, margin: 26 };
const brandName = 'JONUSA MAPS';
const colors = {
  ink: '#0f172a',
  muted: '#64748b',
  faint: '#94a3b8',
  line: '#dbe3ef',
  lineDark: '#bfdbfe',
  blue: '#0284c7',
  blueDark: '#075985',
  blueSoft: '#e0f2fe',
  bluePale: '#f0f9ff',
  soft: '#f8fafc',
  white: '#ffffff',
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

function coreText(link) {
  const core = [link?.core_count, link?.core_number].filter(Boolean).map((v) => val(v)).join(' / ');
  return core || '-';
}

function ponOdcText(link) {
  const parts = [link?.pon_name, link?.odc_name].filter(Boolean).map((v) => val(v));
  return parts.length ? fit(parts.join(' / '), 34) : '-';
}

function qrValue(link) {
  const name = cableName(link);
  const cableType = normalizedCableType(link) || '-';
  const rows = [
    `${brandName} - DATA LINK`,
    `ID Link: ${val(link?.id)}`,
    `Nama Kabel: ${name}`,
    `Dari Node: ${val(link?.source_code)}`,
    `Ke Node: ${val(link?.target_code)}`,
    `Rute: ${routeLine(link)}`,
    `Jenis Kabel: ${cableType}`,
    `Core: ${coreText(link)}`,
    `PON/ODC: ${ponOdcText(link)}`,
    link?.notes ? `Catatan: ${fit(link.notes, 120)}` : null,
    payload?.generated_at ? `Generated: ${payload.generated_at}` : null,
  ];

  return rows.filter(Boolean).join('\n').slice(0, 720);
}

function safeText(text, x, y, width, font = 'Helvetica', size = 8, color = colors.ink, height = size + 3, options = {}) {
  doc.font(font).fontSize(size).fillColor(color);
  doc.text(String(text), x, y, { width, height, lineBreak: false, ellipsis: true, ...options });
}

function detailRow(label, value, x, y, width) {
  const labelW = 44;
  safeText(label, x, y, labelW, 'Helvetica-Bold', 7.3, colors.muted, 10);
  safeText(value, x + labelW, y, width - labelW, 'Helvetica', 7.5, colors.ink, 10);
}

async function drawSticker(link, x, y, width, height, qrCache) {
  doc.roundedRect(x, y, width, height, 10).fillAndStroke(colors.white, colors.lineDark);
  doc.roundedRect(x, y, 5.5, height, 2).fill(colors.blue);

  const pad = 10;
  const qrSize = 76;
  const value = qrValue(link);
  let qr = qrCache.get(value);
  if (!qr) {
    qr = await QRCode.toBuffer(value, { type: 'png', margin: 1, width: 168, errorCorrectionLevel: 'M' });
    qrCache.set(value, qr);
  }

  const qrX = x + pad + 2;
  const qrY = y + pad + 4;
  doc.roundedRect(qrX - 4, qrY - 4, qrSize + 8, qrSize + 8, 7).fillAndStroke('#ffffff', '#e2e8f0');
  doc.image(qr, qrX, qrY, { width: qrSize, height: qrSize });

  const textX = qrX + qrSize + 16;
  const textW = width - (textX - x) - pad - 2;
  const name = cableName(link);
  const type = normalizedCableType(link) || '-';

  safeText(fit(name, 30), textX, y + pad + 2, textW, 'Helvetica-Bold', 9.6, colors.ink, 13);
  safeText(`Rute: ${fit(routeLine(link), 34)}`, textX, y + pad + 17, textW, 'Helvetica', 7.5, colors.muted, 10);

  const boxY = y + pad + 33;
  const boxH = 42;
  doc.roundedRect(textX, boxY, textW, boxH, 7).fillAndStroke(colors.soft, colors.line);
  detailRow('Jenis', type, textX + 7, boxY + 7, textW - 14);
  detailRow('Core', coreText(link), textX + 7, boxY + 19, textW - 14);
  detailRow('PON/ODC', ponOdcText(link), textX + 7, boxY + 31, textW - 14);

  const footerY = y + height - pad - 18;
  doc.roundedRect(textX, footerY, textW, 18, 6).fill(colors.bluePale);
  safeText(`ID ${val(link?.id)}  •  Scan QR`, textX + 8, footerY + 5.2, textW - 16, 'Helvetica-Bold', 7.4, colors.blueDark, 9, { align: 'center' });

  safeText(brandName, qrX - 4, y + height - pad - 9, qrSize + 8, 'Helvetica-Bold', 5.8, colors.faint, 8, { align: 'center' });
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
  const rows = 6;
  const gap = 10;
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
