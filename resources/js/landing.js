import './bootstrap';
import AOS from 'aos';
import 'aos/dist/aos.css';

document.addEventListener('DOMContentLoaded', function() {
    AOS.init({
        duration: 800, 
        easing: 'ease-out-cubic',
        
        // UBAH DUA BARIS INI:
        once: false,    // Agar animasi berulang setiap kali elemen terlihat
        mirror: true,   // Agar animasi jalan saat scroll ke atas (mundur)
        
        offset: 50,     // Jarak trigger sedikit dikurangi agar lebih responsif di HP
    });
});