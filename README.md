--------------------------------------
Hey ^_^
--------------------------------------
Platform berbasis web untuk mengelola kursus, materi pembelajaran, tugas, penilaian, akses pengguna, dan progres pembelajaran.

### Latar Belakang/Masalah: ###
Sebelum LMS diperkenalkan, pelatihan karyawan dilakukan secara offline oleh tim HR, yang memerlukan penjadwalan sesi khusus serta biaya tambahan untuk konsumsi karyawan. Hal ini membuat proses pelatihan kurang fleksibel dan berpotensi mengganggu jam kerja karyawan.
Kehadiran LMS memungkinkan karyawan menyelesaikan pelatihan secara online sesuai waktu yang mereka miliki, memberikan fleksibilitas yang lebih besar sekaligus mengurangi biaya konsumsi. LMS ini juga membantu tim HR memenuhi kebutuhan administrasi dan dokumentasi pelatihan sesuai ketentuan OJK dengan lebih efektif.
LMS ini digunakan secara rutin setiap bulan oleh seluruh  karyawan PT. Sunindo Kookmin Best Finance.

--------------------------------------
# ✨ Fitur Utama
### 👥 Manajemen Pengguna
- Multi-role: Admin, Pengajar (Instruktur), dan Siswa
- Manajemen profil dan hak akses berbasis peran (role-based access control)
### 📚 Manajemen Kursus & Materi
- CRUD kursus, modul, dan materi pembelajaran (video, dokumen, teks)
- Pengaturan jadwal dan status kursus (draft, aktif, selesai)
- **Course Takeover / Pengalihan Pengajar**
  - Mekanisme pengalihan kepemilikan kursus ke pengajar lain apabila pengajar utama berhalangan (cuti, resign, sakit, dsb)
  - Notifikasi otomatis ke pengajar pengganti terkait saat terjadi takeover
### 📝 Quiz & Penilaian
- Pembuatan quiz dengan berbagai tipe soal (pilihan ganda, esai, dan forum diskusi)
- Penilaian otomatis untuk soal objektif, penilaian manual untuk esai
- Rekap nilai per siswa, per kursus, dan leaderboard/progress tracking
### ✅ Approval Stages (Alur Persetujuan Berjenjang)
- Multi-level approval untuk publikasi kursus/materi baru (misal: Pengajar → Reviewer → Admin)
- Status tracking di setiap tahap (pending, revisi, disetujui, ditolak)
- Catatan/feedback pada setiap tahap approval agar pengajar bisa melakukan revisi
### 🔔 Sistem Notifikasi
- Notifikasi real-time (in-app) dan/atau email untuk:
  - Tugas baru & deadline pengumpulan
  - Perubahan status approval
### 📊 Dashboard Progres Belajar
- Visualisasi progres belajar siswa per kursus
- Statistik penyelesaian tugas dan quiz
- Dashboard khusus untuk admin memantau seluruh aktivitas platform
### 📈 HR Reporting & Analytics
- Akses khusus tim HR untuk melihat dan mengolah data hasil pembelajaran di seluruh kursus
- Rekap tingkat partisipasi, penyelesaian kursus, dan pencapaian nilai per karyawan/siswa
- Filter laporan berdasarkan divisi, departemen, periode, atau jenis pelatihan
- Ekspor laporan ke format Excel untuk keperluan evaluasi kinerja atau kebutuhan administratif
- Integrasi data pembelajaran sebagai bahan pertimbangan pengembangan karier (learning & development tracking)
  
--------------------------------------
## 🛠️ Tech Stack
- **Frontend:** [HTML/CSS/Javascript]
- **Backend:** [Laravel]
- **Database:** [MySQL]
- **Server:** [Apache/localhost]
