import fs from 'node:fs';
import PDFDocument from 'pdfkit';
import QRCode from 'qrcode';

const [inputPath, outputPath] = process.argv.slice(2);
if (!inputPath || !outputPath) {
  console.error('Usage: node scripts/pdf-report.mjs input.json output.pdf');
  process.exit(1);
}

const payload = JSON.parse(fs.readFileSync(inputPath, 'utf8'));

const doc = new PDFDocument({ size: 'A4', margin: 36, bufferPages: true, autoFirstPage: false });
const stream = fs.createWriteStream(outputPath);
doc.pipe(stream);

const page = { width: 595.28, height: 841.89, margin: 36 };
const colors = {
  ink: '#0f172a',
  muted: '#64748b',
  line: '#dbe3ef',
  soft: '#f8fafc',
  blue: '#0284c7',
  green: '#059669',
  red: '#e11d48',
  amber: '#d97706',
};

function val(value, fallback = '-') {
  if (value === null || value === undefined || value === '') return fallback;
  return String(value);
}

function fit(value, length = 52) {
  const text = val(value);
  return text.length > length ? `${text.slice(0, length - 3)}...` : text;
}

function labelFromKey(key) {
  return key.replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function statusColor(status) {
  return {
    open: colors.red,
    pending: colors.amber,
    progress: colors.blue,
    completed: colors.green,
    closed: colors.green,
  }[status] || colors.muted;
}

function ensureSpace(height) {
  if (doc.y + height > 770) doc.addPage();
}

function ensureFirstPage() {
  if (doc.page) return;
  doc.addPage();
}

function drawHeader() {
  ensureFirstPage();
  doc.save();
  doc.rect(0, 0, page.width, 92).fill(colors.ink);
  doc.rect(0, 88, page.width, 4).fill(colors.blue);
  doc.fillColor('#ffffff').font('Helvetica-Bold').fontSize(18)
    .text(payload.title || 'Report', page.margin, 24, { width: page.width - 72 });
  doc.font('Helvetica').fontSize(9).fillColor('#dbeafe')
    .text('Sistem Mapping Jaringan', page.margin, 50);
  doc.font('Helvetica-Bold').fontSize(9).fillColor('#ffffff')
    .text(val(payload.generated_at), page.width - 190, 50, { width: 154, align: 'right' });
  doc.restore();
  doc.y = 118;
}

function section(title) {
  ensureFirstPage();
  ensureSpace(44);
  doc.moveDown(0.4);
  doc.font('Helvetica-Bold').fontSize(12).fillColor(colors.ink).text(title);
  doc.moveTo(page.margin, doc.y + 5).lineTo(page.width - page.margin, doc.y + 5).strokeColor(colors.line).stroke();
  doc.moveDown(0.8);
}

function summaryCards(summary) {
  section('Ringkasan');
  const entries = Object.entries(summary);
  const gap = 10;
  const cols = Math.min(entries.length || 1, 4);
  const cardWidth = (page.width - page.margin * 2 - gap * (cols - 1)) / cols;
  const startY = doc.y;

  entries.forEach(([key, value], index) => {
    const x = page.margin + (index % cols) * (cardWidth + gap);
    const y = startY + Math.floor(index / cols) * 66;
    doc.roundedRect(x, y, cardWidth, 52, 7).fillAndStroke(colors.soft, colors.line);
    doc.font('Helvetica').fontSize(8).fillColor(colors.muted).text(labelFromKey(key), x + 11, y + 11, { width: cardWidth - 22 });
    doc.font('Helvetica-Bold').fontSize(16).fillColor(colors.ink).text(val(value), x + 11, y + 27, { width: cardWidth - 22 });
  });

  doc.y = startY + Math.ceil(entries.length / cols) * 66;
}

function table(title, columns, rows) {
  if (!rows?.length) return;
  section(title);

  const totalWidth = page.width - page.margin * 2;
  const headerHeight = 25;
  const rowHeight = 31;

  function drawTableHeader() {
    ensureSpace(headerHeight + rowHeight);
    const y = doc.y;
    doc.roundedRect(page.margin, y, totalWidth, headerHeight, 5).fill(colors.ink);
    let x = page.margin;
    columns.forEach((column) => {
      doc.font('Helvetica-Bold').fontSize(7.5).fillColor('#ffffff')
        .text(column.label, x + 7, y + 8, { width: column.width - 14, height: 10 });
      x += column.width;
    });
    doc.y = y + headerHeight;
  }

  drawTableHeader();
  rows.forEach((row, index) => {
    if (doc.y + rowHeight > 770) {
      doc.addPage();
      drawTableHeader();
    }

    const y = doc.y;
    doc.rect(page.margin, y, totalWidth, rowHeight).fill(index % 2 === 0 ? '#ffffff' : colors.soft);
    doc.moveTo(page.margin, y).lineTo(page.width - page.margin, y).strokeColor(colors.line).stroke();

    let x = page.margin;
    columns.forEach((column) => {
      const raw = typeof column.value === 'function' ? column.value(row, index) : row[column.value];
      const color = column.status ? statusColor(raw) : colors.ink;
      doc.font(column.bold ? 'Helvetica-Bold' : 'Helvetica').fontSize(8).fillColor(color)
        .text(fit(raw, column.limit || 38), x + 7, y + 8, { width: column.width - 14, height: 18 });
      x += column.width;
    });

    doc.y = y + rowHeight;
  });

  doc.moveDown(0.8);
}

function kvGrid(title, rows) {
  section(title);
  const boxY = doc.y;
  const rowHeight = 28;
  const boxHeight = rows.length * rowHeight + 12;
  ensureSpace(boxHeight);
  doc.roundedRect(page.margin, boxY, page.width - 72, boxHeight, 8).fillAndStroke('#ffffff', colors.line);

  rows.forEach(([label, value], index) => {
    const y = boxY + 10 + index * rowHeight;
    doc.font('Helvetica-Bold').fontSize(8).fillColor(colors.muted).text(label, page.margin + 14, y, { width: 132 });
    doc.font('Helvetica').fontSize(9).fillColor(colors.ink).text(val(value), page.margin + 150, y, { width: page.width - 222 });
  });

  doc.y = boxY + boxHeight + 8;
}

function signatures() {
  section('Tanda Tangan');
  const labels = ['Admin NOC', 'Teknisi', 'Supervisor'];
  const width = 160;
  const gap = 18;
  const y = doc.y + 4;
  labels.forEach((label, index) => {
    const x = page.margin + index * (width + gap);
    doc.font('Helvetica-Bold').fontSize(9).fillColor(colors.ink).text(label, x, y, { width, align: 'center' });
    doc.roundedRect(x, y + 22, width, 76, 6).strokeColor(colors.line).stroke();
    doc.font('Helvetica').fontSize(8).fillColor(colors.muted).text('Nama / Tanggal', x, y + 108, { width, align: 'center' });
  });
  doc.y = y + 130;
}

function mapsUrlForNode(node) {
  const lat = Number(node?.latitude);
  const lng = Number(node?.longitude);
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
  return `https://www.google.com/maps?q=${encodeURIComponent(`${lat},${lng}`)}`;
}

function linkBarcodeValue(link) {
  const source = val(link?.source_code, '');
  const target = val(link?.target_code, '');
  const coreCount = val(link?.core_count, '');
  const coreNumber = val(link?.core_number, '');
  const cableType = val(link?.cable_type, '');
  const pon = val(link?.pon_name, '');
  const odc = val(link?.odc_name, '');
  const notes = val(link?.notes, '');
  const id = val(link?.id, '');
  return [
    'LINK',
    `id=${id}`,
    `from=${source}`,
    `to=${target}`,
    cableType ? `cable=${cableType}` : null,
    coreCount ? `core_count=${coreCount}` : null,
    coreNumber ? `core_no=${coreNumber}` : null,
    pon ? `pon=${pon}` : null,
    odc ? `odc=${odc}` : null,
    notes ? `notes=${notes}` : null,
  ].filter(Boolean).join('|').slice(0, 420);
}

function linkTitleLine(link) {
  const source = val(link?.source_code);
  const target = val(link?.target_code);
  return `${source} → ${target}`;
}

function linkCoreLine(link) {
  const parts = [];
  if (link?.cable_type) parts.push(`Kabel: ${val(link.cable_type)}`);
  const core = [link?.core_count, link?.core_number].filter(Boolean).map((v) => val(v)).join(' / ');
  if (core) parts.push(`Core: ${core}`);
  return parts.join(' | ') || 'Kabel/Core: -';
}

function linkPonOdcLine(link) {
  const parts = [link?.pon_name, link?.odc_name].filter(Boolean).map((v) => val(v));
  return parts.length ? `PON/ODC: ${fit(parts.join(' / '), 44)}` : 'PON/ODC: -';
}

async function linkStickerSheets(links, options = {}) {
  const copies = Math.max(1, Math.min(Number(options?.copies) || 3, 6));
  const rows = 8;
  const cols = 3;
  const gap = 12;
  const titleH = 28;
  const startY = page.margin + titleH;
  const usableH = page.height - startY - page.margin;
  const stickerW = (page.width - page.margin * 2 - gap * (cols - 1)) / cols;
  const stickerH = (usableH - gap * (rows - 1)) / rows;
  const perPage = rows * cols;

  const qrCache = new Map();
  async function qrFor(value) {
    if (qrCache.has(value)) return qrCache.get(value);
    const png = await QRCode.toBuffer(value, { type: 'png', margin: 1, width: 128 });
    qrCache.set(value, png);
    return png;
  }

  for (let copy = 1; copy <= copies; copy += 1) {
    let offset = 0;
    while (offset < links.length || offset === 0) {
      doc.addPage();

      doc.font('Helvetica-Bold').fontSize(13).fillColor(colors.ink)
        .text(`Sticker Link (QR) - Lembar ${copy} / ${copies}`, page.margin, page.margin, { width: page.width - 72 });
      doc.font('Helvetica').fontSize(8.5).fillColor(colors.muted)
        .text('Tempel sticker ini di kabel (sisi sumber & tujuan). Scan QR untuk lihat identitas link.', page.margin, page.margin + 16, { width: page.width - 72 });
      doc.moveTo(page.margin, startY - 6).lineTo(page.width - page.margin, startY - 6).strokeColor(colors.line).stroke();

      const chunk = links.slice(offset, offset + perPage);
      if (!chunk.length) {
        doc.font('Helvetica').fontSize(10).fillColor(colors.muted)
          .text('Tidak ada data link.', page.margin, startY + 10);
        break;
      }

      for (let i = 0; i < chunk.length; i += 1) {
        const link = chunk[i];
        const row = Math.floor(i / cols);
        const col = i % cols;
        const x = page.margin + col * (stickerW + gap);
        const y = startY + row * (stickerH + gap);

        doc.roundedRect(x, y, stickerW, stickerH, 8).fillAndStroke('#ffffff', colors.line);

        const pad = 7;
        const value = linkBarcodeValue(link);
        const qrSize = 64;
        const qr = await qrFor(value);

        const qrTop = y + pad;
        doc.image(qr, x + pad, qrTop, { width: qrSize, height: qrSize });

        const textX = x + pad + qrSize + 8;
        const textW = stickerW - (textX - x) - pad;
        const line1Y = y + pad + 1;
        const line2Y = line1Y + 12;
        const line3Y = line2Y + 12;
        const line4Y = line3Y + 12;

        doc.font('Helvetica-Bold').fontSize(8.7).fillColor(colors.ink)
          .text(fit(linkTitleLine(link), 28), textX, line1Y, { width: textW });
        doc.font('Helvetica').fontSize(7.8).fillColor(colors.ink)
          .text(fit(linkCoreLine(link), 42), textX, line2Y, { width: textW });
        doc.font('Helvetica').fontSize(7.8).fillColor(colors.ink)
          .text(linkPonOdcLine(link), textX, line3Y, { width: textW });

        const id = val(link?.id, '-');
        doc.font('Helvetica').fontSize(7.4).fillColor(colors.muted)
          .text(`ID: ${id}`, textX, line4Y, { width: textW });

        doc.font('Helvetica').fontSize(6.2).fillColor(colors.muted)
          .text(fit(value, 70), x + pad, y + stickerH - pad - 8, { width: stickerW - pad * 2, align: 'left' });
      }

      offset += perPage;
      if (links.length <= perPage) break;
    }
  }
}

async function nodeQrSection(nodes) {
  const withCoords = (nodes || []).filter((n) => mapsUrlForNode(n));
  if (!withCoords.length) return;

  section('QR Lokasi Node (Google Maps)');
  doc.font('Helvetica').fontSize(8.5).fillColor(colors.muted)
    .text('Scan QR untuk buka lokasi di Google Maps. QR hanya dibuat untuk node yang punya latitude & longitude.', page.margin, doc.y - 2);
  doc.moveDown(0.6);

  const gap = 12;
  const cols = 2;
  const cardW = (page.width - page.margin * 2 - gap * (cols - 1)) / cols;
  const qrSize = 86;
  const cardPad = 10;
  const cardH = qrSize + cardPad * 2;

  for (let rowStart = 0; rowStart < withCoords.length; rowStart += cols) {
    ensureSpace(cardH + gap);
    const rowY = doc.y;

    for (let col = 0; col < cols; col += 1) {
      const node = withCoords[rowStart + col];
      if (!node) continue;
      const url = mapsUrlForNode(node);
      if (!url) continue;

      const x = page.margin + col * (cardW + gap);
      const y = rowY;

      doc.roundedRect(x, y, cardW, cardH, 8).fillAndStroke('#ffffff', colors.line);

      const qr = await QRCode.toBuffer(url, { type: 'png', margin: 1, width: qrSize });
      doc.image(qr, x + cardPad, y + cardPad, { width: qrSize, height: qrSize });

      const textX = x + cardPad + qrSize + 10;
      const textW = cardW - (textX - x) - cardPad;
      const line1Y = y + cardPad;
      const line2Y = line1Y + 14;
      const line3Y = line2Y + 18;
      const line4Y = line3Y + 14;

      doc.font('Helvetica-Bold').fontSize(9).fillColor(colors.ink)
        .text(fit(node.code || '-', 28), textX, line1Y, { width: textW });
      doc.font('Helvetica').fontSize(8.5).fillColor(colors.muted)
        .text(fit(node.name || '-', 34), textX, line2Y, { width: textW });

      const coords = node.latitude && node.longitude ? `${node.latitude}, ${node.longitude}` : '-';
      doc.font('Helvetica').fontSize(8.5).fillColor(colors.ink)
        .text(`Koordinat: ${fit(coords, 44)}`, textX, line3Y, { width: textW });

      if (node.address) {
        doc.font('Helvetica').fontSize(8.5).fillColor(colors.ink)
          .text(`Alamat: ${fit(node.address, 56)}`, textX, line4Y, { width: textW });
      }

      doc.font('Helvetica').fontSize(7.5).fillColor(colors.muted)
        .text(fit(url, 64), textX, y + cardH - 18, { width: textW });
    }

    doc.y = rowY + cardH + gap;
  }
}


const stickerPosition = payload.stickers?.position === 'first' ? 'first' : 'last';
const shouldRenderLinkStickers = (payload.type === 'links' || payload.type === 'link-stickers') && payload.stickers?.enabled && payload.links?.length;

if (shouldRenderLinkStickers && stickerPosition === 'first') {
  await linkStickerSheets(payload.links, payload.stickers);
}

drawHeader();
doc.font('Helvetica').fontSize(9).fillColor(colors.muted)
  .text(`Jenis laporan: ${val(payload.type)}`, page.margin, 100);

if (payload.summary) summaryCards(payload.summary);

table('Daftar Node', [
  { label: 'Kode', value: 'code', width: 70, bold: true, limit: 20 },
  { label: 'Nama', value: 'name', width: 112, limit: 28 },
  { label: 'Jenis', value: (row) => row.type_label || row.type, width: 76, limit: 22 },
  { label: 'Koordinat', value: (row) => `${val(row.latitude)}, ${val(row.longitude)}`, width: 112, limit: 30 },
  { label: 'Alamat / Catatan', value: (row) => row.address || row.notes, width: 153, limit: 44 },
], payload.nodes);

table('Daftar Link', [
  { label: 'Dari', value: 'source_code', width: 80, bold: true },
  { label: 'Ke', value: 'target_code', width: 80, bold: true },
  { label: 'Kabel', value: 'cable_type', width: 92 },
  { label: 'Core', value: (row) => [row.core_count, row.core_number].filter(Boolean).join(' / '), width: 92 },
  { label: 'PON / ODC', value: (row) => [row.pon_name, row.odc_name].filter(Boolean).join(' / '), width: 109 },
  { label: 'Catatan', value: 'notes', width: 70 },
], payload.links);

if ((payload.type === 'topology' || payload.type === 'nodes') && payload.nodes?.length) {
  await nodeQrSection(payload.nodes);
}

if (shouldRenderLinkStickers && stickerPosition === 'last') {
  await linkStickerSheets(payload.links, payload.stickers);
}


const range = doc.bufferedPageRange();
for (let i = range.start; i < range.start + range.count; i += 1) {
  doc.switchToPage(i);
  doc.font('Helvetica').fontSize(7.5).fillColor(colors.muted)
    .text('Dokumen ini di-generate otomatis oleh Wifi Maps.', page.margin, page.height - page.margin - 14, { width: 300 });
  doc.text(`Halaman ${i + 1} / ${range.count}`, page.width - 126, page.height - page.margin - 14, { width: 90, align: 'right' });
}

doc.end();

stream.on('finish', () => process.exit(0));
stream.on('error', (error) => {
  console.error(error);
  process.exit(1);
});
