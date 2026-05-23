import PDFDocument from 'pdfkit';
import path from 'node:path';
import fs from 'node:fs';
import QRCode from 'qrcode';

function formatDateId(date = new Date()) {
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(
    date.getHours()
  )}:${pad(date.getMinutes())}`;
}

function clipText(value, max = 1400) {
  if (value === undefined || value === null) return '';
  const s = String(value).trim();
  return s.length > max ? `${s.slice(0, max)}...` : s;
}

function tryResolveUploadPath(photoPath, uploadDirAbs) {
  if (!photoPath || typeof photoPath !== 'string') return null;
  if (!photoPath.startsWith('/uploads/')) return null;
  const rel = photoPath.replace(/^\/uploads\//, '').replace(/\.\.(\/|\\)/g, '');
  const abs = path.resolve(uploadDirAbs, rel);
  try {
    if (fs.existsSync(abs)) return abs;
  } catch (_) {}
  return null;
}

function buildSuratJalanPdf({ node, createdAt = new Date(), extras = {}, uploadDirAbs }) {
  const doc = new PDFDocument({ size: 'A4', margins: { top: 40, bottom: 36, left: 48, right: 48 } });
  const pageW = 595.28;
  const pageH = 841.89;

  const palette = {
    ink: '#0F172A',
    muted: '#475569',
    border: '#CBD5E1',
    soft: '#F8FAFC',
    accent: '#0284C7',
    accentDark: '#0369A1',
  };

  const companyName = 'PT. JASA ONLINE NUSANTARA';
  const docNo = String(extras.doc_no || `SJ-${String(node.id).padStart(6, '0')}`);
  const createdText = formatDateId(createdAt);

  const lat = Number.isFinite(Number(node.latitude)) ? Number(node.latitude).toFixed(6) : null;
  const lng = Number.isFinite(Number(node.longitude)) ? Number(node.longitude).toFixed(6) : null;
  const gps = lat && lng ? `${lat}, ${lng}` : '-';
  const maps = lat && lng ? `https://maps.google.com/?q=${lat},${lng}` : '-';
  const qrPng = extras.qr_png || extras.maps_qr_png || null;

  const locationName = clipText(node.name || node.code || '-', 80);
  const addressFull = String(node.address || '-').trim();

  const normalizeContent = (value) => {
    const raw = String(value ?? '').replace(/\r\n/g, '\n');
    return raw
      .replace(/\bham\b/gi, 'jam')
      .replace(/\btechnictian\b/gi, 'Technician')
      .replace(/\btechnitian\b/gi, 'Technician')
      .replace(/[ \t]+/g, ' ')
      .trim();
  };

  const nocAdmin = clipText(normalizeContent(extras.noc_admin || '-'), 80);
  const damage = normalizeContent(extras.kerusakan || extras.damage || '-');
  const instruction = normalizeContent(extras.keperluan || extras.purpose || '-');
  const technician = clipText(normalizeContent(extras.teknisi || '-'), 80);
  const technicianContact = clipText(extras.teknisi_contact || extras.technician_contact || '-', 80);
  const technicianEmail = clipText(extras.teknisi_email || extras.technician_email || '-', 100);
  const vehicle = clipText(extras.kendaraan || '-', 80); // kept for future; not shown in preview

  function fitText(text, width, maxHeight, opts = {}) {
    const value = String(text ?? '').trim();
    if (!value) return '-';
    const lineGap = opts.lineGap ?? 2;
    const suffix = opts.suffix ?? '...';
    const measure = (s) => doc.heightOfString(String(s), { width, lineGap });
    if (measure(value) <= maxHeight) return value;
    let lo = 0;
    let hi = value.length;
    let best = '';
    while (lo <= hi) {
      const mid = Math.floor((lo + hi) / 2);
      const cand = `${value.slice(0, mid).trim()}${suffix}`;
      if (measure(cand) <= maxHeight) {
        best = cand;
        lo = mid + 1;
      } else {
        hi = mid - 1;
      }
    }
    return best || `${value.slice(0, 20)}${suffix}`;
  }

  function normalizeAddress(value) {
    const s = String(value || '').replace(/\r?\n+/g, ', ').replace(/\s+/g, ' ').trim();
    return s || '-';
  }

  const marginLeft = doc.page.margins.left;
  const marginRight = doc.page.margins.right;
  const contentW = pageW - marginLeft - marginRight;
  const x0 = marginLeft;
  let y = doc.page.margins.top;

  // Watermark (very subtle)
  doc.save();
  doc.rotate(-18, { origin: [pageW / 2, pageH / 2] });
  doc.opacity(0.06);
  doc.fillColor(palette.accentDark).font('Helvetica-Bold').fontSize(48).text('JONUSA', 90, 360, { width: 420, align: 'center' });
  doc.opacity(1);
  doc.restore();

  const drawCard = (x, y, w, h, opts = {}) => {
    const radius = opts.radius ?? 10;
    const stroke = opts.stroke ?? true;
    const strokeColor = opts.strokeColor ?? '#DCE7F5';
    const strokeOpacity = opts.strokeOpacity ?? 1;
    if (!stroke) return;
    doc.save();
    if (strokeOpacity !== 1) doc.opacity(strokeOpacity);
    doc.roundedRect(x, y, w, h, radius).lineWidth(1).strokeColor(strokeColor).stroke();
    doc.opacity(1);
    doc.restore();
  };

  const label = (text, x, y) => {
    doc.fillColor('#64748B').font('Helvetica-Bold').fontSize(8).text(String(text || '').toUpperCase(), x, y);
  };

  const valueText = (text, x, y, w, opts = {}) => {
    const v = fitText(text, w, opts.maxHeight ?? 9999, { lineGap: 2, suffix: '...' });
    doc.fillColor('#0F172A').font(opts.bold ? 'Helvetica-Bold' : 'Helvetica').fontSize(opts.size ?? 11).text(v, x, y, {
      width: w,
      lineGap: 2,
    });
  };

  // Header (letterhead / "kop surat")
  const headerTop = y;

  const companyLine = 'PT. JASA ONLINE NUSANTARA';
  const companyMeta = '';
  const companyContact = 'Alamat : Jl. Puri Lestari Utama No.20 Blok D3, Sukajaya, Kec. Cibitung, Kabupaten Bekasi, Jawa Barat 17520 | Telp: +62-822-1100-1991 | www.jonusa.net';

  // Left: company identity
  doc.fillColor(palette.accentDark).font('Helvetica-Bold').fontSize(12.5).text(companyLine, x0, headerTop);
  const contactX = x0;
  const contactY = headerTop + 16;
  const contactW = contentW - 170;
  doc.font('Helvetica').fontSize(8.2);
  const contactH = doc.heightOfString(companyContact, { width: contactW, lineGap: 1.2 });
  doc.fillColor('#94A3B8').text(companyContact, contactX, contactY, { width: contactW, lineGap: 1.2 });

  // Right: document number box aligned with header
  const docBoxW = 150;
  const docBoxH = 54;
  const docBoxX = x0 + contentW - docBoxW;
  const docBoxY = headerTop + 2;
  drawCard(docBoxX, docBoxY, docBoxW, docBoxH);
  doc.fillColor('#64748B').font('Helvetica-Bold').fontSize(8).text('NO DOKUMEN', docBoxX, docBoxY + 14, {
    width: docBoxW,
    align: 'center',
  });
  doc.fillColor('#0F172A').font('Helvetica-Bold').fontSize(10).text(docNo, docBoxX, docBoxY + 30, {
    width: docBoxW,
    align: 'center',
  });

  // Title block (no visual gap: computed from contact height)
  const titleTop = Math.max(contactY + contactH + 10, headerTop + 34);
  const titleW = contentW - docBoxW - 16;
  doc.fillColor(palette.ink).font('Helvetica-Bold').fontSize(18.5).text('Surat Jalan / Work Order', x0, titleTop, { width: titleW });
  doc.fillColor('#64748B').font('Helvetica').fontSize(10).text('Pekerjaan lapangan jaringan', x0, titleTop + 22, { width: titleW });

  // Divider line
  y = Math.max(titleTop + 40, docBoxY + docBoxH + 12);
  doc.save();
  doc.moveTo(x0, y).lineTo(x0 + contentW, y).lineWidth(2).strokeColor(palette.accent).stroke();
  doc.restore();
  y += 18;

  // Group card: compact + pro layout (title centered, row2: technician + QR, then keperluan + kerusakan)
  const groupX = x0;
  const groupY = y;
  const groupW = contentW;
  const pad = 20;
  const colGap = 26;
  const qrColW = 190;
  const leftColW = groupW - pad * 2 - qrColW - colGap;
  const leftX = groupX + pad;
  const rightX = leftX + leftColW + colGap;

  // Baris 1: Judul utama (center) dari "tujuan" (tanpa label)
  const headerText = normalizeContent(extras.tujuan || extras.destination || '-') || '-';
  const headerValue = clipText(headerText, 140).toUpperCase();
  doc.fillColor('#0F172A').font('Helvetica-Bold').fontSize(13.5);
  const titleH = doc.heightOfString(headerValue, { width: groupW - pad * 2, align: 'center', lineGap: 1.1 });
  doc.text(headerValue, groupX + pad, groupY + 16, { width: groupW - pad * 2, align: 'center', lineGap: 1.1 });

  let cy = groupY + 16 + titleH + 10;

  const drawSoftDivider = (yy) => {
    doc.save();
    doc.opacity(0.28);
    doc
      .moveTo(groupX + pad, yy)
      .lineTo(groupX + groupW - pad, yy)
      .lineWidth(1)
      .strokeColor('#DCE7F5')
      .stroke();
    doc.opacity(1);
    doc.restore();
  };

  drawSoftDivider(cy);
  cy += 10;

  // Baris 2: Teknisi (kiri) + QR (kanan) sejajar vertikal
  label('Teknisi', leftX, cy);
  const techY = cy + 16;
  const techLineH = 16;
  doc.fillColor('#0F172A').font('Helvetica').fontSize(10);
  doc.text(`${technician || '-'}`, leftX, techY, { width: leftColW });
  doc.text(`Kontak: ${technicianContact || '-'}`, leftX, techY + techLineH, { width: leftColW });
  doc.text(`Email: ${technicianEmail || '-'}`, leftX, techY + techLineH * 2, { width: leftColW });
  const techBlockH = 16 + techLineH * 3;

  const qrImg = 70;
  const caption = 'Scan untuk buka Google Maps.';
  const captionFont = 8.5;
  const captionLineGap = 1.25;
  doc.font('Helvetica').fontSize(captionFont);
  const captionH = doc.heightOfString(caption, { width: qrColW, lineGap: captionLineGap });
  const qrBlockH = qrPng ? qrImg + 8 + captionH : 0;
  const row2H = Math.max(techBlockH, qrBlockH) + 6;

  if (qrPng) {
    const row2Top = cy;
    const qrTop = row2Top + Math.max(0, Math.round((row2H - qrBlockH) / 2));
    const qrLeft = rightX + Math.round((qrColW - qrImg) / 2);
    try {
      doc.image(qrPng, qrLeft, qrTop, { width: qrImg, height: qrImg });
    } catch (_) {}
    doc
      .fillColor('#334155')
      .font('Helvetica')
      .fontSize(captionFont)
      .text(caption, rightX, qrTop + qrImg + 8, { width: qrColW, align: 'center', lineGap: captionLineGap });
  }

  cy += row2H + 8;
  drawSoftDivider(cy - 2);
  cy += 10;

  // Baris 3: Keperluan (full width)
  label('Keperluan', leftX, cy);
  doc.fillColor('#0F172A').font('Helvetica').fontSize(11);
  const kepMaxH = 78;
  const kepText = fitText(instruction || '-', groupW - pad * 2, kepMaxH, { lineGap: 2, suffix: '...' });
  doc.text(kepText, leftX, cy + 16, { width: groupW - pad * 2, lineGap: 2 });
  const kepH = Math.min(kepMaxH, doc.heightOfString(kepText, { width: groupW - pad * 2, lineGap: 2 }));
  cy += 16 + kepH + 10;

  // Baris 4: Kerusakan / Catatan (full width)
  label('Kerusakan / Catatan', leftX, cy);
  valueText(damage, leftX, cy + 16, groupW - pad * 2, { size: 11, maxHeight: 64 });
  const dmgH = Math.min(64, doc.heightOfString(fitText(damage, groupW - pad * 2, 64, { lineGap: 2, suffix: '...' }), { width: groupW - pad * 2, lineGap: 2 }));
  cy += 16 + dmgH + 14;

  // Outer group border (soft / transparent)
  const groupH = Math.max(180, cy - groupY);
  drawCard(groupX, groupY, groupW, groupH, { radius: 12, strokeOpacity: 0.30, strokeColor: '#BFD5F0' });

  // Move y below group
  y = groupY + groupH + 22;

  // Lokasi / Node section
  doc.fillColor('#0F172A').font('Helvetica-Bold').fontSize(12).text('Lokasi / Node', x0, y);
  y += 14;

  const tableY = y + 10;
  const tableW = contentW;
  const rowH = 34;
  const labelW = 120;

  const tableRows = [
    ['Kode', node.code || '-'],
    ['Nama', locationName || '-'],
    ['Jenis', node.type_label || node.type || '-'],
    ['Alamat', normalizeAddress(addressFull)],
  ];

  const rowCount = tableRows.length;
  const addressRowH = 54;
  const tableH = (rowCount - 1) * rowH + addressRowH + 2;
  drawCard(x0, tableY, tableW, tableH);

  let rowCursorY = tableY + 1;
  tableRows.forEach(([k, v], index) => {
    const thisH = index === tableRows.length - 1 ? addressRowH : rowH;
    const ry = rowCursorY;
    if (index > 0) {
      doc
        .save()
        .moveTo(x0 + 1, ry)
        .lineTo(x0 + tableW - 1, ry)
        .lineWidth(1)
        .strokeColor('#EEF2F7')
        .stroke()
        .restore();
    }
    doc.fillColor('#334155').font('Helvetica-Bold').fontSize(8).text(String(k).toUpperCase(), x0 + 18, ry + 12, {
      width: labelW - 24,
    });
    const valueW = tableW - labelW - 36;
    if (index === tableRows.length - 1) {
      doc.fillColor('#0F172A').font('Helvetica').fontSize(10).text(normalizeContent(v), x0 + labelW + 18, ry + 10, {
        width: valueW,
        lineGap: 2,
      });
    } else {
      doc.fillColor('#0F172A').font('Helvetica').fontSize(10).text(fitText(v, valueW, rowH - 12), x0 + labelW + 18, ry + 11, {
        width: valueW,
        lineGap: 1.5,
      });
    }
    rowCursorY += thisH;
  });

  y = tableY + tableH + 20;

  // Signatures (official: solid line + (....................))
  const signGap = 22;
  const signW = (contentW - signGap * 2) / 3;
  const signH = 62;
  const signTop = Math.min(y + 26, pageH - doc.page.margins.bottom - 120);

  const signLabels = ['Admin NOC', 'Teknisi', 'Supervisor'];
  signLabels.forEach((t, i) => {
    const sx = x0 + i * (signW + signGap);
    doc.fillColor('#0F172A').font('Helvetica-Bold').fontSize(10).text(t, sx, signTop, { width: signW, align: 'center' });
    // solid signature line
    doc.save();
    doc
      .moveTo(sx + 10, signTop + 18 + signH)
      .lineTo(sx + signW - 10, signTop + 18 + signH)
      .lineWidth(0.9)
      .strokeColor('#CBD5E1')
      .stroke();
    doc.restore();
    doc.fillColor('#94A3B8').font('Helvetica').fontSize(8.5).text('( .................... )', sx, signTop + 18 + signH + 6, {
      width: signW,
      align: 'center',
    });
    doc.fillColor('#94A3B8').font('Helvetica').fontSize(8.5).text('Nama / Tanggal', sx, signTop + 18 + signH + 18, {
      width: signW,
      align: 'center',
    });
  });

  // Small footer
  const bottomLimit = pageH - (doc.page.margins?.bottom || 0);
  doc.fillColor('#94A3B8').font('Helvetica').fontSize(7.5).text(`Generated at ${createdText}`, x0, bottomLimit - 10);

  return doc;
}

function buildTopologyPdf({ title = 'Topology Report', nodes = [], links = [] }) {
  const doc = new PDFDocument({ size: 'A4', margin: 40 });
  const pageW = 595.28;

  doc.save();
  doc.rect(0, 0, pageW, 86).fill('#0B1220');
  doc.fillColor('#FFFFFF');
  doc.font('Helvetica-Bold').fontSize(17).text(title, 40, 24, { align: 'center' });
  doc.font('Helvetica').fontSize(10).text('Sistem Mapping Jaringan', 40, 52, { align: 'center' });
  doc.restore();

  doc.fillColor('#111827');
  doc.moveDown(3.5);
  doc.font('Helvetica-Bold').fontSize(11).text('Ringkasan', { continued: false });
  doc.moveDown(0.5);
  doc.font('Helvetica').fontSize(9).text(`Jumlah node: ${nodes.length}`);
  doc.font('Helvetica').fontSize(9).text(`Jumlah link: ${links.length}`);
  doc.moveDown(1);

  if (nodes.length > 0) {
    doc.font('Helvetica-Bold').fontSize(11).text('Daftar Node');
    doc.moveDown(0.5);
    nodes.forEach((node, index) => {
      const label = node.type_label || node.type || '-';
      const coord =
        Number.isFinite(node.latitude) && Number.isFinite(node.longitude) ? `${node.latitude}, ${node.longitude}` : '-';
      doc.font('Helvetica-Bold').fontSize(9).text(`${index + 1}. ${node.code} (${label})`, { continued: false });
      doc
        .font('Helvetica')
        .fontSize(9)
        .fillColor('#6B7280')
        .text(`    Nama: ${node.name || '-'} | Lokasi: ${coord}`);
      doc
        .font('Helvetica')
        .fontSize(9)
        .fillColor('#6B7280')
        .text(`    Alamat: ${node.address || '-'}${node.notes ? ` | Catatan: ${clipText(node.notes, 160)}` : ''}`);
      doc.fillColor('#111827');
      doc.moveDown(0.5);
    });
    doc.moveDown(0.5);
  }

  if (links.length > 0) {
    doc.addPage();
    doc.font('Helvetica-Bold').fontSize(11).text('Daftar Link');
    doc.moveDown(0.5);
    links.forEach((link, index) => {
      const label = link.cable_type ? `${link.cable_type}` : '-';
      const core = link.core_count ? `core ${link.core_count}` : '-';
      doc
        .font('Helvetica-Bold')
        .fontSize(9)
        .text(`${index + 1}. ${link.source_code} -> ${link.target_code}`, { continued: false });
      doc
        .font('Helvetica')
        .fontSize(9)
        .fillColor('#6B7280')
        .text(`    Kabel: ${label} | ${core} ${link.core_number ? `(${link.core_number})` : ''}`);
      doc
        .font('Helvetica')
        .fontSize(9)
        .fillColor('#6B7280')
        .text(`    PON: ${link.pon_name || '-'} | ODC: ${link.odc_name || '-'} | Catatan: ${link.notes || '-'}`);
      doc.fillColor('#111827');
      doc.moveDown(0.5);
    });
  }

  doc.moveDown(1);
  doc
    .font('Helvetica')
    .fontSize(8)
    .fillColor('#6B7280')
    .text('Dokumen ini di-generate otomatis oleh Sistem Mapping.', 40, doc.y, { align: 'left' });

  return doc;
}

function buildSuratJalanPdfBuffer(opts) {
  return new Promise(async (resolve, reject) => {
    try {
      const next = { ...(opts || {}), extras: { ...((opts || {}).extras || {}) } };
      const node = next.node || {};
      const lat = Number.isFinite(Number(node.latitude)) ? Number(node.latitude).toFixed(6) : null;
      const lng = Number.isFinite(Number(node.longitude)) ? Number(node.longitude).toFixed(6) : null;
      const maps = lat && lng ? `https://maps.google.com/?q=${lat},${lng}` : null;
      const address = String(node.address || '').trim();
      const addressUrl = address ? `https://maps.google.com/?q=${encodeURIComponent(address)}` : null;
      const qrUrl = maps || addressUrl;

      if (qrUrl && !next.extras.qr_png && !next.extras.maps_qr_png) {
        next.extras.qr_png = await QRCode.toBuffer(qrUrl, {
          type: 'png',
          errorCorrectionLevel: 'M',
          margin: 1,
          scale: 4,
          color: { dark: '#0B1220', light: '#FFFFFF' },
        });
      }

      const doc = buildSuratJalanPdf(next);
      const chunks = [];
      doc.on('data', (d) => chunks.push(d));
      doc.on('end', () => resolve(Buffer.concat(chunks)));
      doc.on('error', reject);
      doc.end();
    } catch (e) {
      reject(e);
    }
  });
}

export { buildSuratJalanPdf, buildSuratJalanPdfBuffer, buildTopologyPdf };
