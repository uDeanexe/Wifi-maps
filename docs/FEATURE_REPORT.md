  # Wifi-maps - Feature & API Report

Tanggal: 2026-05-24  
Repo: `d:\development\Wifi-maps`

Dokumen ini merangkum fitur yang **sudah ada** (UI + endpoint yang berperan sebagai API), struktur data inti, serta **rencana update** (khususnya audit trail dan pemetaan jalur kabel jaringan).

---

## 1) Ringkasan Sistem

Sistem ini adalah aplikasi Laravel untuk pemetaan jaringan berbasis:
- **Node** (titik: ODC/PON/ODP/tiang/customer/server/OLC, dll) dengan koordinat GPS dan posisi topology (canvas).
- **Link** (relasi koneksi antar node) dengan metadata kabel (jenis kabel, jumlah core, nomor core, PON/ODC name, catatan).
- **Map View**: tampilan peta (Leaflet + OpenStreetMap) menampilkan marker node dan garis koneksi.
- **Topology View**: kanvas topology interaktif (drag node, pan/zoom, bikin link via klik port).
- **Report**: export PDF dan CSV untuk nodes/links/topology.
- **User & Role**: login, logout, manajemen user (role + active flag).

Catatan: repo ini tidak memakai `routes/api.php`; seluruh endpoint ada di `routes/web.php` dan dilindungi middleware `auth` (kecuali `/login`).

---

## 2) Fitur Utama (Sudah Ada)

### A. Autentikasi & Akses
- Login: validasi email + password, hanya user `is_active=true` yang bisa masuk.
- Logout: invalidate session + regenerate token.
- Middleware:
  - `guest` untuk halaman login.
  - `auth` untuk semua fitur mapping/report.

### B. Dashboard
- Halaman ringkasan total `nodes`, `links`, dan `users`.

### C. Map View (Peta Jaringan)
- Marker node berdasarkan `latitude/longitude`.
- Polyline link berdasarkan node source/target.
- Focus node dari querystring (`focus_node`, `lat`, `lng`).
- Tersedia tombol unduh report PDF.

### D. Topology View (Editor Topologi)
- Menampilkan node sebagai card dengan posisi `topology_x/topology_y`.
- Interaksi:
  - Drag node lalu auto-save posisi via `PATCH /nodes/{node}/position` (JSON).
  - Pan canvas (drag area kosong).
  - Zoom (Ctrl/Meta + wheel).
  - Buat link: klik port node sumber lalu klik port node tujuan (POST JSON ke `/links`).
  - Fit-to-content: double click area kosong.

### E. CRUD Node
- Buat node termasuk upload foto (`photo`) ke storage public.
- Update node termasuk ganti foto (menghapus file lama di disk).
- Delete node sekaligus menghapus link terkait (transaction).
- Validasi koordinat:
  - Normalisasi lat/lng (termasuk deteksi tertukar).
  - Reject koordinat di luar rentang valid.

### F. CRUD Link
- Buat/update/delete link antar node.
- Validasi:
  - source != target
  - unik per pasangan `source_node_id + target_node_id`
- Link menyimpan metadata kabel: `cable_type`, `core_count`, `core_number`, `pon_name`, `odc_name`, `notes`.

### G. Import/Export
- Import CSV:
  - Nodes: by `code`, update jika sudah ada.
  - Links: by `source_code` + `target_code`, update jika sudah ada.
  - Error dan skip dibatasi (maks 25 message ditampilkan).
- Export CSV:
  - `nodes-YYYY-MM-DD.csv`
  - `links-YYYY-MM-DD.csv`
- Export PDF:
  - Topology report (nodes + links + ringkasan)
  - Nodes only
  - Links only
  - Generator PDF utama via Node script `scripts/pdf-report.mjs` dengan fallback generator PDF sederhana jika proses Node gagal.

### H. Manajemen User
- List user + create user (khusus role `superadmin`/`admin`).
- Role yang digunakan: `superadmin`, `admin`, `supervisor_noc`, `teknisi`.
- Guard: admin biasa tidak boleh membuat `superadmin`.

---

## 3) Endpoint (Web + “API-like”)

Sumber: `routes/web.php`.

### Public (Guest)
- `GET /login` — form login (`login`)
- `POST /login` — proses login (`login.store`)

### Auth Required
- `POST /logout` (`logout`)
- `GET /` — dashboard (`dashboard`)
- `GET /map` — map view (`map`)
- `GET /topology` — topology view (`topology`)

#### Nodes
- `GET /nodes` — list + form (`nodes.index`)
- `POST /nodes` — create (`nodes.store`)
- `PUT /nodes/{node}` — update (`nodes.update`)
- `PATCH /nodes/{node}/position` — update `topology_x/y` (mendukung JSON response jika `Accept: application/json`) (`nodes.position`)
- `DELETE /nodes/{node}` — delete (`nodes.destroy`)
- `POST /nodes/import-csv` — import nodes CSV (`nodes.import.csv`)

#### Links
- `GET /links` — list + form (`links.index`)
- `POST /links` — create (mendukung JSON response jika `Accept: application/json`) (`links.store`)
- `PUT /links/{link}` — update (`links.update`)
- `DELETE /links/{link}` — delete (`links.destroy`)
- `POST /links/import-csv` — import links CSV (`links.import.csv`)

#### Users
- `GET /users` — list (`users.index`)
- `POST /users` — create (`users.store`)

#### Reports
- `GET /reports/topology.pdf` (`reports.topology.pdf`)
- `GET /reports/nodes.pdf` (`reports.nodes.pdf`)
- `GET /reports/links.pdf` (`reports.links.pdf`)
- `GET /reports/nodes.csv` (`reports.nodes.csv`)
- `GET /reports/links.csv` (`reports.links.csv`)

---

## 4) Struktur Data Inti (Database)

### A. `node_types`
Seed default di `database/seeders/DatabaseSeeder.php`:
- `odc`, `pon`, `box`, `pole`, `customer`, `server`, `olc` (dengan label + icon).

### B. `nodes`
Field utama:
- Identitas: `node_type_id`, `code` (unique), `name`
- Lokasi: `latitude`, `longitude`, `address`
- Media: `photo_path`
- Topologi: `topology_x`, `topology_y`
- Catatan: `notes`

### C. `links`
Field utama:
- Relasi: `source_node_id`, `target_node_id` (unique per pasangan)
- Kabel: `cable_type`, `core_count`, `core_number`
- Metadata: `pon_name`, `odc_name`, `notes`

### D. `users`
Tambahan field mapping:
- `role` (default `teknisi`)
- `is_active` (default `true`)
- `phone` (nullable)

### E. `activity_logs` (Sudah Ada di schema, belum terpakai)
- `action`, `entity_type`, `entity_id`, `description`, timestamps

Catatan: tabel `incidents` dan `work_reports` sempat ada di migrasi awal, tetapi sudah di-drop (migrasi `2026_05_24_000001_drop_incidents_and_work_reports.php`).

---

## 5) Rencana Update Mendatang (Roadmap)

Bagian ini adalah proposal yang selaras dengan kebutuhan “audit” dan “pemetaan jalur kabel”. Detail final bisa disesuaikan (misal kebutuhan field tambahan, format report, dan scope role).

### A. Audit Trail (Untuk Audit Operasional & Kepatuhan)

Tujuan:
- Menjawab “siapa mengubah apa, kapan, dari mana”.
- Memudahkan investigasi perubahan data node/link/user.
- Menjadi sumber untuk report audit (PDF/CSV) dan filtering.

Minimum scope yang disarankan:
1. Logging event untuk operasi:
   - Node: create/update/delete, update position (topology_x/y)
   - Link: create/update/delete
   - User: create/activate/deactivate/change role
2. Menyimpan metadata:
   - `user_id` pelaku (perlu kolom baru pada `activity_logs`)
   - `ip_address`, `user_agent` (opsional tapi berguna)
   - `before` / `after` (JSON) untuk audit value-level (kolom baru)
3. Halaman “Audit Log” + export:
   - Filter by rentang tanggal, entity, action, pelaku
   - Export CSV/PDF

Catatan implementasi:
- Bisa via Observer/Eloquent events atau service wrapper (misal `MappingService` menulis log setelah sukses).

### B. Pemetaan Jalur Kabel Jaringan (Cable Route Mapping)

Kondisi saat ini:
- “Link” masih sebatas koneksi antar node + metadata kabel, dan pada Map View garis ditarik lurus antar koordinat node (bukan jalur aktual di lapangan).

Target update:
- Setiap link dapat memiliki **jalur kabel aktual** (polyline) di peta.

Minimum scope yang disarankan:
1. Menyimpan geometri jalur:
   - Opsi 1 (ringan): tambah kolom `links.route_geojson` (JSON) berisi LineString/Polyline.
   - Opsi 2 (lebih rapi): tabel baru `link_routes` / `link_route_points` (support multi-segmen + versi).
2. Editor jalur di Map View:
   - Mode “Draw cable route” untuk memilih link lalu menggambar polyline mengikuti jalan/tiang.
   - Simpan, edit, hapus jalur.
   - Hitung panjang jalur (estimasi meter) dan simpan `route_length_m`.
3. Report jalur kabel:
   - Export CSV: link + panjang + core info + status jalur (ada/tidak).
   - PDF: ringkasan jalur per ODC/PON/area.
4. Import/Export jalur:
   - CSV untuk metadata link tetap dipertahankan.
   - Tambahan import/export GeoJSON/KML (opsional) untuk integrasi survei lapangan.

### C. (Opsional) Manajemen Core/Fiber yang Lebih Detail

Jika kebutuhan audit kabel meningkat:
- Tambah konsep “fiber allocation” (core dipakai untuk apa/siapa) per link.
- Field contoh: `fiber_no`, `service_id/customer_id`, `status` (free/used/reserved), `notes`.

---

## 6) Konfigurasi & Catatan Operasional

- `AUTO_SEED_DEMO=true` akan membuat contoh node/link jika database masih kosong.
- Superadmin default dibuat via env:
  - `DEFAULT_SUPERADMIN_EMAIL` (fallback: `jonusadeveloper@gmail.com`)
  - `DEFAULT_SUPERADMIN_PASSWORD` (fallback: `superadmin123`)
