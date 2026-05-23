import fs from 'node:fs';
import path from 'node:path';
import PDFDocument from 'pdfkit';

const [inputPath, outputPath] = process.argv.slice(2);
if (!inputPath || !outputPath) {
  console.error('Usage: node scripts/pdf-report.mjs input.json output.pdf');
  process.exit(1);
}

const payload = JSON.parse(fs.readFileSync(inputPath, 'utf8'));

if (payload?.type === 'surat_jalan') {
  const { buildSuratJalanPdfBuffer } = await import('../resources/js/pdf.js');

  const sj = payload?.surat_jalan || {};
  const node = sj?.node || {};

  let createdAt = new Date();
  if (payload?.generated_at) {
    const parsed = new Date(payload.generated_at);
    if (!Number.isNaN(parsed.getTime())) createdAt = parsed;
  }

  const uploadDirAbs = path.resolve(process.cwd(), 'public', 'uploads');
  const photoPath = node.photo_path || node.photo || node.photoPath;
  const nodeForPdf = { ...node, photo_path: photoPath };

  const extras = {
    doc_no: sj.document_no,
    ticket_no: sj.ticket_no || sj.tiket_no || sj.incident_no || sj.incident_id,
    tujuan: sj.tujuan,
    noc_admin: sj.noc_admin,
    teknisi: sj.teknisi,
    teknisi_contact: sj.teknisi_contact || sj.technician_contact,
    teknisi_email: sj.teknisi_email || sj.technician_email,
    kendaraan: sj.kendaraan,
    kerusakan: sj.kerusakan,
    keperluan: sj.keperluan,
  };

  const buffer = await buildSuratJalanPdfBuffer({ node: nodeForPdf, createdAt, extras, uploadDirAbs });
  fs.writeFileSync(outputPath, buffer);
  process.exit(0);
}

const doc = new PDFDocument({ size: 'A4', margin: 36, bufferPages: true });
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

function drawHeader() {
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

function suratJalan(sj) {
  kvGrid('Informasi Dokumen', [
    ['No Dokumen', sj.document_no],
    ['Tujuan', sj.tujuan],
    ['Keperluan', sj.keperluan],
    ['Kerusakan / Catatan', sj.kerusakan],
    ['Admin NOC', sj.noc_admin],
    ['Teknisi', sj.teknisi],
    ['Kendaraan', sj.kendaraan],
  ]);

  kvGrid('Lokasi / Node', [
    ['Kode Node', sj.node?.code],
    ['Nama Node', sj.node?.name],
    ['Jenis', sj.node?.type_label || sj.node?.type],
    ['Koordinat', sj.node?.latitude && sj.node?.longitude ? `${sj.node.latitude}, ${sj.node.longitude}` : '-'],
    ['Alamat', sj.node?.address],
    ['Catatan Node', sj.node?.notes],
  ]);

  signatures();
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

table('Daftar Gangguan', [
  { label: 'Judul', value: 'title', width: 150, bold: true, limit: 34 },
  { label: 'Kategori', value: 'category', width: 74 },
  { label: 'Status', value: 'status', width: 76, status: true, bold: true },
  { label: 'Node', value: 'node_code', width: 68, bold: true },
  { label: 'Pelapor', value: 'reporter_name', width: 80 },
  { label: 'Teknisi', value: 'technician_name', width: 75 },
], payload.incidents);

table('Rekam Kerja', [
  { label: 'Laporan', value: 'report_title', width: 162, bold: true, limit: 34 },
  { label: 'Status', value: 'status', width: 76, status: true, bold: true },
  { label: 'Teknisi', value: 'technician_name', width: 94 },
  { label: 'Node', value: 'node_code', width: 76, bold: true },
  { label: 'Keterangan', value: 'description', width: 115, limit: 36 },
], payload.work_reports);

if (payload.surat_jalan) suratJalan(payload.surat_jalan);

const range = doc.bufferedPageRange();
for (let i = range.start; i < range.start + range.count; i += 1) {
  doc.switchToPage(i);
  doc.font('Helvetica').fontSize(7.5).fillColor(colors.muted)
    .text('Dokumen ini di-generate otomatis oleh Wifi Maps.', page.margin, 808, { width: 300 });
  doc.text(`Halaman ${i + 1} / ${range.count}`, page.width - 126, 808, { width: 90, align: 'right' });
}

doc.end();

stream.on('finish', () => process.exit(0));
stream.on('error', (error) => {
  console.error(error);
  process.exit(1);
});
