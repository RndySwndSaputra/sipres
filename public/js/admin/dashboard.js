(() => {
  // 1. ANIMASI COUNTER (TETAP)
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

  // 2. [PERBAIKAN] COMBO CHART (ACARA VS KEHADIRAN)
  const ctx = document.getElementById('comboChart');
  
  if (ctx && typeof Chart !== 'undefined' && typeof chartDataConfig !== 'undefined') {
      
      // Setup Gradient untuk Line Chart
      let chartContext = ctx.getContext('2d');
      let gradient = chartContext.createLinearGradient(0, 0, 0, 400);
      gradient.addColorStop(0, 'rgba(45, 138, 110, 0.2)'); // Brand Color Transparent
      gradient.addColorStop(1, 'rgba(45, 138, 110, 0)');

      new Chart(ctx, {
          type: 'bar',
          data: {
              labels: chartDataConfig.labels,
              datasets: [
                  {
                      // Dataset 1: Kehadiran (Line Chart)
                      type: 'line',
                      label: 'Total Kehadiran',
                      data: chartDataConfig.datasets.hadir,
                      borderColor: '#2d8a6e', // Brand Strong
                      backgroundColor: gradient,
                      borderWidth: 2,
                      pointBackgroundColor: '#fff',
                      pointBorderColor: '#2d8a6e',
                      pointRadius: 4,
                      fill: true,
                      tension: 0.4, // Membuat garis melengkung halus
                      yAxisID: 'y'
                  },
                  {
                      // Dataset 2: Jumlah Acara (Bar Chart)
                      type: 'bar',
                      label: 'Jumlah Acara',
                      data: chartDataConfig.datasets.acara,
                      backgroundColor: '#cbd5e1', // Abu-abu lembut
                      borderRadius: 4,
                      barThickness: 20,
                      yAxisID: 'y1'
                  }
              ]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              interaction: {
                  mode: 'index',
                  intersect: false,
              },
              plugins: {
                  legend: {
                      display: true,
                      position: 'top',
                      align: 'end',
                      labels: {
                          usePointStyle: true,
                          boxWidth: 8
                      }
                  },
                  tooltip: {
                      backgroundColor: '#1e293b',
                      padding: 12,
                      titleFont: { size: 13 },
                      bodyFont: { size: 12 },
                      cornerRadius: 8,
                      displayColors: true
                  }
              },
              scales: {
                  x: {
                      grid: { display: false }
                  },
                  y: {
                      type: 'linear',
                      display: true,
                      position: 'left',
                      title: { display: true, text: 'Peserta Hadir', color: '#2d8a6e', font: {size: 11, weight: 'bold'} },
                      grid: { borderDash: [4, 4], color: '#f1f5f9' }
                  },
                  y1: {
                      type: 'linear',
                      display: true,
                      position: 'right',
                      title: { display: true, text: 'Jml Acara', color: '#64748b', font: {size: 11} },
                      grid: { drawOnChartArea: false }, // Agar grid tidak tumpang tindih
                      ticks: { stepSize: 1 }
                  }
              }
          }
      });
  }
})();