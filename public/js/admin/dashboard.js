(() => {
  // 1. ANIMASI COUNTER (KODE LAMA ANDA - TETAP)
  const counters = Array.from(document.querySelectorAll('[data-counter]'));
  if (counters.length) {
      const formatNumber = (n) => n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      
      const animate = (el, to) => {
          const duration = 1000;
          const start = 0;
          const startTime = performance.now();
          const cleanTo = String(to).replace(/,/g, '').replace(/\./g, '');
          const target = parseInt(cleanTo, 10);
          
          if (isNaN(target)) { el.textContent = to; return; }

          const tick = (now) => {
              const elapsed = now - startTime;
              const t = Math.min(1, elapsed / duration);
              const eased = 1 - Math.pow(1 - t, 3); 
              const value = Math.round(start + (target - start) * eased);
              
              el.textContent = formatNumber(value);
              
              if (t < 1) requestAnimationFrame(tick);
              else el.textContent = formatNumber(target);
          };
          requestAnimationFrame(tick);
      };

      const observer = new IntersectionObserver((entries, obs) => {
          entries.forEach(entry => {
              if (entry.isIntersecting) {
                  animate(entry.target, entry.target.getAttribute('data-counter'));
                  obs.unobserve(entry.target);
              }
          });
      }, { threshold: 0.5 });

      counters.forEach(el => observer.observe(el));
  }

  // 2. [BARU] INISIALISASI CHART TAHUNAN
  // Pastikan element canvas ada dan Chart.js sudah terload
  const ctx = document.getElementById('yearlyChart');
  if (ctx && typeof Chart !== 'undefined' && typeof chartDataConfig !== 'undefined') {
      new Chart(ctx, {
          type: 'bar', // Grafik Batang
          data: {
              labels: chartDataConfig.labels, // ['Jan', 'Feb', ...]
              datasets: [{
                  label: 'Jumlah Acara',
                  data: chartDataConfig.data, // Data dari DB
                  backgroundColor: '#2d8a6e', // Warna Hijau Sipres
                  borderRadius: 4,
                  barThickness: 25
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: { display: false },
                  tooltip: {
                      callbacks: {
                          label: function(context) {
                              return context.raw + ' Acara';
                          }
                      }
                  }
              },
              scales: {
                  y: {
                      beginAtZero: true,
                      grid: { borderDash: [2, 4], color: '#f1f5f9' },
                      ticks: { stepSize: 1 }
                  },
                  x: {
                      grid: { display: false }
                  }
              }
          }
      });
  }
})();