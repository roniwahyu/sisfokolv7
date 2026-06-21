# Desain Sistem Navigasi & Komponen Antarmuka Modern
## Cetak Biru Reusable Frontend Partials (Inertia JS & Vue 3 / Livewire)
**Konteks:** Transformasi Menu Legacy SISFOKOL v7 ke Navigasi Dinamis & Plug-and-Play SaaS

---

## 1. Konsep Navigasi Responsif & Imersif

Sistem navigasi baru menggantikan menu statis hardcoded pada template HTML legacy SISFOKOL v7. Navigasi dirancang menggunakan **Inertia.js dengan komponen Vue 3** yang reaktif, modern, serta mendukung integrasi menu dinamis dari **Modular Plugins** (Plug-and-Play) dan isolasi tenant.

### 1.1. Pilar Utama Navigasi Modern
1.  **Dynamic Role-Based Rendering:** Server (Laravel) mendeteksi peran aktif pengguna dan mengirimkan daftar menu yang sah melalui payload Inertia (`$page.props.auth.menus`). Frontend me-render menu secara otomatis.
2.  **Plugin Menu Injection (Plug-and-Play):** Jika modul plugin eksternal (seperti LMS atau WhatsApp Gateway) aktif untuk tenant tersebut, plugin dapat menginjeksikan menu ke layout utama melalui Event Hook `NavigationRegister`.
3.  **Responsive Dashboard Layout:** Tata letak sidebar adaptif yang dapat disembunyikan (*collapsible sidebar*) untuk layar smartphone, dilengkapi dengan gerakan gestur sentuh (touch swipe) pada perangkat mobile.
4.  **SaaS Tenant & Role Switcher:** Untuk pengguna yang memiliki multi-role (misal: Guru Mapel sekaligus Wali Kelas) atau Global Admin SaaS, disediakan komponen *quick-switcher* di bilah atas (topbar) untuk berganti konteks secara instan tanpa reload halaman.

---

## 2. Struktur Komponen Antarmuka (Partials Vue 3)

Tata letak antarmuka diorganisasikan dalam komponen parsial yang reusable (dapat digunakan kembali) untuk menjamin konsistensi visual:

```
resources/js/
├── Layouts/
│   └── AppLayout.vue         # Shell Layout utama (Sidebar + Topbar + Content Frame)
├── Partials/
│   ├── Sidebar.vue           # Panel navigasi samping dengan dukungan scroll terpisah
│   ├── Topbar.vue            # Baris atas (pencarian, notifikasi, profil, switcher)
│   ├── NotificationMenu.vue  # Dropdown notifikasi real-time via WebSockets
│   ├── TenantSwitcher.vue    # Switcher Sekolah (khusus Super Admin)
│   └── RoleSwitcher.vue      # Switcher Peran aktif pengguna
└── Components/
    ├── SidebarMenuItem.vue   # Item menu tunggal dengan icon SVG dinamis
    ├── SidebarMenuDropdown.vue # Kelompok menu dropdown bertingkat
    └── Card.vue              # Kontainer konten standar yang konsisten
```

---

## 3. Peta Navigasi Dinamis Target (Unified Tenant System)

Berikut adalah struktur peta navigasi modern yang dihasilkan secara dinamis berdasarkan otorisasi server (Spatie Permissions) dan status plugin tenant:

### 3.1. Struktur Menu Utama Layout (`AppLayout.vue`)

```json
[
  {
    "category": "Utama",
    "items": [
      { "label": "Dashboard", "route": "dashboard", "icon": "HomeIcon", "roles": ["*"] }
    ]
  },
  {
    "category": "Akademik",
    "items": [
      {
        "label": "Kurikulum",
        "icon": "AcademicCapIcon",
        "roles": ["Admin", "Kepala_Sekolah", "Guru_Mapel", "Wali_Kelas"],
        "children": [
          { "label": "Tahun Pelajaran", "route": "classrooms.index", "roles": ["Admin"] },
          { "label": "Daftar Kelas", "route": "classrooms.index", "roles": ["Admin", "Kepala_Sekolah"] },
          { "label": "Mata Pelajaran", "route": "subjects.index", "roles": ["Admin", "Guru_Mapel"] },
          { "label": "Jadwal Pelajaran", "route": "schedules.index", "roles": ["Admin", "Guru_Mapel", "Wali_Kelas", "Siswa"] }
        ]
      },
      {
        "label": "Asesmen Rapor",
        "icon": "DocumentReportIcon",
        "roles": ["Guru_Mapel", "Wali_Kelas", "Kepala_Sekolah", "Siswa", "Orang_Tua"],
        "children": [
          { "label": "Tujuan Pembelajaran (TP)", "route": "tp.index", "roles": ["Guru_Mapel"] },
          { "label": "Input Skor Formatif", "route": "scores.formatif", "roles": ["Guru_Mapel"] },
          { "label": "Input Skor Sumatif", "route": "scores.sumatif", "roles": ["Guru_Mapel"] },
          { "label": "Nilai Karakter P5", "route": "p5.index", "roles": ["Wali_Kelas"] },
          { "label": "Cetak Rapor", "route": "rapor.print", "roles": ["Wali_Kelas", "Kepala_Sekolah", "Siswa", "Orang_Tua"] }
        ]
      }
    ]
  },
  {
    "category": "Keuangan & Tabungan",
    "items": [
      {
        "label": "Keuangan Siswa",
        "icon": "CashIcon",
        "roles": ["Bendahara", "Kepala_Sekolah", "Siswa", "Orang_Tua"],
        "children": [
          { "label": "Kasir Pembayaran", "route": "finance.kasir", "roles": ["Bendahara"] },
          { "label": "Master Item Tagihan", "route": "finance.items", "roles": ["Admin", "Bendahara"] },
          { "label": "Laporan Tunggakan", "route": "finance.tunggakan", "roles": ["Bendahara", "Wali_Kelas", "Kepala_Sekolah"] },
          { "label": "Riwayat Setor SPP", "route": "finance.history", "roles": ["Siswa", "Orang_Tua"] }
        ]
      },
      {
        "label": "Tabungan Siswa",
        "icon": "CreditCardIcon",
        "roles": ["Bendahara", "Siswa"],
        "children": [
          { "label": "Mutasi Tabungan", "route": "savings.index", "roles": ["Bendahara"] },
          { "label": "Info Saldo", "route": "savings.client", "roles": ["Siswa"] }
        ]
      }
    ]
  },
  {
    "category": "Kesiswaan & BK",
    "items": [
      {
        "label": "Presensi Harian",
        "icon": "FingerPrintIcon",
        "roles": ["Piket", "Wali_Kelas", "Kepala_Sekolah", "Siswa", "Orang_Tua"],
        "children": [
          { "label": "Konsol QR Scanner", "route": "presence.scan", "roles": ["Piket"] },
          { "label": "Rekap Kehadiran", "route": "presence.recap", "roles": ["Wali_Kelas", "Kepala_Sekolah", "Siswa", "Orang_Tua"] },
          { "label": "Izin Keluar Kelas", "route": "presence.permits", "roles": ["Piket", "Wali_Kelas"] }
        ]
      },
      {
        "label": "Kedisiplinan & BK",
        "icon": "ExclamationIcon",
        "roles": ["Guru_BK", "Piket", "Wali_Kelas", "Kepala_Sekolah", "Siswa", "Orang_Tua"],
        "children": [
          { "label": "Input Pelanggaran", "route": "discipline.infraction", "roles": ["Piket", "Guru_BK"] },
          { "label": "Daftar Pembinaan BK", "route": "discipline.pembinaan", "roles": ["Guru_BK", "Wali_Kelas"] },
          { "label": "Poin Disiplin Siswa", "route": "discipline.poin", "roles": ["Siswa", "Orang_Tua", "Kepala_Sekolah"] }
        ]
      }
    ]
  },
  {
    "category": "Injeksi Plugin (Plug-and-Play)",
    "items": [
      {
        "label": "E-Learning LMS",
        "icon": "BookOpenIcon",
        "plugin_dependency": "lms-learning",
        "roles": ["Guru_Mapel", "Siswa"],
        "children": [
          { "label": "Bank Soal & Ujian", "route": "lms.exams", "roles": ["Guru_Mapel", "Siswa"] },
          { "label": "Materi Pembelajaran", "route": "lms.materials", "roles": ["Guru_Mapel", "Siswa"] }
        ]
      },
      {
        "label": "WA Gateway",
        "icon": "ChatIcon",
        "plugin_dependency": "whatsapp-gateway",
        "roles": ["Admin"],
        "children": [
          { "label": "Pengaturan Notifikasi", "route": "plugins.wa.settings", "roles": ["Admin"] },
          { "label": "Log Pesan Keluar", "route": "plugins.wa.logs", "roles": ["Admin"] }
        ]
      }
    ]
  }
]
```

---

## 4. Implementasi Reaktif Komponen Sidebar Vue 3 (`Sidebar.vue`)

Komponen reaktif di bawah ini menunjukkan bagaimana menu di-render secara aman menggunakan sistem properti Inertia.js dan menyaring hak akses menu secara dinamis di tingkat *Client-Side*:

```html
<template>
  <aside class="sidebar bg-slate-900 text-white w-60 h-screen flex flex-col border-r border-slate-800">
    <div class="sidebar-brand h-16 flex items-center px-6 border-b border-slate-800">
      <span class="font-bold text-lg text-emerald-400">SMP IT Modern</span>
    </div>
    <nav class="flex-1 overflow-y-auto py-4">
      <div v-for="cat in filteredMenus" :key="cat.category" class="mb-4">
        <h4 class="text-xs uppercase text-slate-500 font-semibold px-6 mb-2 tracking-wider">
          {{ cat.category }}
        </h4>
        <ul>
          <li v-for="item in cat.items" :key="item.label">
            <Link 
              v-if="!item.children" 
              :href="route(item.route)" 
              class="flex items-center gap-3 px-6 py-2.5 text-sm text-slate-300 hover:bg-slate-800 hover:text-white transition-all"
              :class="{ 'bg-slate-800 text-emerald-400 font-bold border-l-4 border-emerald-400': isActive(item.route) }"
            >
              <span class="menu-text">{{ item.label }}</span>
            </Link>
          </li>
        </ul>
      </div>
    </nav>
  </aside>
</template>

<script>
import { Link } from '@inertiajs/vue3';

export default {
  components: { Link },
  props: {
    userRole: String,
    activePermissions: Array,
    menus: Array
  },
  computed: {
    filteredMenus() {
      return this.menus.filter(cat => {
        const allowedItems = cat.items.filter(item => {
          if (item.roles.includes('*') || item.roles.includes(this.userRole)) {
            return true;
          }
          return false;
        });
        return allowedItems.length > 0;
      });
    }
  },
  methods: {
    isActive(routeName) {
      return this.$page.component === routeName;
    }
  }
}
</script>
```
