# 🩸 DBSCAN Clustering Analysis for Diabetes Data - Aceh Utara

![PHP Version](https://img.shields.io/badge/PHP-92.2%25-blue)
![CSS Version](https://img.shields.io/badge/CSS-7.8%25-purple)
![Status](https://img.shields.io/badge/Status-Active-green)

## 📊 Tentang Proyek

DBSCAN Clustering Dashboard adalah aplikasi analisis data berbasis web untuk mengidentifikasi dan memvisualisasikan persebaran kasus diabetes di wilayah Aceh Utara. Proyek ini menggunakan algoritma DBSCAN (Density-Based Spatial Clustering of Applications with Noise) untuk mengelompokkan data diabetes berdasarkan kepadatan populasi dan faktor risiko.

Demo: [http://www.diabetesacehutara.my.id/](http://www.diabetesacehutara.my.id/)

![Dashboard Preview](/img/dashboard-preview.png)

## 🔍 Fitur Utama

- **Analisis Clustering** - Menggunakan algoritma DBSCAN untuk mengidentifikasi area dengan konsentrasi kasus diabetes tinggi
- **Visualisasi Data** - Menampilkan hasil analisis dalam bentuk dashboard interaktif
- **Analisis Regional** - Membandingkan data dari berbagai wilayah di Aceh Utara (Paya Bakong, Geuredong Pase, Simpang Kramat, dll.)
- **Basis Data SQL** - Penyimpanan data terstruktur menggunakan SQL database

## 🛠️ Teknologi

- **Backend**: PHP (92.2%)
- **Frontend**: HTML, CSS (7.8%), JavaScript
- **Database**: MySQL
- **Algoritma**: DBSCAN (Density-Based Spatial Clustering of Applications with Noise)
- **Deployment**: Heroku

## 📋 Persyaratan Sistem

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web server (Apache/Nginx)
- Browser modern (Chrome, Firefox, Edge, Safari)

## 🚀 Instalasi

1. **Clone Repository**
   ```bash
   git clone https://github.com/AhmadFauziAbdillah/diabetesacehutara.git
   cd diabetesacehutara
   ```

2. **Konfigurasi Database**
   - Import file `dbscan.sql` ke MySQL database Anda
   - Sesuaikan pengaturan koneksi database di file konfigurasi

3. **Konfigurasi Web Server**
   - Arahkan document root web server ke folder proyek
   - Pastikan mod_rewrite diaktifkan (untuk Apache)

4. **Akses Aplikasi**
   - Buka aplikasi melalui browser: `http://localhost/` atau domain Anda

## 📊 Struktur Data

Analisis DBSCAN menggunakan data dari beberapa wilayah di Aceh Utara:

| Wilayah | Populasi | Kasus Diabetes | Risiko |
|---------|----------|----------------|--------|
| Paya Bakong | 16,377 | 8 | Rendah |
| Geuredong Pase | 5,851 | 8 | Rendah |
| Simpang Kramat | 10,272 | 0 | Rendah |
| Syamtalira Bayu | 23,325 | 0 | Rendah |
| Tanah Jambo Aye | 45,472 | 0 | Rendah |
| Tanah Luas | 25,992 | 0 | Rendah |

## 🧪 Metodologi

Algoritma DBSCAN digunakan karena keunggulannya untuk data geografis:

1. **Tidak memerlukan jumlah cluster yang telah ditentukan** - Ideal untuk eksplorasi data geografis
2. **Dapat mendeteksi outlier** - Penting untuk mengidentifikasi anomali dalam data kesehatan
3. **Mampu mengenali cluster berbentuk tidak teratur** - Cocok untuk pola persebaran penyakit

## 👨‍💻 Pengembang

**Ahmad Fauzi Abdillah**  
Mahasiswa Informatika Universitas Malikussaleh  
[GitHub](https://github.com/AhmadFauziAbdillah) | [Instagram](https://www.instagram.com/faujiabdilah_/)

## 📄 Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).

## 🤝 Kontribusi

Kontribusi sangat diterima! Jika Anda menemukan bug atau memiliki saran untuk peningkatan:

1. Fork repository
2. Buat branch fitur (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan Anda (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buka Pull Request

---

© 2025 DBSCAN Clustering Analysis for Diabetes Data
